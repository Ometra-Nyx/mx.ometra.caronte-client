<?php

namespace Ometra\Caronte\Helpers;

use Ometra\Caronte\Facades\Caronte;
use Ometra\Caronte\Support\CaronteApplicationToken;

class PermissionHelper
{
    public static function hasApplication(): bool
    {
        $user = Caronte::getUser();
        $roles = collect($user->roles ?? []);

        return $roles->contains(function ($role): bool {
            $roleAppId = $role->uri_application ?? $role->app_id ?? null;

            return $roleAppId === null || $roleAppId === CaronteApplicationToken::appId();
        });
    }

    public static function hasRoles(mixed $roles): bool
    {
        $user = Caronte::getUser();
        $requiredRoles = is_array($roles) ? $roles : explode(',', (string) $roles);
        $requiredRoles = array_values(array_filter(array_map('trim', $requiredRoles)));
        $requiredRoles[] = 'root';

        if (in_array('_self', $requiredRoles, true) && Caronte::getRouteUser() === ($user->uri_user ?? null)) {
            return true;
        }

        $userRoles = collect($user->roles ?? []);

        return $userRoles->contains(function ($userRole) use ($requiredRoles): bool {
            $roleAppId = $userRole->uri_application ?? $userRole->app_id ?? null;

            return in_array($userRole->name ?? null, $requiredRoles, true)
                && ($roleAppId === null || $roleAppId === CaronteApplicationToken::appId());
        });
    }
}
