<?php

namespace Ometra\Caronte\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Inertia\Response as InertiaResponse;
use Ometra\Caronte\Api\ClientApi;
use Ometra\Caronte\Api\RoleApi;
use Ometra\Caronte\Facades\Caronte;
use Ometra\Caronte\Support\CaronteResponse;
use Ometra\Caronte\Support\ConfiguredRoles;
use Symfony\Component\HttpFoundation\Response;

class ManagementController extends BaseController
{
    public function dashboard(Request $request): View|InertiaResponse|Response
    {
        try {
            $search = trim((string) $request->query('search', ''));
            $response = ClientApi::showUsers(search: $search, usersApp: true);
            $users = collect(is_array($response['data']) ? $response['data'] : []);
            $paginator = $this->paginateUsers($users, $request);
            $preview = $this->previewRoleSync();

            return $this->toView('management.index', [
                'branding' => $this->branding(),
                'search' => $search,
                'tenant_id' => Caronte::getTenantId(),
                'users' => $paginator,
                'configured_roles' => ConfiguredRoles::all(),
                'remote_roles' => array_values($preview['remote']),
                'missing_roles' => $preview['missing'],
                'outdated_roles' => $preview['outdated'],
                'features' => config('caronte.management.features', []),
                'csrf_token' => csrf_token(),
                'routes' => [
                    'dashboard' => route('caronte.management.dashboard'),
                    'rolesSync' => route('caronte.management.roles.sync'),
                    'usersStore' => route('caronte.management.users.store'),
                    'usersShow' => route('caronte.management.users.show', ['uri_user' => '__USER__']),
                    'logout' => route('caronte.logout'),
                ],
            ], true);
        } catch (\Exception $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                forwardUrl: (string) config('caronte.login_url')
            );
        }
    }

    private function paginateUsers(Collection $users, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;
        $items = $users->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            items: $items,
            total: $users->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    /**
     * @return array{configured: array<int, array{name: string, description: string, uri_applicationRole: string}>, remote: array<string, array<string, mixed>>, missing: array<int, string>, outdated: array<int, string>}
     */
    private function previewRoleSync(): array
    {
        $configured = ConfiguredRoles::all();
        $response = RoleApi::showRoles();
        $remoteRoles = is_array($response['data']) ? $response['data'] : [];
        $remote = [];
        $missing = [];
        $outdated = [];

        foreach ($remoteRoles as $role) {
            if (!is_array($role) || !isset($role['name'])) {
                continue;
            }
            $remote[(string) $role['name']] = $role;
        }

        foreach ($configured as $role) {
            $remoteRole = $remote[$role['name']] ?? null;

            if ($remoteRole === null) {
                $missing[] = $role['name'];
                continue;
            }

            if (($remoteRole['description'] ?? null) !== $role['description']) {
                $outdated[] = $role['name'];
            }
        }

        return compact('configured', 'remote', 'missing', 'outdated');
    }
}
