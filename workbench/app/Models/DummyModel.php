<?php

namespace Workbench\App\Models;

use Alazziaz\LaravelBitmask\Casts\BitmaskCast;
use Alazziaz\LaravelBitmask\Casts\EnumBitmaskCast;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Enums\Permissions;

class DummyModel extends Model
{
    protected $table = 'dummy_models';

    protected $fillable = ['permissions', 'flags'];

    protected $casts = [
        'permissions' => EnumBitmaskCast::class.':'.Permissions::class,
        'flags' => BitmaskCast::class,
    ];
}
