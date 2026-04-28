<?php

namespace Tests;

use DateTimeImmutable;
use DateTimeZone;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Orchestra\Testbench\TestCase as Orchestra;
use Ometra\Caronte\Providers\CaronteServiceProvider;
use Ometra\Caronte\Support\CaronteApplicationToken;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CaronteServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('app.url', 'https://client.test');
        $app['config']->set('session.driver', 'array');
        $app['config']->set(
            'view.compiled',
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'caronte-client-views-' . getmypid()
        );

        $app['config']->set('caronte.url', 'https://caronte.test/');
        $app['config']->set('caronte.app_cn', 'test-app-id');
        $app['config']->set('caronte.app_secret', 'test-app-secret-with-minimum-length-32');
        $app['config']->set('caronte.login_url', '/login');
        $app['config']->set('caronte.issuer_id', 'caronte');
        $app['config']->set('caronte.enforce_issuer', true);
        $app['config']->set('caronte.routes_prefix', '');
        $app['config']->set('caronte.use_inertia', false);
        $app['config']->set('caronte.management.enabled', true);
        $app['config']->set('caronte.management.route_prefix', 'caronte/management');
        $app['config']->set('caronte.management.access_roles', ['root']);
        $app['config']->set('caronte.management.use_inertia', false);
        $app['config']->set('caronte.management.features.metadata', true);
        $app['config']->set('caronte.roles', [
            'root' => 'Default super administrator role',
            'admin' => 'Administrative access',
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $user
     */
    protected function makeToken(
        ?array $user = null,
        ?DateTimeImmutable $issuedAt = null,
        ?DateTimeImmutable $expiresAt = null,
    ): string {
        $issuedAt ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $expiresAt ??= $issuedAt->modify('+15 minutes');

        $user ??= [
            'uri_user' => 'user-123',
            'name' => 'Root User',
            'email' => 'root@example.com',
            'id_tenant' => 'tenant-1',
            'roles' => [
                [
                    'name' => 'root',
                    'app_id' => CaronteApplicationToken::appId(),
                    'uri_applicationRole' => sha1(CaronteApplicationToken::appId() . 'root'),
                ],
            ],
            'metadata' => [],
        ];

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText((string) config('caronte.app_secret'))
        );

        return $config->builder(ChainedFormatter::default())
            ->issuedBy((string) config('caronte.issuer_id', ''))
            ->issuedAt($issuedAt)
            ->canOnlyBeUsedAfter($issuedAt)
            ->expiresAt($expiresAt)
            ->withClaim('app_id', CaronteApplicationToken::appId())
            ->withClaim('user', json_encode($user))
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }
}
