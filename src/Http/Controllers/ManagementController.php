<?php

namespace Ometra\Caronte\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Inertia\Response as InertiaResponse;
use Ometra\Caronte\Api\ClientApi;
use Ometra\Caronte\CaronteRoleManager;
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
            $preview = CaronteRoleManager::previewSync();

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
                forwardUrl: (string) config('caronte.LOGIN_URL')
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
}
