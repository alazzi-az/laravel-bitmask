<?php

namespace Workbench\App\Models;

use Alazziaz\LaravelBitmask\Casts\BitmaskCast;
use Alazziaz\LaravelBitmask\Casts\EnumBitmaskCast;
use Alazziaz\LaravelBitmask\Traits\HasBitmask;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Enums\ArchiveDataFlag;
use Workbench\App\Enums\Permissions;
use Workbench\Database\Factories\DummyModelFactory;

class DummyModel extends Model
{
    use HasBitmask, HasFactory;

    protected static function newFactory()
    {
        return new DummyModelFactory;
    }

    protected $table = 'dummy_models';

    protected $fillable = ['permissions', 'flags', 'archive_data_flag'];

    protected $casts = [
        'permissions' => EnumBitmaskCast::class.':'.Permissions::class,
        'flags' => BitmaskCast::class,
        'archive_data_flag' => EnumBitmaskCast::class.':'.ArchiveDataFlag::class,
    ];

    protected array $bitmaskColumns = [
        'archive_data_flag' => ArchiveDataFlag::class,
    ];
}
