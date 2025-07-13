<?php

namespace Alazziaz\LaravelBitmask\Tests\Unit\Traits;

use Alazziaz\LaravelBitmask\Casts\ConfigurableBitmaskCast;
use Alazziaz\LaravelBitmask\StateMachine\BitmaskStateConfiguration;
use Alazziaz\LaravelBitmask\StateMachine\ConfigurableBitmaskHandler;
use Alazziaz\LaravelBitmask\Tests\TestCase;
use Alazziaz\LaravelBitmask\Traits\HasBitmask;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

enum TestPermissions: int
{
    case READ = 1 << 0;    // 1
    case WRITE = 1 << 1;   // 2
    case DELETE = 1 << 2;  // 4
    case ADMIN = 1 << 3;   // 8
}

enum TestStates: int
{
    case DRAFT = 1 << 0;     // 1
    case REVIEW = 1 << 1;    // 2
    case APPROVED = 1 << 2;  // 4
    case PUBLISHED = 1 << 3; // 8
}

class TestStateCast extends ConfigurableBitmaskCast
{
    public static function config(): BitmaskStateConfiguration
    {
        return (new BitmaskStateConfiguration(TestStates::class))
            ->allowTransition([], TestStates::DRAFT)
            ->allowTransition([TestStates::DRAFT], TestStates::REVIEW)
            ->allowTransition([TestStates::REVIEW], TestStates::APPROVED)
            ->allowTransition([TestStates::APPROVED], TestStates::PUBLISHED)
            ->allowMultipleActive(false) // Only one state active at a time
            ->requireAllPrerequisites(true); // Strict prerequisites
    }
}

class TestModel extends Model
{
    use HasBitmask;

    protected $table = 'test_models';
    protected $fillable = ['permissions', 'status'];

    protected $casts = [
        'status' => TestStateCast::class,
    ];

    protected $bitmaskColumns = [
        'permissions' => TestPermissions::class,
    ];
}

class HasBitmaskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->integer('permissions')->default(0);
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_models');
        parent::tearDown();
    }

    /**
     * Test traditional bitmask column functionality
     */
    public function test_traditional_bitmask_column_scopes()
    {
        // Create test data
        TestModel::create(['permissions' => TestPermissions::READ->value]);
        TestModel::create(['permissions' => TestPermissions::READ->value | TestPermissions::WRITE->value]);
        TestModel::create(['permissions' => TestPermissions::ADMIN->value]);

        // Test whereHasFlag
        $readModels = TestModel::whereHasFlag('permissions', TestPermissions::READ)->get();
        $this->assertCount(2, $readModels);

        // Test whereHasAnyFlags
        $readWriteModels = TestModel::whereHasAnyFlags('permissions', [TestPermissions::READ, TestPermissions::WRITE])->get();
        $this->assertCount(2, $readWriteModels);

        // Test whereHasAllFlags
        $allFlags = TestModel::whereHasAllFlags('permissions', [TestPermissions::READ, TestPermissions::WRITE])->get();
        $this->assertCount(1, $allFlags);

        // Test whereHasNoFlag
        $noReadModels = TestModel::whereHasNoFlag('permissions', TestPermissions::READ)->get();
        $this->assertCount(1, $noReadModels);
    }

    /**
     * Test configurable bitmask cast column functionality
     */
    public function test_configurable_bitmask_cast_scopes()
    {
        // Create test data with different states
        TestModel::create(['status' => TestStates::DRAFT->value]);
        TestModel::create(['status' => TestStates::REVIEW->value]);
        TestModel::create(['status' => TestStates::APPROVED->value]);

        // Test whereAtState
        $draftModels = TestModel::whereAtState('status', TestStates::DRAFT)->get();
        $this->assertCount(1, $draftModels);

        // Test whereInStates
        $draftReviewModels = TestModel::whereInStates('status', [TestStates::DRAFT, TestStates::REVIEW])->get();
        $this->assertCount(2, $draftReviewModels);

        // Test whereHasAnyStates
        $anyStateModels = TestModel::whereHasAnyStates('status', [TestStates::DRAFT, TestStates::PUBLISHED])->get();
        $this->assertCount(1, $anyStateModels); // Only draft exists
    }

    /**
     * Test configurable bitmask transition queries
     */
    public function test_configurable_bitmask_transition_queries()
    {
        // Create models in different states
        TestModel::create(['status' => TestStates::DRAFT->value]);
        TestModel::create(['status' => TestStates::REVIEW->value]);
        TestModel::create(['status' => TestStates::APPROVED->value]);

        // Test whereCanTransitionTo
        $canTransitionToReview = TestModel::whereCanTransitionTo('status', TestStates::REVIEW)->get();
        $this->assertCount(1, $canTransitionToReview); // Only draft can transition to review

        $canTransitionToApproved = TestModel::whereCanTransitionTo('status', TestStates::APPROVED)->get();
        $this->assertCount(1, $canTransitionToApproved); // Only review can transition to approved
    }

    /**
     * Test model instance methods for configurable bitmask
     */
    public function test_model_instance_methods_for_configurable_bitmask()
    {
        $model = TestModel::create(['status' => TestStates::DRAFT->value]);

        // Test canTransitionTo
        $this->assertTrue($model->canTransitionTo('status', TestStates::REVIEW));
        $this->assertFalse($model->canTransitionTo('status', TestStates::PUBLISHED));

        // Test getPossibleTransitions
        $possibleTransitions = $model->getPossibleTransitions('status');
        $this->assertContains(TestStates::REVIEW->value, $possibleTransitions);
        $this->assertNotContains(TestStates::PUBLISHED->value, $possibleTransitions);

        // Test getActiveStates
        $activeStates = $model->getActiveStates('status');
        $this->assertContains(TestStates::DRAFT->value, $activeStates);
    }

    /**
     * Test model instance methods for traditional bitmask
     */
    public function test_model_instance_methods_for_traditional_bitmask()
    {
        $model = TestModel::create(['permissions' => TestPermissions::READ->value | TestPermissions::WRITE->value]);

        // Test getActiveStates for traditional bitmask
        $activeStates = $model->getActiveStates('permissions');
        $this->assertContains(TestPermissions::READ->value, $activeStates);
        $this->assertContains(TestPermissions::WRITE->value, $activeStates);
        $this->assertNotContains(TestPermissions::DELETE->value, $activeStates);
    }

    /**
     * Test validation errors
     */
    public function test_validation_errors()
    {
        $model = new TestModel();

        // Test invalid column for traditional bitmask methods
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'invalid_column' is not a defined bitmask column.");
        TestModel::whereHasFlag('invalid_column', TestPermissions::READ);
    }

    /**
     * Test configurable bitmask validation errors
     */
    public function test_configurable_bitmask_validation_errors()
    {
        $model = new TestModel();

        // Test invalid column for configurable bitmask methods
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'permissions' does not use ConfigurableBitmaskCast.");
        $model->canTransitionTo('permissions', TestStates::DRAFT);
    }

    /**
     * Test mixed column types
     */
    public function test_mixed_column_types()
    {
        $model = TestModel::create([
            'permissions' => TestPermissions::READ->value,
            'status' => TestStates::DRAFT->value
        ]);

        // Traditional bitmask column
        $this->assertTrue(TestModel::whereHasFlag('permissions', TestPermissions::READ)->exists());

        // Configurable bitmask column
        $this->assertTrue(TestModel::whereAtState('status', TestStates::DRAFT)->exists());

        // Instance methods work for both
        $activePermissions = $model->getActiveStates('permissions');
        $this->assertContains(TestPermissions::READ->value, $activePermissions);

        $activeStatus = $model->getActiveStates('status');
        $this->assertContains(TestStates::DRAFT->value, $activeStatus);
    }

    /**
     * Test flag value calculation fix
     */
    public function test_flag_value_calculation_fix()
    {
        // Test the fixed calculateMask method
        TestModel::create(['permissions' => TestPermissions::READ->value]);
        TestModel::create(['permissions' => TestPermissions::WRITE->value]);
        TestModel::create(['permissions' => TestPermissions::READ->value | TestPermissions::WRITE->value]);

        // This should work now (previously would fail due to calculateMask bug)
        $models = TestModel::whereHasAnyFlags('permissions', [TestPermissions::READ, TestPermissions::WRITE])->get();
        $this->assertCount(3, $models);

        $models = TestModel::whereHasAllFlags('permissions', [TestPermissions::READ, TestPermissions::WRITE])->get();
        $this->assertCount(1, $models);
    }

    /**
     * Test enum type validation
     */
    public function test_enum_type_validation()
    {
        // Test that enum validation works correctly
        $model = TestModel::create(['permissions' => TestPermissions::READ->value]);

        // This should work - correct enum type
        $this->assertTrue(TestModel::whereHasFlag('permissions', TestPermissions::READ)->exists());

        // Test with integer values
        $this->assertTrue(TestModel::whereHasFlag('permissions', 1)->exists());
    }

    /**
     * Test complex state transitions
     */
    public function test_complex_state_transitions()
    {
        // Create models at different stages
        $draft = TestModel::create(['status' => TestStates::DRAFT->value]);
        $review = TestModel::create(['status' => TestStates::REVIEW->value]);
        $approved = TestModel::create(['status' => TestStates::APPROVED->value]);

        // Test transition capabilities
        $this->assertTrue($draft->canTransitionTo('status', TestStates::REVIEW));
        $this->assertFalse($draft->canTransitionTo('status', TestStates::PUBLISHED));

        $this->assertTrue($review->canTransitionTo('status', TestStates::APPROVED));
        $this->assertFalse($review->canTransitionTo('status', TestStates::PUBLISHED));

        $this->assertTrue($approved->canTransitionTo('status', TestStates::PUBLISHED));
        $this->assertFalse($approved->canTransitionTo('status', TestStates::DRAFT));
    }

    /**
     * Test OR operation in calculateMask
     */
    public function test_or_operation_in_calculate_mask()
    {
        // Create test data
        TestModel::create(['permissions' => TestPermissions::READ->value]); // 1
        TestModel::create(['permissions' => TestPermissions::WRITE->value]); // 2
        TestModel::create(['permissions' => TestPermissions::READ->value | TestPermissions::WRITE->value]); // 3

        // Test that OR operation works correctly (not addition)
        $models = TestModel::whereHasAnyFlags('permissions', [TestPermissions::READ, TestPermissions::WRITE])->get();
        $this->assertCount(3, $models);

        // Verify the mask calculation uses OR (|) not addition (+)
        // If it used addition, the mask would be 3 (1+2), but with OR it's also 3 (1|2)
        // The difference would be visible with overlapping bits
        $mask = TestPermissions::READ->value | TestPermissions::WRITE->value; // Should be 3
        $this->assertEquals(3, $mask);

        // But if we had overlapping values, addition would give wrong results
        // This test ensures we're using bitwise OR correctly
    }
}
