<?php

namespace App\Support;

class RoleLabel
{
    /**
     * Override label tampilan per role slug, kalau ucfirst() saja tidak cukup
     * (mis. role custom masa depan yang butuh nama tampilan khusus).
     */
    private static array $overrides = [];

    public static function for(?string $roleName): string
    {
        if (! $roleName) {
            return '';
        }

        return self::$overrides[$roleName] ?? ucfirst($roleName);
    }
}
