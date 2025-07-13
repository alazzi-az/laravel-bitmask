<?php

namespace Alazziaz\LaravelBitmask\Tests\Unit\Casts;

use Alazziaz\LaravelBitmask\Casts\ConfigurableBitmaskCast;
use Alazziaz\LaravelBitmask\StateMachine\BitmaskStateConfiguration;
use Alazziaz\LaravelBitmask\StateMachine\ConfigurableBitmaskHandler;
use Alazziaz\LaravelBitmask\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

enum TestSteps: int
{
	case STEP_0 = 1 << 0;  // 1
	case STEP_1 = 1 << 1;  // 2
	case STEP_2 = 1 << 2;  // 4
	case STEP_3 = 1 << 3;  // 8
	case STEP_4 = 1 << 4;  // 16
	case STEP_5 = 1 << 5;  // 32
}

class TestProgressCast extends ConfigurableBitmaskCast
{
	public static function config(): BitmaskStateConfiguration
	{
		return (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1)
			->allowTransition([TestSteps::STEP_1], TestSteps::STEP_2)
			->allowTransition([TestSteps::STEP_2], TestSteps::STEP_3)
			->allowMultipleActive(true);
	}
}

class TestParallelCast extends ConfigurableBitmaskCast
{
	public static function config(): BitmaskStateConfiguration
	{
		return (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1)
			->allowTransition([TestSteps::STEP_1], TestSteps::STEP_2)
			// Parallel processes
			->allowTransition([TestSteps::STEP_2], TestSteps::STEP_3)
			->allowTransition([TestSteps::STEP_2], TestSteps::STEP_4)
			->allowTransition([TestSteps::STEP_2], TestSteps::STEP_5)
			->allowMultipleActive(true)
			->requireAllPrerequisites(true);
	}
}

class TestOrLogicCast extends ConfigurableBitmaskCast
{
	public static function config(): BitmaskStateConfiguration
	{
		return (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_2)
			->allowTransition([TestSteps::STEP_1, TestSteps::STEP_2], TestSteps::STEP_3)
			->allowMultipleActive(true)
			->requireAllPrerequisites(false); // OR logic
	}
}

class TestModel extends Model
{
	protected $fillable = ['progress_state', 'parallel_state', 'or_logic_state'];

	protected $casts = [
		'progress_state' => TestProgressCast::class,
		'parallel_state' => TestParallelCast::class,
		'or_logic_state' => TestOrLogicCast::class,
	];
}

class ConfigurableBitmaskCastTest extends TestCase
{
	/**
	 * Test basic cast functionality
	 */
	public function test_cast_returns_configurable_bitmask_handler()
	{
		$cast = new TestProgressCast();
		$model = new TestModel();

		$result = $cast->get($model, 'progress_state', 5, ['progress_state' => 5]);

		$this->assertInstanceOf(ConfigurableBitmaskHandler::class, $result);
		$this->assertEquals(5, $result->getValue());
	}

	/**
	 * Test cast returns null for null values
	 */
	public function test_cast_returns_null_for_null_values()
	{
		$cast = new TestProgressCast();
		$model = new TestModel();

		$result = $cast->get($model, 'progress_state', null, ['progress_state' => null]);

		$this->assertNull($result);
	}

	/**
	 * Test cast throws exception for non-integer values
	 */
	public function test_cast_throws_exception_for_non_integer_values()
	{
		$cast = new TestProgressCast();
		$model = new TestModel();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Bitmask value must be an integer.');

		$cast->get($model, 'progress_state', 'invalid', ['progress_state' => 'invalid']);
	}

	/**
	 * Test setting integer values
	 */
	public function test_set_integer_values()
	{
		$cast = new TestProgressCast();
		$model = new TestModel();

		// Valid transition
		$result = $cast->set($model, 'progress_state', 1, ['progress_state' => 0]);
		$this->assertEquals(1, $result);

		// Same value should be allowed
		$result = $cast->set($model, 'progress_state', 1, ['progress_state' => 1]);
		$this->assertEquals(1, $result);
	}

	/**
	 * Test setting invalid integer values throws exception
	 */
	public function test_set_invalid_integer_values_throws_exception()
	{
		$cast = new TestProgressCast();
		$model = new TestModel();
		$model->exists = true; // Simulate persisted model

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Transition from 0 to 8 is not allowed by the state machine configuration.');

		// Try to skip from 0 to 8 (STEP_3) without proper prerequisites
		$cast->set($model, 'progress_state', 8, ['progress_state' => 0]);
	}

	/**
	 * Test setting ConfigurableBitmaskHandler values
	 */
	public function test_set_configurable_bitmask_handler_values()
	{
		$cast = new TestProgressCast();
		$model = new TestModel();

		$config = TestProgressCast::config();
		$handler = new ConfigurableBitmaskHandler(5, $config);

		$result = $cast->set($model, 'progress_state', $handler, ['progress_state' => 0]);
		$this->assertEquals(5, $result);
	}

	/**
	 * Test setting enum values
	 */
	public function test_set_enum_values()
	{
		$cast = new TestProgressCast();
		$model = new TestModel();

		// Valid transition
		$result = $cast->set($model, 'progress_state', TestSteps::STEP_0, ['progress_state' => 0]);
		$this->assertEquals(TestSteps::STEP_0->value, $result);
	}

	/**
	 * Test setting invalid enum values throws exception
	 */
	public function test_set_invalid_enum_values_throws_exception()
	{
		$cast = new TestProgressCast();
		$model = new TestModel();
		$model->exists = true; // Simulate persisted model

		$this->expectException(InvalidArgumentException::class);

		// Try to skip to STEP_3 without proper prerequisites
		$cast->set($model, 'progress_state', TestSteps::STEP_3, ['progress_state' => 0]);
	}

