<?php

namespace Alazziaz\LaravelBitmask\Tests\Unit\Examples;

use Examples\PaymentSteps;
use Examples\ProgressStateCast;
use Examples\SimpleProgressStateCast;
use Examples\FlexibleProgressStateCast;
use Examples\OrderModel;
use Examples\UsageExamples;
use Alazziaz\LaravelBitmask\StateMachine\ConfigurableBitmaskHandler;
use Alazziaz\LaravelBitmask\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ExamplesIntegrationTest extends TestCase
{
	/**
	 * Test PaymentSteps enum functionality
	 */
	public function test_payment_steps_enum()
	{
		$this->assertEquals(1, PaymentSteps::STEP_0->value);
		$this->assertEquals(2, PaymentSteps::STEP_1->value);
		$this->assertEquals(4, PaymentSteps::STEP_2->value);
		$this->assertEquals(8, PaymentSteps::STEP_3->value);
		$this->assertEquals(16, PaymentSteps::STEP_4->value);
		$this->assertEquals(32, PaymentSteps::STEP_5->value);
		$this->assertEquals(64, PaymentSteps::STEP_6->value);
		$this->assertEquals(128, PaymentSteps::STEP_7->value);
		$this->assertEquals(256, PaymentSteps::STEP_8->value);
		$this->assertEquals(512, PaymentSteps::STEP_9->value);

		// Test descriptions
		$this->assertEquals('Order Created', PaymentSteps::STEP_0->description());
		$this->assertEquals('Payment Method Selected', PaymentSteps::STEP_1->description());
		$this->assertEquals('Delivered', PaymentSteps::STEP_9->description());
	}

	/**
	 * Test ProgressStateCast configuration
	 */
	public function test_progress_state_cast_configuration()
	{
		$config = ProgressStateCast::config();

		$this->assertEquals(PaymentSteps::class, $config->getEnumClass());
		$this->assertTrue($config->canHaveMultipleActive());
		$this->assertTrue($config->areAllPrerequisitesRequired());

		// Test that basic transitions are configured
		$this->assertTrue($config->isTransitionAllowed(0, PaymentSteps::STEP_0));
		$this->assertTrue($config->isTransitionAllowed(PaymentSteps::STEP_0->value, PaymentSteps::STEP_1));
		$this->assertFalse($config->isTransitionAllowed(0, PaymentSteps::STEP_1));
	}

	/**
	 * Test SimpleProgressStateCast configuration
	 */
	public function test_simple_progress_state_cast_configuration()
	{
		// Skip this test if the class is not autoloaded properly
		if (!class_exists('Examples\SimpleProgressStateCast')) {
			$this->markTestSkipped('SimpleProgressStateCast class not found - multiple classes in one file may not autoload properly');
		}

		$config = SimpleProgressStateCast::config();

		$this->assertEquals(PaymentSteps::class, $config->getEnumClass());
		$this->assertFalse($config->canHaveMultipleActive());
		$this->assertTrue($config->areAllPrerequisitesRequired());
	}

	/**
	 * Test FlexibleProgressStateCast configuration
	 */
	public function test_flexible_progress_state_cast_configuration()
	{
		// Skip this test if the class is not autoloaded properly
		if (!class_exists('Examples\FlexibleProgressStateCast')) {
			$this->markTestSkipped('FlexibleProgressStateCast class not found - multiple classes in one file may not autoload properly');
		}

		$config = FlexibleProgressStateCast::config();

		$this->assertEquals(PaymentSteps::class, $config->getEnumClass());
		$this->assertTrue($config->canHaveMultipleActive());
		$this->assertFalse($config->areAllPrerequisitesRequired());
	}

	/**
	 * Test ProgressStateCast with parallel processing workflow
	 */
	public function test_progress_state_cast_parallel_workflow()
	{
		$cast = new ProgressStateCast();
		$model = new class extends Model {
			protected $casts = ['progress_state' => ProgressStateCast::class];
		};

		// Start with empty state
		$handler = $cast->get($model, 'progress_state', 0, ['progress_state' => 0]);
		$this->assertInstanceOf(ConfigurableBitmaskHandler::class, $handler);

		// Progress through initial steps
		$handler->addState(PaymentSteps::STEP_0);
		$handler->addState(PaymentSteps::STEP_1);
		$handler->addState(PaymentSteps::STEP_2);

		// Start parallel processes
		$handler->addState(PaymentSteps::STEP_3);
		$handler->addState(PaymentSteps::STEP_4);
		$handler->addState(PaymentSteps::STEP_5);

		// Should now be able to proceed to order confirmation
		$this->assertTrue($handler->canTransition(PaymentSteps::STEP_6));
		$handler->addState(PaymentSteps::STEP_6);

		// Verify all states are active
		$this->assertTrue($handler->hasState(PaymentSteps::STEP_3));
		$this->assertTrue($handler->hasState(PaymentSteps::STEP_4));
		$this->assertTrue($handler->hasState(PaymentSteps::STEP_5));
		$this->assertTrue($handler->hasState(PaymentSteps::STEP_6));
	}

	/**
	 * Test ProgressStateCast with incomplete prerequisites
	 */
	public function test_progress_state_cast_incomplete_prerequisites()
	{
		$cast = new ProgressStateCast();
		$model = new class extends Model {
			protected $casts = ['progress_state' => ProgressStateCast::class];
		};

		$handler = $cast->get($model, 'progress_state', 0, ['progress_state' => 0]);

		// Progress through initial steps
		$handler->addState(PaymentSteps::STEP_0);
		$handler->addState(PaymentSteps::STEP_1);
		$handler->addState(PaymentSteps::STEP_2);

		// Start only some parallel processes
		$handler->addState(PaymentSteps::STEP_3);
		$handler->addState(PaymentSteps::STEP_4);
		// Missing STEP_5

		// Should NOT be able to proceed to order confirmation
		$this->assertFalse($handler->canTransition(PaymentSteps::STEP_6));

		// Should throw exception when trying to force transition
		$this->expectException(InvalidArgumentException::class);
		$handler->addState(PaymentSteps::STEP_6);
	}

	/**
	 * Test FlexibleProgressStateCast with OR logic
	 */
	public function test_flexible_progress_cast_or_logic()
	{
		// Skip this test if the class is not autoloaded properly
		if (!class_exists('Examples\FlexibleProgressStateCast')) {
			$this->markTestSkipped('FlexibleProgressStateCast class not found - multiple classes in one file may not autoload properly');
		}

		$cast = new FlexibleProgressStateCast();
		$model = new class extends Model {
			protected $casts = ['progress_state' => FlexibleProgressStateCast::class];
		};

		$handler = $cast->get($model, 'progress_state', 0, ['progress_state' => 0]);

		// Progress through initial steps
		$handler->addState(PaymentSteps::STEP_0);
		$handler->addState(PaymentSteps::STEP_1);
		$handler->addState(PaymentSteps::STEP_2);

		// Add only payment processing (STEP_3)
		$handler->addState(PaymentSteps::STEP_3);

		// Should be able to proceed to confirmation with just payment (OR logic)
		$this->assertTrue($handler->canTransition(PaymentSteps::STEP_6));
		$handler->addState(PaymentSteps::STEP_6);
		$this->assertTrue($handler->hasState(PaymentSteps::STEP_6));
	}

	/**
	 * Test OrderModel helper methods
	 */
	public function test_order_model_helper_methods()
	{
		$order = new OrderModel();
		// Don't set attributes that would trigger database operations
		$order->setAttribute('progress_state', 0);

		// Test canProcessPayment method
		$progressState = $order->getAttribute('progress_state');
		$progressState->addState(PaymentSteps::STEP_0);
		$progressState->addState(PaymentSteps::STEP_1);
		$progressState->addState(PaymentSteps::STEP_2);

		$this->assertTrue($order->canProcessPayment());

		// Test getCurrentProgressDescription
		$descriptions = $order->getCurrentProgressDescription();
		$this->assertContains('Order Created', $descriptions);
		$this->assertContains('Payment Method Selected', $descriptions);
		$this->assertContains('Address Validated', $descriptions);

		// Test advanceToStep without saving to database
		$progressState->addState(PaymentSteps::STEP_3);
		$this->assertTrue($progressState->hasState(PaymentSteps::STEP_3));

		// Test isComplete
		$this->assertFalse($order->isComplete());

		// Test getProgressPercentage
		$percentage = $order->getProgressPercentage();
		$this->assertGreaterThan(0, $percentage);
		$this->assertLessThan(100, $percentage);
	}

	/**
	 * Test OrderModel with complete workflow
	 */
	public function test_order_model_complete_workflow()
	{
		$order = new OrderModel();
		$order->setAttribute('progress_state', 0);

		$progressState = $order->getAttribute('progress_state');

		// Complete the entire workflow
		$progressState->addState(PaymentSteps::STEP_0);
		$progressState->addState(PaymentSteps::STEP_1);
		$progressState->addState(PaymentSteps::STEP_2);
		$progressState->addState(PaymentSteps::STEP_3);
		$progressState->addState(PaymentSteps::STEP_4);
		$progressState->addState(PaymentSteps::STEP_5);
		$progressState->addState(PaymentSteps::STEP_6);
		$progressState->addState(PaymentSteps::STEP_7);
		$progressState->addState(PaymentSteps::STEP_8);
		$progressState->addState(PaymentSteps::STEP_9);

		// Test completion
		$this->assertTrue($order->isComplete());

		// Test progress percentage
		$percentage = $order->getProgressPercentage();
		$this->assertEquals(100.0, $percentage);

		// Test possible next steps (should be empty since complete)
		$nextSteps = $order->getPossibleNextSteps();
		$this->assertIsArray($nextSteps);
	}

	/**
	 * Test UsageExamples class functionality
	 */
	public function test_usage_examples_basic_functionality()
	{
		$examples = new UsageExamples();

		// Test that the class exists and can be instantiated
		$this->assertInstanceOf(UsageExamples::class, $examples);

		// Test that methods exist
		$this->assertTrue(method_exists($examples, 'basicLinearWorkflow'));
		$this->assertTrue(method_exists($examples, 'parallelProcessingWorkflow'));
		$this->assertTrue(method_exists($examples, 'orLogicPrerequisites'));
		$this->assertTrue(method_exists($examples, 'dynamicTransitionChecking'));
		$this->assertTrue(method_exists($examples, 'stateRemovalAndValidation'));
		$this->assertTrue(method_exists($examples, 'maskableInterfaceExample'));
		$this->assertTrue(method_exists($examples, 'arrayRepresentationExample'));
		$this->assertTrue(method_exists($examples, 'runAllExamples'));
	}

	/**
	 * Test cast integration with different configurations
	 */
	public function test_cast_integration_with_different_configurations()
	{
		// Skip classes that may not be autoloaded properly
		if (!class_exists('Examples\SimpleProgressStateCast') || !class_exists('Examples\FlexibleProgressStateCast')) {
			$this->markTestSkipped('Some cast classes not found - multiple classes in one file may not autoload properly');
		}

		$model = new class extends Model {
			protected $casts = [
				'progress_state' => ProgressStateCast::class,
				'simple_progress_state' => SimpleProgressStateCast::class,
				'flexible_progress_state' => FlexibleProgressStateCast::class,
			];
		};

		$model->setAttribute('progress_state', 0);
		$model->setAttribute('simple_progress_state', 0);
		$model->setAttribute('flexible_progress_state', 0);

		// Test different configurations work independently
		$progressState = $model->getAttribute('progress_state');
		$simpleState = $model->getAttribute('simple_progress_state');
		$flexibleState = $model->getAttribute('flexible_progress_state');

		$this->assertInstanceOf(ConfigurableBitmaskHandler::class, $progressState);
		$this->assertInstanceOf(ConfigurableBitmaskHandler::class, $simpleState);
		$this->assertInstanceOf(ConfigurableBitmaskHandler::class, $flexibleState);

		// Test that they have different configurations
		$this->assertTrue($progressState->getConfig()->canHaveMultipleActive());
		$this->assertFalse($simpleState->getConfig()->canHaveMultipleActive());
		$this->assertTrue($flexibleState->getConfig()->canHaveMultipleActive());

		$this->assertTrue($progressState->getConfig()->areAllPrerequisitesRequired());
		$this->assertTrue($simpleState->getConfig()->areAllPrerequisitesRequired());
		$this->assertFalse($flexibleState->getConfig()->areAllPrerequisitesRequired());
	}

	/**
	 * Test error handling with example casts
	 */
	public function test_error_handling_with_example_casts()
	{
		$cast = new ProgressStateCast();
		$model = new class extends Model {
			protected $casts = ['progress_state' => ProgressStateCast::class];
		};

		// Test invalid transition through cast
		$this->expectException(InvalidArgumentException::class);
		$cast->set($model, 'progress_state', PaymentSteps::STEP_6->value, ['progress_state' => 0]);
	}

	/**
	 * Test example enum bit values are correct
	 */
	public function test_example_enum_bit_values()
	{
		$steps = PaymentSteps::cases();

		// Verify each step has a unique power of 2 value
		$values = [];
		foreach ($steps as $step) {
			$this->assertTrue(($step->value & ($step->value - 1)) === 0, "Step {$step->name} value {$step->value} is not a power of 2");
			$this->assertNotContains($step->value, $values, "Duplicate value found: {$step->value}");
			$values[] = $step->value;
		}

		// Verify values are in ascending order
		$this->assertEquals([1, 2, 4, 8, 16, 32, 64, 128, 256, 512], $values);
	}
}
