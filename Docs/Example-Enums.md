
---

## Example Enums

To effectively use the `HasBitmask` trait and related classes, define your enums as `BackedEnum` types. Here's an example:

```php
<?php

namespace App\Enums;

enum ArchiveDataFlag: int
{
    case COUNTRIES      = 1 << 0; // 1
    case CITIES         = 1 << 1; // 2
    case FACILITIES     = 1 << 2; // 4
    case NATIONALITIES  = 1 << 3; // 8
    case REGIONS        = 1 << 4; // 16
    case LOCATIONS      = 1 << 5; // 32
}
```

## Advanced Usage

### Customizing the Bitmask Column Name

If your bitmask column has a different name or requires special handling, you can override the `getBitmaskColumns` method in your model or adjust the `$bitmaskColumns` property accordingly.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Alazziaz\LaravelBitmask\Traits\HasBitmask;
use App\Enums\CustomFlagEnum;

class CustomModel extends Model
{
    use HasBitmask;

    protected array $bitmaskColumns = [
        'custom_flag_column' => CustomFlagEnum::class,
    ];

    // Optionally override methods if needed
}
```

### Dynamic Bitmask Column Detection

Instead of explicitly passing the column name each time, the trait can dynamically determine the bitmask column based on the Enum or model configuration.

```php
// Example usage without specifying the column
$archivesWithHotels = Archive::whereHasFlag('archive_data_flag', ArchiveDataFlag::HOTELS)->get();
```

### Caching Frequently Used Queries

For performance optimization, especially with large datasets, consider caching frequently executed bitmask queries.

```php
use Illuminate\Support\Facades\Cache;
use App\Models\Archive;
use App\Enums\ArchiveDataFlag;

$archivesWithHotels = Cache::remember('archives_with_hotels', now()->addMinutes(60), function () {
    return Archive::whereHasFlag('archive_data_flag', ArchiveDataFlag::HOTELS)->get();
});
```

### Indexing Bitmask Columns

If you frequently query bitmask columns, indexing them can improve query performance. However, be cautious as bitmask operations might not always benefit significantly from indexing, depending on the database and query patterns.

**Example Migration: Adding Index to Bitmask Column**

```php
public function up()
{
    Schema::table('archives', function (Blueprint $table) {
        $table->index('archive_data_flag');
        $table->index('user_permissions_flag');
    });
}
```

### Unit Testing the HasBitmask Trait

Ensure that the `HasBitmask` trait works as expected by writing unit tests. This promotes reliability and facilitates future enhancements.

**Example Test Case: Testing `whereHasFlag` Scope**

```php
public function test_where_has_flag_scope()
{
    // Arrange: Create archives with different flags
    Archive::factory()->create(['archive_data_flag' => ArchiveDataFlag::HOTELS->value]);
    Archive::factory()->create(['archive_data_flag' => ArchiveDataFlag::CITIES->value]);
    Archive::factory()->create(['archive_data_flag' => ArchiveDataFlag::HOTELS->value | ArchiveDataFlag::CITIES->value]);

    // Act: Query archives with HOTELS flag
    $archivesWithHotels = Archive::whereHasFlag('archive_data_flag', ArchiveDataFlag::HOTELS)->get();

    // Assert: Should retrieve two records
    $this->assertCount(2, $archivesWithHotels);
}
```

## Conclusion

By integrating the `HasBitmask` trait and utilizing the provided casting classes and handlers, you can efficiently manage and query bitmask fields within your Laravel applications. This package not only simplifies bitmask operations but also ensures your code remains clean, maintainable, and aligned with Laravel's best practices.

For further customization or features, feel free to explore the source code or contact support.

---

If you have any further questions or need additional assistance with integrating the `laravel-bitmask` package, feel free to ask!
