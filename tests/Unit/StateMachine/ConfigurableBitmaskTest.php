<?php

namespace Alazziaz\LaravelBitmask\Tests\Unit\StateMachine;

use Alazziaz\LaravelBitmask\StateMachine\BitmaskStateConfiguration;
use Alazziaz\LaravelBitmask\StateMachine\ConfigurableBitmaskHandler;
use Alazziaz\LaravelBitmask\Tests\TestCase;
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

class ConfigurableBitmaskTest extends TestCase
{
	public function test_can_create_basic_configuration()
	{
		$config = new BitmaskStateConfiguration(TestSteps::class);

		$this->assertInstanceOf(BitmaskStateConfiguration::class, $config);
		$this->assertEquals(TestSteps::class, $config->getEnumClass());
	}

	public function test_can_define_simple_transitions()
	{
		$config = (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1)
			->allowTransition([TestSteps::STEP_1], TestSteps::STEP_2);

		$transitions = $config->getTransitions();

		$this->assertArrayHasKey(TestSteps::STEP_0->value, $transitions);
		$this->assertArrayHasKey(TestSteps::STEP_1->value, $transitions);
		$this->assertArrayHasKey(TestSteps::STEP_2->value, $transitions);
	}

	public function test_can_validate_allowed_transitions()
	{
		$config = (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1)
			->allowTransition([TestSteps::STEP_1], TestSteps::STEP_2);

		// Starting from empty state, should be able to go to STEP_0
		$this->assertTrue($config->isTransitionAllowed(0, TestSteps::STEP_0));

		// From STEP_0, should be able to go to STEP_1
		$this->assertTrue($config->isTransitionAllowed(TestSteps::STEP_0->value, TestSteps::STEP_1));

		// From STEP_1, should be able to go to STEP_2
		$this->assertTrue($config->isTransitionAllowed(TestSteps::STEP_1->value, TestSteps::STEP_2));

		// Should not be able to skip steps
		$this->assertFalse($config->isTransitionAllowed(TestSteps::STEP_0->value, TestSteps::STEP_2));
	}

	public function test_configurable_bitmask_handler_basic_functionality()
	{
		$config = (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		$this->assertEquals(0, $handler->getValue());
		$this->assertFalse($handler->hasState(TestSteps::STEP_0));
	}

	public function test_can_add_states_with_valid_transitions()
	{
		$config = (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Should be able to add STEP_0 from empty state
		$handler->addState(TestSteps::STEP_0);
		$this->assertTrue($handler->hasState(TestSteps::STEP_0));

		// Should be able to add STEP_1 from STEP_0
		$handler->addState(TestSteps::STEP_1);
		$this->assertTrue($handler->hasState(TestSteps::STEP_1));
	}

	public function test_throws_exception_on_invalid_transitions()
	{
		$config = (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Should throw exception when trying to skip to STEP_1 without STEP_0
		$this->expectException(InvalidArgumentException::class);
		$handler->addState(TestSteps::STEP_1);
	}

	public function test_can_check_possible_transitions()
	{
		$config = (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_2);

		$handler = new ConfigurableBitmaskHandler(TestSteps::STEP_0->value, $config);

		$possibleTransitions = $handler->getPossibleTransitions();

		$this->assertContains(TestSteps::STEP_1->value, $possibleTransitions);
		$this->assertContains(TestSteps::STEP_2->value, $possibleTransitions);
	}

	public function test_multiple_prerequisites_with_and_logic()
	{
		$config = (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_2)
			->allowTransition([TestSteps::STEP_1, TestSteps::STEP_2], TestSteps::STEP_3)
			->requireAllPrerequisites(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Build up prerequisites
		$handler->addState(TestSteps::STEP_0);
		$handler->addState(TestSteps::STEP_1);
		$handler->addState(TestSteps::STEP_2);

		// Now should be able to transition to STEP_3
		$this->assertTrue($handler->canTransition(TestSteps::STEP_3));
		$handler->addState(TestSteps::STEP_3);
		$this->assertTrue($handler->hasState(TestSteps::STEP_3));
	}

	public function test_multiple_prerequisites_with_or_logic()
	{
		$config = (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_2)
			->allowTransition([TestSteps::STEP_1, TestSteps::STEP_2], TestSteps::STEP_3)
			->requireAllPrerequisites(false); // OR logic

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Build up one prerequisite
		$handler->addState(TestSteps::STEP_0);
		$handler->addState(TestSteps::STEP_1);

		// Should be able to transition to STEP_3 with just one prerequisite
		$this->assertTrue($handler->canTransition(TestSteps::STEP_3));
		$handler->addState(TestSteps::STEP_3);
		$this->assertTrue($handler->hasState(TestSteps::STEP_3));
	}

	public function test_can_remove_states()
	{
		$config = (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		$handler->addState(TestSteps::STEP_0);
		$handler->addState(TestSteps::STEP_1);

		$this->assertTrue($handler->hasState(TestSteps::STEP_0));
		$this->assertTrue($handler->hasState(TestSteps::STEP_1));

		// Remove STEP_0
		$handler->removeState(TestSteps::STEP_0);
		$this->assertFalse($handler->hasState(TestSteps::STEP_0));
		$this->assertTrue($handler->hasState(TestSteps::STEP_1));
	}

	public function test_maskable_interface_methods()
	{
		$config = (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Test add method (from Maskable interface)
		$handler->add(TestSteps::STEP_0->value);
		$this->assertTrue($handler->has(TestSteps::STEP_0->value));

		// Test has method (from Maskable interface)
		$this->assertTrue($handler->has(TestSteps::STEP_0->value));

		// Now add STEP_1 (valid transition from STEP_0)
		$handler->add(TestSteps::STEP_1->value);
		$this->assertTrue($handler->has(TestSteps::STEP_1->value));

		// Test getActiveBits method (from Maskable interface)
		$activeBits = $handler->getActiveBits();
		$this->assertContains(TestSteps::STEP_0->value, $activeBits);
		$this->assertContains(TestSteps::STEP_1->value, $activeBits);

		// Test remove method (from Maskable interface)
		$handler->remove(TestSteps::STEP_0->value);
		$this->assertFalse($handler->has(TestSteps::STEP_0->value));
		$this->assertTrue($handler->has(TestSteps::STEP_1->value));
	}

	public function test_to_array_method_provides_comprehensive_info()
	{
		$config = (new BitmaskStateConfiguration(TestSteps::class))
			->allowTransition([], TestSteps::STEP_0)
			->allowTransition([TestSteps::STEP_0], TestSteps::STEP_1);

		$handler = new ConfigurableBitmaskHandler(0, $config);
		$handler->addState(TestSteps::STEP_0);

		$array = $handler->toArray();

		$this->assertArrayHasKey('value', $array);
		$this->assertArrayHasKey('active_states', $array);
		$this->assertArrayHasKey('possible_transitions', $array);
		$this->assertArrayHasKey('binary', $array);

		$this->assertEquals(TestSteps::STEP_0->value, $array['value']);
		$this->assertContains(TestSteps::STEP_0->value, $array['active_states']);
		$this->assertContains(TestSteps::STEP_1->value, $array['possible_transitions']);
	}
}
