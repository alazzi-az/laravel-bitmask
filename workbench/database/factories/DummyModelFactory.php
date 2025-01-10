<?php

namespace Workbench\Database\Factories;

use Alazziaz\LaravelBitmask\Facades\BitmaskFacade;
use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Enums\ArchiveDataFlag;
use Workbench\App\Enums\Permissions;
use Workbench\App\Models\DummyModel;

class DummyModelFactory extends Factory
{
    protected $model = DummyModel::class;

    public function definition()
    {

        $permissions = $this->randomEnum(Permissions::class);

        $flags = $this->faker->numberBetween(0, 255);

        $archiveDataFlags = $this->randomEnumValues(ArchiveDataFlag::class);
        $archive_data_flag = array_sum($archiveDataFlags);

        return [
            'permissions' => BitmaskFacade::enumBitmaskHandler(Permissions::class, ...$permissions)->getValue(),
            'flags' => $flags,
            'archive_data_flag' => BitmaskFacade::bitmaskHandler($archive_data_flag)->getValue(),
        ];
    }

    /**
     * Helper method to randomly select enum values.
     *
     * @param  \BackedEnum  $enumClass
     * @return array<int>
     */
    protected function randomEnumValues(string $enumClass): array
    {

        $selected = $this->randomEnum($enumClass);

        return array_map(fn ($case) => $case->value, $selected);
    }

    /**
     * Helper method to randomly select enum values.
     *
     * @param  \BackedEnum  $enumClass
     * @return array<\BackedEnum>
     */
    protected function randomEnum(string $enumClass): array
    {
        $cases = $enumClass::cases();

        return $this->faker->randomElements($cases, rand(1, count($cases)));
    }
}
