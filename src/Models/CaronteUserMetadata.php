<?php

/**
 * @author Gabriel Ruelas
 * @license MIT
 * @version 1.4.0
 */

namespace Ometra\Caronte\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Equidna\Toolkit\Traits\Database\HasCompositePrimaryKey;

/**
 * Eloquent model for Caronte user metadata.
 *
 * @author Gabriel Ruelas
 * @license MIT
 * @version 1.4.0
 */
class CaronteUserMetadata extends Model
{
    use HasCompositePrimaryKey;

    protected $table;
    protected $primaryKey = ['uri_user', 'scope', 'key'];

    public $timestamps = false;

    protected $fillable = [
        'id_tenant',
        'uri_user',
        'key',
        'value',
        'scope'
    ];

    /**
     * Initialize the model.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('caronte.table_prefix') . 'UsersMetadata';
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
     * Get the user relationship for this metadata.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(CaronteUser::class, 'uri_user');
    }
}
