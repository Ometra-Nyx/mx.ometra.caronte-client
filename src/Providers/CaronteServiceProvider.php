<?php

namespace Ometra\Caronte\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Ometra\Caronte\Api\CaronteHttpClient;
use Ometra\Caronte\Caronte;
use Ometra\Caronte\Console\Commands\ManagementCaronte;
use Ometra\Caronte\Console\Commands\Roles\SyncRoles;
use Ometra\Caronte\Console\Commands\Users\CreateUser;
use Ometra\Caronte\Console\Commands\Users\DeleteUser;
use Ometra\Caronte\Console\Commands\Users\ListUsers;
use Ometra\Caronte\Console\Commands\Users\SyncUserRoles;
use Ometra\Caronte\Console\Commands\Users\UpdateUser;
use Ometra\Caronte\Contracts\SendsPasswordRecovery;
use Ometra\Caronte\Contracts\SendsTwoFactorChallenge;
use Ometra\Caronte\Facades\Caronte as CaronteFacade;
use Ometra\Caronte\Helpers\PermissionHelper;
use Ometra\Caronte\Http\Middleware\ResolveApplicationToken;
use Ometra\Caronte\Http\Middleware\ResolveTenantContext;
use Ometra\Caronte\Http\Middleware\ValidateRoles;
use Ometra\Caronte\Http\Middleware\ValidateSession;
use Ometra\Caronte\Notifications\LaravelPasswordRecoverySender;
use Ometra\Caronte\Notifications\LaravelTwoFactorChallengeSender;
use Ometra\Caronte\Support\ConfiguredRoles;

class CaronteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/caronte.php', 'caronte');

        $this->app->singleton(Caronte::class, fn() => new Caronte());
        $this->app->singleton(CaronteHttpClient::class, fn() => new CaronteHttpClient());

        $this->app->bind(SendsTwoFactorChallenge::class, LaravelTwoFactorChallengeSender::class);
        $this->app->bind(SendsPasswordRecovery::class, LaravelPasswordRecoverySender::class);
    }

    public function boot(Router $router): void
    {
        if ($this->shouldValidateCaronteConfig()) {
            $this->validateCaronteConfig();
        }

        $loader = AliasLoader::getInstance();
        $loader->alias('Caronte', CaronteFacade::class);
        $loader->alias('PermissionHelper', PermissionHelper::class);

        $router->aliasMiddleware('caronte.session', ValidateSession::class);
        $router->aliasMiddleware('caronte.roles', ValidateRoles::class);
        $router->aliasMiddleware('caronte.application', ResolveApplicationToken::class);
        $router->aliasMiddleware('caronte.tenant', ResolveTenantContext::class);

        Route::middleware(['web'])->group(function (): void {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        });

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'caronte');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->publishes(
            [
                __DIR__ . '/../../config/caronte.php' => config_path('caronte.php'),
            ],
            ['caronte:config', 'caronte']
        );

        $this->publishes(
            [
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/caronte'),
            ],
            ['caronte:views', 'caronte']
        );

        $this->publishes(
            [
                __DIR__ . '/../../resources/assets' => public_path('vendor/caronte'),
            ],
            ['caronte-assets', 'caronte']
        );

        $this->publishes(
            [
                __DIR__ . '/../../resources/js' => resource_path('js/vendor/caronte'),
            ],
            ['caronte:inertia', 'caronte']
        );

        $this->publishes(
            [
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ],
            ['caronte:migrations', 'caronte']
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                ManagementCaronte::class,
                SyncRoles::class,
                ListUsers::class,
                CreateUser::class,
                UpdateUser::class,
                DeleteUser::class,
                SyncUserRoles::class,
            ]);
        }

        Inertia::share('caronte', function (): array {
            return [
                'branding' => config('caronte.ui.branding', []),
                'management' => [
                    'enabled' => (bool) config('caronte.management.enabled', true),
                    'access_roles' => ConfiguredRoles::accessRoles(),
                ],
                'user' => CaronteFacade::checkToken() ? CaronteFacade::getUser() : null,
            ];
        });
    }

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

            if ($value === null || $value === '') {
                $missing[] = $key;
            }
        }

        if (config('caronte.ENFORCE_ISSUER') && empty(config('caronte.ISSUER_ID'))) {
            $missing[] = 'caronte.ISSUER_ID';
        }

        if ($missing !== []) {
            throw new \InvalidArgumentException(
                'Caronte: Missing required configuration: ' . implode(', ', $missing) . '.'
            );
        }

        $url = (string) config('caronte.URL');
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme !== 'https' && ! (bool) config('caronte.ALLOW_HTTP_REQUESTS', false)) {
            throw new \InvalidArgumentException(
                'Caronte: CARONTE_URL must use HTTPS unless CARONTE_ALLOW_HTTP_REQUESTS=true.'
            );
        }

        ConfiguredRoles::validate();
    }

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

        return str_starts_with($command, 'caronte:');
    }
}
