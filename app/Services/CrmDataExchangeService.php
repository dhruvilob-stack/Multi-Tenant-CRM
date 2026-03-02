<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class CrmDataExchangeService
{
    /**
     * @var array<string, array<string, array{type_name:string,nullable:bool}>>
     */
    protected array $columnMetaCache = [];

    /**
     * @return array<string, class-string<Model>>
     */
    public function resourceModelMap(): array
    {
        return [
            'products' => \App\Models\Product::class,
            'categories' => \App\Models\Category::class,
            'users' => \App\Models\User::class,
            'orders' => \App\Models\Order::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function crmFields(string $resource): array
    {
        $modelClass = $this->modelClass($resource);

        return Schema::getColumnListing((new $modelClass())->getTable());
    }

    /**
     * @return array{imported:int,updated:int,skipped:int,errors:int,error_samples:array<int,string>,halted:bool,auto_skipped_steps:bool,first_changed_id:int|null}
     */
    public function import(string $resource, array $data): array
    {
        $modelClass = $this->modelClass($resource);
        $rows = $this->parseRows($data);

        $duplicateHandling = (string) ($data['duplicate_handling'] ?? 'skip');
        $matchingFields = $this->parseMatchingFields($data['matching_fields'] ?? '');
        $mappings = $this->normalizeMappings($data['column_mappings'] ?? []);
        $skipInvalid = (bool) ($data['skip_invalid_rows'] ?? true);

        $stats = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0, 'error_samples' => [], 'halted' => false, 'auto_skipped_steps' => false, 'first_changed_id' => null];

        [$autoSkippable, $autoDetectedMatchingFields] = $this->canAutoSkipSteps($resource, $rows);
        if ($autoSkippable) {
            $duplicateHandling = 'update';
            $matchingFields = $autoDetectedMatchingFields;
            $mappings = [];
            $stats['auto_skipped_steps'] = true;
        }

        foreach ($rows as $rowIndex => $row) {
            try {
                $payload = $this->mapRow($row, $mappings);
                $payload = $this->normalizePayload($resource, $payload);

                if ($payload === []) {
                    $stats['skipped']++;
                    continue;
                }

                $query = $modelClass::query();
                $existing = null;

                if ($matchingFields !== []) {
                    foreach ($matchingFields as $field) {
                        if (! array_key_exists($field, $payload)) {
                            continue;
                        }

                        $query->where($field, $payload[$field]);
                    }

                    $existing = $query->first();
                }

                if ($existing) {
                    if ($duplicateHandling === 'skip') {
                        $stats['skipped']++;
                        continue;
                    }

                    if ($duplicateHandling === 'update') {
                        $existing->fill($payload);
                        $existing->save();
                        $stats['updated']++;
                        $stats['first_changed_id'] ??= (int) $existing->getKey();
                        continue;
                    }
                }

                $created = $modelClass::query()->create($payload);
                $stats['imported']++;
                $stats['first_changed_id'] ??= (int) $created->getKey();
            } catch (\Throwable $exception) {
                $stats['errors']++;
                if (count($stats['error_samples']) < 5) {
                    $stats['error_samples'][] = 'Row ' . ($rowIndex + 1) . ': ' . $exception->getMessage();
                }

                if (! $skipInvalid) {
                    $stats['halted'] = true;
                    break;
                }
            }
        }

        return $stats;
    }

    /**
     * @return array{headers:array<int,string>,exact_match:bool,suggested_mappings:array<int,array{source_column:string,crm_field:string,manual_value:string}>,matching_fields:array<int,string>,auto_skip_possible:bool}
     */
    public function detectImportHeaders(string $resource, array $data): array
    {
        $fileType = strtolower((string) ($data['file_type'] ?? 'csv'));
        $filePath = $this->normalizeImportFileState($data['import_file'] ?? null);
        $definedColumns = (string) ($data['defined_columns'] ?? '');

        if ($filePath === '') {
            return [
                'headers' => [],
                'exact_match' => false,
                'suggested_mappings' => [],
                'matching_fields' => [],
                'auto_skip_possible' => false,
            ];
        }

        $headers = $fileType === 'json' || $fileType === 'mongoose'
            ? $this->detectJsonHeaders($data)
            : $this->detectCsvHeaders(
                $data,
                $definedColumns,
            );

        $headers = array_values(array_filter(array_map(fn ($header) => trim((string) $header), $headers)));

        if ($headers === []) {
            return [
                'headers' => [],
                'exact_match' => false,
                'suggested_mappings' => [],
                'matching_fields' => [],
                'auto_skip_possible' => false,
            ];
        }

        $crmFields = $this->crmFields($resource);
        $crmLookup = array_flip($crmFields);
        $exactMatch = collect($headers)->every(fn (string $header): bool => isset($crmLookup[$header]));

        $suggestedMappings = [];
        foreach ($headers as $header) {
            if (! isset($crmLookup[$header])) {
                continue;
            }

            $suggestedMappings[] = [
                'source_column' => $header,
                'crm_field' => $header,
                'manual_value' => '',
            ];
        }

        $matchingFields = [];
        foreach (['id', 'sku', 'email', 'order_number', 'invoice_number', 'quotation_number', 'slug', 'name'] as $candidate) {
            if (in_array($candidate, $headers, true) && isset($crmLookup[$candidate])) {
                $matchingFields[] = $candidate;
                break;
            }
        }

        return [
            'headers' => $headers,
            'exact_match' => $exactMatch,
            'suggested_mappings' => $suggestedMappings,
            'matching_fields' => $matchingFields,
            'auto_skip_possible' => $exactMatch,
        ];
    }

    /**
     * @return array{path:string,filename:string}
     */
    public function export(string $resource, array $data): array
    {
        $modelClass = $this->modelClass($resource);
        $rows = max(1, min(50000, (int) ($data['rows_limit'] ?? 100)));
        $type = strtolower((string) ($data['file_type'] ?? 'csv'));

        $records = $modelClass::query()->latest('id')->limit($rows)->get()->map->attributesToArray()->all();

        $directory = storage_path('app/tmp-exports');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $timestamp = now()->format('Ymd_His');
        $filename = "{$resource}_export_{$timestamp}.{$type}";
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        if ($type === 'json' || $type === 'mongoose') {
            file_put_contents($path, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return ['path' => $path, 'filename' => $filename];
        }

        if ($type === 'sql') {
            file_put_contents($path, $this->toSqlInsertStatements($modelClass, $records));

            return ['path' => $path, 'filename' => $filename];
        }

        if ($type === 'csv' || $type === 'xlsx') {
            $handle = fopen($path, 'wb');
            $headers = array_keys($records[0] ?? []);
            if ($headers !== []) {
                fputcsv($handle, $headers);
                foreach ($records as $record) {
                    fputcsv($handle, array_map(fn ($h) => $record[$h] ?? null, $headers));
                }
            }
            fclose($handle);

            return ['path' => $path, 'filename' => $filename];
        }

        throw new RuntimeException('Unsupported export type.');
    }

    protected function modelClass(string $resource): string
    {
        $map = $this->resourceModelMap();
        $resource = strtolower(trim($resource));

        if (! isset($map[$resource])) {
            throw new RuntimeException("Unsupported resource [{$resource}] for data exchange.");
        }

        return $map[$resource];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function parseRows(array $data): array
    {
        $fileType = strtolower((string) ($data['file_type'] ?? 'csv'));
        $filePath = $this->normalizeImportFileState($data['import_file'] ?? null);
        $maxFileSizeMb = 100;

        if ($filePath === '') {
            throw new RuntimeException('Please upload a file.');
        }

        $absolute = $this->resolveAbsoluteImportPath($filePath);

        if (! $absolute) {
            throw new RuntimeException('Uploaded file not found on server.');
        }

        $sizeBytes = @filesize($absolute);
        if ($sizeBytes !== false) {
            $maxBytes = $maxFileSizeMb * 1024 * 1024;
            if ($sizeBytes > $maxBytes) {
                throw new RuntimeException("File exceeds max allowed size of {$maxFileSizeMb} MB.");
            }
        }

        if ($fileType === 'json' || $fileType === 'mongoose') {
            $decoded = json_decode((string) file_get_contents($absolute), true);

            if (! is_array($decoded)) {
                throw new RuntimeException('Invalid JSON file.');
            }

            return array_values(array_filter($decoded, fn ($row) => is_array($row)));
        }

        if ($fileType === 'sql') {
            return $this->parseSqlRows((string) file_get_contents($absolute));
        }

        $delimiterMap = [
            'comma' => ',',
            'semicolon' => ';',
            'pipe' => '|',
            'caret' => '^',
            ',' => ',',
            ';' => ';',
            '|' => '|',
            '^' => '^',
        ];
        $delimiter = $delimiterMap[strtolower((string) ($data['delimiter'] ?? 'comma'))] ?? ',';

        return $this->parseCsvRows($absolute, $delimiter, (string) ($data['defined_columns'] ?? ''));
    }

    protected function resolveAbsoluteImportPath(string $filePath): ?string
    {
        $normalized = ltrim(str_replace('\\', '/', $filePath), '/');
        $candidates = [
            storage_path('app/private/' . $normalized),
            storage_path('app/' . $normalized),
            storage_path('framework/' . $normalized),
        ];

        if (! str_contains($normalized, '/')) {
            $candidates[] = storage_path('framework/livewire-tmp/' . $normalized);
            $candidates[] = storage_path('app/livewire-tmp/' . $normalized);
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function parseCsvRows(string $absolutePath, string $delimiter, string $definedColumns): array
    {
        $handle = fopen($absolutePath, 'rb');
        if (! $handle) {
            throw new RuntimeException('Unable to open import file.');
        }

        $firstRow = fgetcsv($handle, 0, $delimiter) ?: [];
        $defined = $this->parseMatchingFields($definedColumns);
        $headers = $defined !== [] ? $defined : array_map(fn ($h) => trim((string) $h), $firstRow);
        $firstRowLooksLikeHeader = $this->rowLooksLikeHeader($firstRow, $headers);

        if ($defined !== [] && ! $firstRowLooksLikeHeader) {
            rewind($handle);
        }

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($row === [null] || $row === [] || $this->rowIsEmpty($row)) {
                continue;
            }

            $item = [];
            foreach ($headers as $index => $header) {
                $item[$header] = $row[$index] ?? null;
            }

            if ($this->mappedRowLooksLikeHeader($item)) {
                continue;
            }

            $rows[] = $item;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    protected function detectCsvHeaders(array $data, string $definedColumns): array
    {
        $filePath = $this->normalizeImportFileState($data['import_file'] ?? null);
        $delimiterMap = [
            'comma' => ',',
            'semicolon' => ';',
            'pipe' => '|',
            'caret' => '^',
            ',' => ',',
            ';' => ';',
            '|' => '|',
            '^' => '^',
        ];
        $delimiter = $delimiterMap[strtolower((string) ($data['delimiter'] ?? 'comma'))] ?? ',';
        $defined = $this->parseMatchingFields($definedColumns);

        if ($defined !== []) {
            return $defined;
        }

        $absolute = $this->resolveAbsoluteImportPath($filePath);
        if (! $absolute) {
            return [];
        }

        $handle = fopen($absolute, 'rb');
        if (! $handle) {
            return [];
        }

        $firstRow = fgetcsv($handle, 0, $delimiter) ?: [];
        fclose($handle);

        return array_values(array_filter(array_map(fn ($value) => trim((string) $value), $firstRow)));
    }

    /**
     * @return array<int, string>
     */
    protected function detectJsonHeaders(array $data): array
    {
        $filePath = $this->normalizeImportFileState($data['import_file'] ?? null);
        $absolute = $this->resolveAbsoluteImportPath($filePath);
        if (! $absolute) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($absolute), true);
        if (! is_array($decoded) || ! is_array($decoded[0] ?? null)) {
            return [];
        }

        return array_map(fn ($value) => (string) $value, array_keys($decoded[0]));
    }

    /**
     * @return Collection<int, User>
     */
    public function exchangeRecipients(?User $actor): Collection
    {
        $query = User::query()->where('status', 'active');

        if ($actor && $actor->role !== UserRole::SUPER_ADMIN) {
            $query->where(function ($inner) use ($actor): void {
                $inner->where('organization_id', $actor->organization_id)
                    ->orWhere('role', UserRole::SUPER_ADMIN);
            });
        }

        $users = $query->get()->filter(fn (User $user): bool => $actor ? $user->id !== $actor->id : true);

        return $users->values();
    }

    protected function normalizeImportFileState(mixed $state): string
    {
        if (is_array($state)) {
            $first = reset($state);

            return is_string($first) ? $first : '';
        }

        return is_string($state) ? $state : '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function parseSqlRows(string $sql): array
    {
        $rows = [];

        preg_match_all('/insert\s+into\s+[^\(]+\((.*?)\)\s+values\s*(.*?);/ims', $sql, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $columns = array_map(fn ($c) => trim($c, " `\"'"), explode(',', $match[1]));
            $valuesChunk = trim((string) $match[2]);

            preg_match_all('/\((.*?)\)/s', $valuesChunk, $valueMatches);
            foreach ($valueMatches[1] as $valueSet) {
                $values = str_getcsv($valueSet, ',', "'");
                $row = [];
                foreach ($columns as $index => $column) {
                    $row[$column] = $values[$index] ?? null;
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $mappings
     * @return array<int, array{source_column:string,crm_field:string,manual_value:mixed}>
     */
    protected function normalizeMappings(array $mappings): array
    {
        $normalized = [];

        foreach ($mappings as $mapping) {
            $source = trim((string) ($mapping['source_column'] ?? ''));
            $field = trim((string) ($mapping['crm_field'] ?? ''));
            $manual = $mapping['manual_value'] ?? null;

            if ($field === '') {
                continue;
            }

            $normalized[] = [
                'source_column' => $source,
                'crm_field' => $field,
                'manual_value' => $manual,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, array{source_column:string,crm_field:string,manual_value:mixed}> $mappings
     * @return array<string, mixed>
     */
    protected function mapRow(array $row, array $mappings): array
    {
        if ($mappings === []) {
            return $row;
        }

        $payload = [];

        foreach ($mappings as $mapping) {
            $source = $mapping['source_column'];
            $field = $mapping['crm_field'];
            $manual = $mapping['manual_value'];

            if ($manual !== null && $manual !== '') {
                $payload[$field] = $manual;
                continue;
            }

            if ($source !== '' && array_key_exists($source, $row)) {
                $payload[$field] = $row[$source];
            }
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function normalizePayload(string $resource, array $payload): array
    {
        if (isset($payload['id']) && (string) $payload['id'] === '') {
            unset($payload['id']);
        }

        $user = Auth::user();
        if ($user && $user->role !== UserRole::SUPER_ADMIN && array_key_exists('organization_id', $payload) === false) {
            $payload['organization_id'] = $user->organization_id;
        }

        if ($resource === 'categories') {
            if (! empty($payload['name']) && empty($payload['slug'])) {
                $payload['slug'] = Str::slug((string) $payload['name']);
            }
        }

        if ($resource === 'products') {
            if (! empty($payload['category_name']) && empty($payload['category_id'])) {
                $payload['category_id'] = Category::query()->firstOrCreate(
                    [
                        'name' => (string) $payload['category_name'],
                        'organization_id' => $payload['organization_id'] ?? $user?->organization_id,
                    ],
                    [
                        'slug' => Str::slug((string) $payload['category_name']),
                    ],
                )->getKey();
            }

            unset($payload['category_name']);
        }

        if ($resource === 'users') {
            if (! empty($payload['password'])) {
                $payload['password'] = bcrypt((string) $payload['password']);
            }

            if (! isset($payload['status']) || $payload['status'] === null || $payload['status'] === '') {
                $payload['status'] = 'active';
            }
        }

        return $this->coercePayloadBySchema($resource, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function coercePayloadBySchema(string $resource, array $payload): array
    {
        $columnMeta = $this->columnMeta($resource);

        foreach ($payload as $field => $value) {
            if (! array_key_exists($field, $columnMeta)) {
                continue;
            }

            $type = $columnMeta[$field]['type_name'];
            $nullable = $columnMeta[$field]['nullable'];

            if (is_string($value)) {
                $trimmed = trim($value);

                if ($trimmed === '' || strtoupper($trimmed) === 'NULL') {
                    if ($nullable || $this->isNonStringColumnType($type)) {
                        $payload[$field] = null;
                    }

                    continue;
                }

                if ($this->isIntegerColumnType($type) && is_numeric($trimmed)) {
                    $payload[$field] = (int) $trimmed;
                    continue;
                }

                if ($this->isDecimalColumnType($type) && is_numeric($trimmed)) {
                    $payload[$field] = (float) $trimmed;
                    continue;
                }

                if ($this->isBooleanColumnType($type)) {
                    $normalized = strtolower($trimmed);
                    if (in_array($normalized, ['1', 'true', 'yes', 'y'], true)) {
                        $payload[$field] = true;
                        continue;
                    }

                    if (in_array($normalized, ['0', 'false', 'no', 'n'], true)) {
                        $payload[$field] = false;
                        continue;
                    }
                }
            }
        }

        return $payload;
    }

    /**
     * @return array<string, array{type_name:string,nullable:bool}>
     */
    protected function columnMeta(string $resource): array
    {
        if (isset($this->columnMetaCache[$resource])) {
            return $this->columnMetaCache[$resource];
        }

        $modelClass = $this->modelClass($resource);
        $table = (new $modelClass())->getTable();
        $columns = Schema::getColumns($table);

        $meta = [];
        foreach ($columns as $column) {
            $name = (string) ($column['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $meta[$name] = [
                'type_name' => strtolower((string) ($column['type_name'] ?? 'string')),
                'nullable' => (bool) ($column['nullable'] ?? false),
            ];
        }

        return $this->columnMetaCache[$resource] = $meta;
    }

    protected function isIntegerColumnType(string $type): bool
    {
        return in_array($type, ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'], true);
    }

    protected function isDecimalColumnType(string $type): bool
    {
        return in_array($type, ['decimal', 'double', 'float', 'real'], true);
    }

    protected function isBooleanColumnType(string $type): bool
    {
        return $type === 'boolean' || $type === 'bool' || $type === 'tinyint';
    }

    protected function isNonStringColumnType(string $type): bool
    {
        return ! in_array($type, ['char', 'varchar', 'string', 'text', 'mediumtext', 'longtext'], true);
    }

    /**
     * @return array<int, string>
     */
    protected function parseMatchingFields(string $raw): array
    {
        return collect(explode(',', $raw))
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{0: bool, 1: array<int, string>}
     */
    protected function canAutoSkipSteps(string $resource, array $rows): array
    {
        if ($rows === []) {
            return [false, []];
        }

        $rowKeys = array_keys($rows[0]);
        if ($rowKeys === []) {
            return [false, []];
        }

        $crmFields = array_flip($this->crmFields($resource));
        $allMatch = collect($rowKeys)
            ->every(fn (string $field): bool => isset($crmFields[$field]));

        if (! $allMatch) {
            return [false, []];
        }

        $candidates = [
            'id',
            'sku',
            'email',
            'order_number',
            'invoice_number',
            'quotation_number',
            'slug',
            'name',
        ];

        $matches = [];
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $rowKeys, true)) {
                $matches[] = $candidate;
                break;
            }
        }

        return [true, $matches];
    }

    /**
     * @param array<int, mixed> $row
     * @param array<int, string> $headers
     */
    protected function rowLooksLikeHeader(array $row, array $headers): bool
    {
        if ($row === [] || $headers === []) {
            return false;
        }

        $normalizedRow = array_map(fn ($value): string => Str::lower(trim((string) $value)), $row);
        $normalizedHeaders = array_map(fn (string $value): string => Str::lower(trim($value)), $headers);

        return $normalizedRow === $normalizedHeaders;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function mappedRowLooksLikeHeader(array $row): bool
    {
        if ($row === []) {
            return false;
        }

        foreach ($row as $key => $value) {
            if (Str::lower(trim((string) $key)) !== Str::lower(trim((string) $value))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, mixed> $row
     */
    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param class-string<Model> $modelClass
     * @param array<int, array<string, mixed>> $rows
     */
    protected function toSqlInsertStatements(string $modelClass, array $rows): string
    {
        $table = (new $modelClass())->getTable();

        if ($rows === []) {
            return "-- No rows to export for table {$table}" . PHP_EOL;
        }

        $columns = array_keys($rows[0]);
        $columnSql = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));
        $lines = [];

        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $column) {
                $value = $row[$column] ?? null;
                if ($value === null) {
                    $values[] = 'NULL';
                    continue;
                }

                $escaped = str_replace("'", "''", (string) $value);
                $values[] = "'{$escaped}'";
            }

            $lines[] = 'INSERT INTO `' . $table . '` (' . $columnSql . ') VALUES (' . implode(', ', $values) . ');';
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}
