<?php

/**
 * @author Gabriel Ruelas
 * @license MIT
 * @version 1.4.0
 *
 */

namespace Ometra\Caronte\Helpers;

use Ometra\Caronte\Facades\Caronte;

class PermissionHelper
{
    public function __construct()
    {
        //
    }

    /**
     * Determine if the user has any roles for the current application.
     *
     * @return bool True if user has roles for the application, false otherwise.
     */
    public static function hasApplication(): bool
    {
        $user   = Caronte::getUser();
        $app_id = sha1(config('caronte.APP_ID'));
        $roles  = collect($user->roles);

        return $roles->contains(
            fn($role) => ($role->uri_application ?? $role->app_id) === $app_id
        );
    }

    /**
     * Determine if the user has any of the specified roles for the application.
     *
     * @param mixed $roles Roles to check (comma-separated string or array).
     * @return bool True if user has any of the specified roles, false otherwise.
     */
    public static function hasRoles(mixed $roles): bool
    {
        $user   = Caronte::getUser();
        $app_id = sha1(config('caronte.APP_ID'));

        if (!is_array($roles)) {
            $roles = explode(",", $roles);
        }

        $roles   = array_map('trim', $roles);
        $roles[] = 'root';  //* root role is always available

        if (in_array('_self', $roles, true) && Caronte::getRouteUser() === $user->uri_user) {
            return true;
        }

        $roles_collection = collect($user->roles);

        return $roles_collection->contains(
            fn($user_role) => in_array($user_role->name, $roles, true) && ($app_id === ($user_role->uri_application ?? $user_role->app_id))
        );
    }
}
