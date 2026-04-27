<?php

namespace Ometra\Caronte\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Ometra\Caronte\CaronteRoleManager;
use Ometra\Caronte\Support\CaronteResponse;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends BaseController
{
    public function sync(): Response
    {
        try {
            $response = CaronteRoleManager::syncConfiguredRoles();

            return redirect()
                ->route('caronte.management.dashboard')
                ->with('success', $response['message']);
        } catch (\Exception $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                forwardUrl: route('caronte.management.dashboard')
            );
        }
    }
}
