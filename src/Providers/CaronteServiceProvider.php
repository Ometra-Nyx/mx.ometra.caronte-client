<?php

/**
 * Service provider for Caronte Client package registration and bootstrapping.
 *
 * PHP 8.1+
 *
 * @package   Ometra\Caronte\Providers
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/Ometra-Core/mx.ometra.caronte-client Documentation
 */

namespace Ometra\Caronte\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Equidna\Toolkit\Exceptions\ConflictException;
use Inertia\Inertia;
use Exception;

use Ometra\Caronte\Console\Commands\ManagementCaronte;
use Ometra\Caronte\Console\Commands\ManagementRoles;
use Ometra\Caronte\Console\Commands\ManagementUsers;
use Ometra\Caronte\Console\Commands\Roles\CreateRole;
use Ometra\Caronte\Console\Commands\Roles\DeleteRole;
use Ometra\Caronte\Console\Commands\Roles\ShowRoles;
use Ometra\Caronte\Console\Commands\Roles\UpdateRole;
use Ometra\Caronte\Console\Commands\Users\AttachRoles;
use Ometra\Caronte\Console\Commands\Users\CreateUser;
use Ometra\Caronte\Console\Commands\Users\DeleteRolesUser;
use Ometra\Caronte\Console\Commands\Users\ShowRolesByUser;
use Ometra\Caronte\Console\Commands\Users\UpdateUser;
use Ometra\Caronte\Facades\Caronte;
use Ometra\Caronte\Helpers\PermissionHelper;
use Ometra\Caronte\Http\Middleware\ValidateRoles;
use Ometra\Caronte\Http\Middleware\ValidateSession;

class CaronteServiceProvider extends ServiceProvider
{
    /**
     * Registers package services and merges configuration.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            Caronte::class,
            fn() => new Caronte()
        );

        $this->mergeConfigFrom(__DIR__ . '/../../config/caronte.php', 'caronte');
    }

    /**
     * Boots package resources, routes, and publishable assets.
     *
     * @param  Router $router  Router instance for registering middleware aliases.
     * @return void
     * @throws ConflictException  When required configuration values are missing.
     */
    public function boot(Router $router)
    {
        if ($this->shouldValidateCaronteConfig()) {
            $this->validateCaronteConfig();
        }

        //Registers the Caronte alias and facade.
        $loader = AliasLoader::getInstance();
        $loader->alias('Caronte', Caronte::class);
        $loader->alias('PermissionHelper', PermissionHelper::class);

        //Registers the middleware
        $router->aliasMiddleware('Caronte.ValidateSession', ValidateSession::class);
        $router->aliasMiddleware('Caronte.ValidateRoles', ValidateRoles::class);

        //Registers the base Routes for clients
        Route::prefix(config('caronte.ROUTES_PREFIX'))->middleware(['web'])->group(
            function () {
                $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
            }
        );

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'caronte');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        //Config
        $this->publishes(
            [
                __DIR__ . '/../../config/caronte.php' => config_path('caronte.php'),
            ],
            [
                'caronte:config',
                'caronte',
            ]
        );

        //Views
        $this->publishes(
            [
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/caronte'),
            ],
            [
                'caronte:views',
                'caronte',
            ]
        );

        //Assets
        $this->publishes(
            [
                __DIR__ . '/../../resources/assets' => public_path('vendor/caronte'),
            ],
            [
                'caronte-assets',
                'caronte',
            ]
        );

        //Inertia
        $this->publishes(
            [
                __DIR__ . '/../../resources/js' => resource_path('js/vendor/caronte'),
            ],
            [
                'caronte:inertia',
                'caronte',
            ]
        );

        //Migrations
        $this->publishes(
            [
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ],
            [
                'caronte:migrations',
                'caronte'
            ]
        );

        //Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                AttachRoles::class,
                ManagementRoles::class,
                ManagementUsers::class,
                CreateRole::class,
                UpdateRole::class,
                DeleteRole::class,
                ShowRoles::class,
                CreateUser::class,
                DeleteRolesUser::class,
                UpdateUser::class,
                ShowRolesByUser::class,
                ManagementCaronte::class,
            ]);
        }


        Inertia::share('caronte_user', function () {
            try {
                if (Caronte::checkToken()) {
                    return Caronte::getUser();
                }
            } catch (Exception $e) {
                return null;
            }

            return null;
        });
    }

    /**
     * Validates required Caronte config values and fails early with a clear message.
     *
     * @throws ConflictException  When one or more required config values are missing.
     */
    protected function validateCaronteConfig(): void
    {
        $required = [
            'caronte.URL',
            'caronte.APP_ID',
            'caronte.APP_SECRET',
            'caronte.LOGIN_URL',
        ];

        $missing = [];
        foreach ($required as $key) {
            $value = config($key);
            if (is_null($value) || $value === '') {
                $missing[] = $key;
            }
        }

        if (config('caronte.ENFORCE_ISSUER')) {
            if (empty(config('caronte.ISSUER_ID'))) {
                $missing[] = 'caronte.ISSUER_ID';
            }
        }

        if (!empty($missing)) {
            $msg = "Caronte: Missing required configuration: " . implode(', ', $missing) . ". Please check your .env and config/caronte.php.";
            throw new ConflictException($msg);
        }
    }

    /**
     * Determines whether strict config validation must run in current execution context.
     *
     * In console mode, only Caronte's own commands should require strict validation.
     * This prevents unrelated tooling commands from failing before .env is generated.
     */
    protected function shouldValidateCaronteConfig(): bool
    {
        if (!$this->app->runningInConsole()) {
            return true;
        }

        $argv = $_SERVER['argv'] ?? [];
        $command = $argv[1] ?? '';

        if (!is_string($command) || $command === '') {
            return false;
        }

        return str_starts_with($command, 'caronte-client:');
    }
}
