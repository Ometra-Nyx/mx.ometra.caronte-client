<?php

namespace Ometra\Caronte\Support;

use InvalidArgumentException;

class ConfiguredPermissions
{
    /**
     * @return array<int, array{permission: string, description: string}>
     */
    public static function all(): array
    {
        $permissions = config('caronte.permissions', []);
        $normalized = [];

        foreach ((array) $permissions as $key => $permission) {
            $entry = static::normalizeEntry($key, $permission);
            $normalized[$entry['permission']] = $entry;
        }

        return array_values($normalized);
    }

    /**
     * @param  int|string  $key
     * @return array{permission: string, description: string}
     */
    private static function normalizeEntry(int|string $key, mixed $permission): array
    {
        if (is_string($permission)) {
            if (is_string($key) && !is_numeric($key)) {
                return [
                    'permission' => static::normalizeName($key),
                    'description' => trim($permission),
                ];
            }

            $name = static::normalizeName($permission);

            return [
                'permission' => $name,
                'description' => ucfirst(str_replace(['_', '-', '.'], ' ', $name)),
            ];
        }

        if (! is_array($permission)) {
            throw new InvalidArgumentException('Each configured permission must be a string or array.');
        }

        return [
            'permission' => static::normalizeName((string) ($permission['permission'] ?? $permission['name'] ?? $key)),
            'description' => trim((string) ($permission['description'] ?? '')),
        ];
    }

    private static function normalizeName(string $name): string
    {
        $normalized = strtolower(trim($name));

        if ($normalized === '') {
            throw new InvalidArgumentException('Configured permission names cannot be empty.');
        }

        if (! preg_match('/^[a-z0-9_.:-]+$/', $normalized)) {
            throw new InvalidArgumentException(sprintf('Configured permission [%s] contains invalid characters.', $name));
        }

        return $normalized;
    }
}
