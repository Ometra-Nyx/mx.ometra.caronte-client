<?php

/**
 * @author Gabriel Ruelas
 * @license MIT
 * @version 1.4.0
 */

namespace Ometra\Caronte\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * Eloquent model for Caronte users.
 *
 * @author Gabriel Ruelas
 * @license MIT
 * @version 1.4.0
 */
class CaronteUser extends Model
{
    protected $table;
    protected $primaryKey = 'uri_user';
    protected $keyType    = 'string';

    public $timestamps   = false;
    public $incrementing = false;

    protected $fillable = [
        'id_tenant',
        'uri_user',
        'name',
        'email'
    ];

    protected $hidden = [];

    /**
     * Initialize the model.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('caronte.table_prefix') . 'Users';
    }

    /**
     * Boot model with optional BeeHive tenant scoping when available.
     *
     * @return void
     */
    protected static function booted(): void
    {
        if (!static::beeHiveEnabled()) {
            return;
        }

        $tenantScopeClass = 'Equidna\\BeeHive\\Scopes\\TenantScope';
        static::addGlobalScope(new $tenantScopeClass());

        static::creating(function (self $model): void {
            $tenantKey = $model->getTenantKeyName();

            if (!empty($model->{$tenantKey})) {
                return;
            }

            $tenantContextClass = 'Equidna\\BeeHive\\Tenancy\\TenantContext';
            $context = app($tenantContextClass);
            $tenantId = $context->get();

            if ($tenantId !== null) {
                $model->{$tenantKey} = $tenantId;
            }
        });
    }

    /**
     * Resolve BeeHive tenant key name from configuration.
     */
    public function getTenantKeyName(): string
    {
        return (string) Config::get('bee-hive.tenant_key', 'id_tenant');
    }

    /**
     * Determine if BeeHive tenancy package is installed and ready.
     */
    protected static function beeHiveEnabled(): bool
    {
        $tenantScopeClass = 'Equidna\\BeeHive\\Scopes\\TenantScope';
        $tenantContextClass = 'Equidna\\BeeHive\\Tenancy\\TenantContext';

        return class_exists($tenantScopeClass)
            && class_exists($tenantContextClass)
            && app()->bound($tenantContextClass);
    }

    /**
     * Get the metadata relationship for the user.
     *
     * @return HasMany
     */
    public function metadata(): HasMany
    {
        return $this->hasMany(CaronteUserMetadata::class, 'uri_user');
    }

    /**
     * Mutator to set the user's name with proper casing.
     *
     * @param string $value Name value.
     * @return void
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = ucwords($value);
    }

    /**
     * Scope to search users by name or email.
     *
     * @param Builder $query Eloquent query builder.
     * @param string|null $search Search term.
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
