<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table): void {
            if (! Schema::hasColumn('invitations', 'token_hash')) {
                $table->string('token_hash', 64)->nullable()->after('token');
            }
        });

        DB::table('invitations')->select(['id', 'token'])->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('invitations')
                    ->where('id', $row->id)
                    ->update(['token_hash' => hash('sha256', (string) $row->token)]);
            }
        });

        $dbName = DB::getDatabaseName();
        $hasOldTokenIndex = DB::table('information_schema.statistics')
            ->where('table_schema', $dbName)
            ->where('table_name', 'invitations')
            ->where('index_name', 'invitations_token_unique')
            ->exists();

        if ($hasOldTokenIndex) {
            DB::statement('ALTER TABLE invitations DROP INDEX invitations_token_unique');
        }

        DB::statement('ALTER TABLE invitations MODIFY token TEXT NOT NULL');
        $hasHashIndex = DB::table('information_schema.statistics')
            ->where('table_schema', $dbName)
            ->where('table_name', 'invitations')
            ->where('index_name', 'invitations_token_hash_unique')
            ->exists();

        if (! $hasHashIndex) {
            DB::statement('ALTER TABLE invitations ADD UNIQUE INDEX invitations_token_hash_unique (token_hash)');
        }

        DB::table('invitations')->whereNull('token_hash')->update(['token_hash' => DB::raw('SHA2(token, 256)')]);
    }

    public function down(): void
    {
        $dbName = DB::getDatabaseName();
        $hasHashIndex = DB::table('information_schema.statistics')
            ->where('table_schema', $dbName)
            ->where('table_name', 'invitations')
            ->where('index_name', 'invitations_token_hash_unique')
            ->exists();

        if ($hasHashIndex) {
            DB::statement('ALTER TABLE invitations DROP INDEX invitations_token_hash_unique');
        }

        DB::statement('ALTER TABLE invitations MODIFY token VARCHAR(255) NOT NULL');
        $hasTokenIndex = DB::table('information_schema.statistics')
            ->where('table_schema', $dbName)
            ->where('table_name', 'invitations')
            ->where('index_name', 'invitations_token_unique')
            ->exists();
        if (! $hasTokenIndex) {
            DB::statement('ALTER TABLE invitations ADD UNIQUE INDEX invitations_token_unique (token)');
        }

        Schema::table('invitations', function (Blueprint $table): void {
            $table->dropColumn('token_hash');
        });
    }
};
