<?php

namespace App\Support;

final class SystemRoles
{
    public const ADMIN = 'admin';
    public const MEMBER = 'member';
    public const CLIENT = 'client';

    /** System roles: not renamable/deletable via the Role CRUD UI. */
    public const ALL = [self::ADMIN, self::MEMBER, self::CLIENT];
}
