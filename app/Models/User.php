<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Support\UserRole;
use App\Support\Concerns\HasAuditNotifications;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\ResourceNotification;
use App\Models\CustomRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasAuditNotifications;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'parent_id',
        'name',
        'email',
        'password',
        'role',
        'invitation_token',
        'invitation_accepted_at',
        'status',
        'locale',
        'custom_role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'invitation_accepted_at' => 'datetime',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function customRole(): BelongsTo
    {
        return $this->belongsTo(CustomRole::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'inviter_id');
    }

    public function resourceNotifications(): HasMany
    {
        return $this->hasMany(ResourceNotification::class, 'recipient_id');
    }

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function getTenants(Panel $panel): array|Collection
    {
        if ($this->organization) {
            return collect([$this->organization]);
        }

        return $this->organizations;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->isRole(UserRole::SUPER_ADMIN)) {
            return true;
        }

        return $this->organization_id === $tenant->getKey() ||
            $this->organizations()->whereKey($tenant)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'super-admin') {
            return $this->isRole(UserRole::SUPER_ADMIN);
        }

        return true;
    }

    public function hasCustomPermission(string $permission): bool
    {
        $permissions = $this->customRole?->permissions ?? [];

        return in_array($permission, $permissions, true);
    }

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

            $query->where($query->qualifyColumn('organization_id'), $user->organization_id);
        });
    }
}
