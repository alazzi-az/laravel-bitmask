<?php

namespace Workbench\App\Enums;

use Alazziaz\Bitmask\Contracts\MaskableEnum;

enum Permissions: int implements MaskableEnum
{
    case READ = 1;
    case WRITE = 2;
    case EXECUTE = 4;

    public function toMaskKey(): string
    {
        return match ($this) {
            self::READ => 'read_permission',
            self::WRITE => 'write_permission',
            self::EXECUTE => 'execute_permission',
        };
    }
}