	/**
	 * Test setting null values
	 */
	public function test_set_null_values()
	{
		$cast = new TestProgressCast();
		$model = new TestModel();

		$result = $cast->set($model, 'progress_state', null, ['progress_state' => 0]);
		$this->assertNull($result);
	}

	/**
	 * Test configuration caching
	 */
	public function test_configuration_caching()
	{
		$config1 = TestProgressCast::config();
		$config2 = TestProgressCast::config();

		// Both should be instances of the same class
		$this->assertInstanceOf(BitmaskStateConfiguration::class, $config1);
		$this->assertInstanceOf(BitmaskStateConfiguration::class, $config2);
	}

	/**
	 * Test parallel processing cast
	 */
	public function test_parallel_processing_cast()
	{
		$cast = new TestParallelCast();
		$model = new TestModel();

		// Test valid sequential progression
		$result = $cast->set($model, 'parallel_state', 1, ['parallel_state' => 0]); // STEP_0
		$this->assertEquals(1, $result);

		$result = $cast->set($model, 'parallel_state', 2, ['parallel_state' => 1]); // STEP_1 only
		$this->assertEquals(2, $result);

		$result = $cast->set($model, 'parallel_state', 4, ['parallel_state' => 2]); // STEP_2 only
		$this->assertEquals(4, $result);
	}

	/**
	 * Test OR logic cast
	 */
	public function test_or_logic_cast()
	{
		$cast = new TestOrLogicCast();
		$model = new TestModel();

		// Get handler to test OR logic
		$handler = $cast->get($model, 'or_logic_state', 1, ['or_logic_state' => 1]); // STEP_0
		$this->assertInstanceOf(ConfigurableBitmaskHandler::class, $handler);

		// Add STEP_1 (one of the prerequisites for STEP_3)
		$handler->addState(TestSteps::STEP_1);

		// Should be able to transition to STEP_3 with just STEP_1 (OR logic)
		$this->assertTrue($handler->canTransition(TestSteps::STEP_3));
		$handler->addState(TestSteps::STEP_3);
		$this->assertTrue($handler->hasState(TestSteps::STEP_3));
	}

	/**
	 * Test cast with model integration
	 */
	public function test_cast_with_model_integration()
	{
		$model = new TestModel();
		$model->setAttribute('progress_state', 0);

		// Should be able to get the cast value
		$progressState = $model->getAttribute('progress_state');
		$this->assertInstanceOf(ConfigurableBitmaskHandler::class, $progressState);
		$this->assertEquals(0, $progressState->getValue());

		// Should be able to modify the state
		$progressState->addState(TestSteps::STEP_0);
		$this->assertTrue($progressState->hasState(TestSteps::STEP_0));

		// Should be able to check transitions
		$this->assertTrue($progressState->canTransition(TestSteps::STEP_1));
		$this->assertFalse($progressState->canTransition(TestSteps::STEP_3));
	}

	/**
	 * Test cast configuration validation
	 */
	public function test_cast_configuration_validation()
	{
		$config = TestProgressCast::config();

		$this->assertInstanceOf(BitmaskStateConfiguration::class, $config);
		$this->assertEquals(TestSteps::class, $config->getEnumClass());
		$this->assertTrue($config->canHaveMultipleActive());
		$this->assertTrue($config->areAllPrerequisitesRequired());
	}

	/**
	 * Test different cast configurations
	 */
	public function test_different_cast_configurations()
	{
		// Test parallel processing configuration
		$parallelConfig = TestParallelCast::config();
		$this->assertTrue($parallelConfig->canHaveMultipleActive());
		$this->assertTrue($parallelConfig->areAllPrerequisitesRequired());

		// Test OR logic configuration
		$orConfig = TestOrLogicCast::config();
		$this->assertTrue($orConfig->canHaveMultipleActive());
		$this->assertFalse($orConfig->areAllPrerequisitesRequired());
	}

	/**
	 * Test cast error handling
	 */
	public function test_cast_error_handling()
	{
		$cast = new TestProgressCast();
		$model = new TestModel();
		$model->exists = true; // Simulate persisted model

		// Test with invalid transition - should throw exception
		// Try to skip from STEP_0 (value 1) to STEP_2 (value 4) without going through STEP_1
		$this->expectException(InvalidArgumentException::class);
		$cast->set($model, 'progress_state', 4, ['progress_state' => 1]); // Skip to STEP_2
	}

	/**
	 * Test cast with complex workflow
	 */
	public function test_cast_with_complex_workflow()
	{
		$model = new TestModel();
		$model->setAttribute('parallel_state', 0);

		// Get the handler
		$parallelState = $model->getAttribute('parallel_state');
		$this->assertInstanceOf(ConfigurableBitmaskHandler::class, $parallelState);

		// Progress through the workflow
		$parallelState->addState(TestSteps::STEP_0);
		$parallelState->addState(TestSteps::STEP_1);
		$parallelState->addState(TestSteps::STEP_2);

		// Now should be able to add parallel processes
		$parallelState->addState(TestSteps::STEP_3);
		$parallelState->addState(TestSteps::STEP_4);
		$parallelState->addState(TestSteps::STEP_5);

		// Should have all states active
		$this->assertTrue($parallelState->hasState(TestSteps::STEP_3));
		$this->assertTrue($parallelState->hasState(TestSteps::STEP_4));
		$this->assertTrue($parallelState->hasState(TestSteps::STEP_5));

		// Verify multiple states are active
		$activeStates = $parallelState->getActiveStates();
		$this->assertGreaterThan(3, count($activeStates));
	}
}
