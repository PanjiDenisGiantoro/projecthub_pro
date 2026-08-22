<?php

namespace App\Support;

class RoleLabel
{
    /**
     * Role slug di DB tetap 'customer' (dipakai luas di hasRole/role/assignRole),
     * ini cuma override label yang ditampilkan ke user.
     */
    private static array $overrides = [
        'customer' => 'Client',
    ];

    public static function for(?string $roleName): string
    {
        if (! $roleName) {
            return '';
        }

        return self::$overrides[$roleName] ?? ucfirst($roleName);
    }
}
