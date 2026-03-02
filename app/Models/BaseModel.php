<?php

namespace App\Models;

use App\Support\Concerns\HasAuditNotifications;
use App\Support\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BaseModel extends Model
{
    use HasAuditNotifications;
    
    /**
     * @var array<string, bool>
     */
    protected static array $organizationColumnCache = [];

    protected static function booted(): void
    {
        static::addGlobalScope('organization_isolation', function (Builder $query): void {
            if (! Auth::hasUser()) {
                return;
            }

            $user = Auth::user();

            if (! $user || $user->role === UserRole::SUPER_ADMIN) {
                return;
            }

            $table = $query->getModel()->getTable();

            if (! static::tableHasOrganizationColumn($table)) {
                return;
            }

            $query->where($query->qualifyColumn('organization_id'), $user->organization_id);
        });
    }

    protected static function tableHasOrganizationColumn(string $table): bool
    {
        if (! array_key_exists($table, static::$organizationColumnCache)) {
            static::$organizationColumnCache[$table] = Schema::hasColumn($table, 'organization_id');
        }

        return static::$organizationColumnCache[$table];
    }
}
