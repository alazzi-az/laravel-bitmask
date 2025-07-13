<?php

namespace Alazziaz\LaravelBitmask\Tests\Unit\StateMachine;

use Alazziaz\LaravelBitmask\StateMachine\BitmaskStateConfiguration;
use Alazziaz\LaravelBitmask\StateMachine\ConfigurableBitmaskHandler;
use Alazziaz\LaravelBitmask\Tests\TestCase;
use InvalidArgumentException;

enum TestPaymentSteps: int
{
	case STEP_0 = 1 << 0;  // 1   - Order Created
	case STEP_1 = 1 << 1;  // 2   - Payment Method Selected
	case STEP_2 = 1 << 2;  // 4   - Address Validated
	case STEP_3 = 1 << 3;  // 8   - Payment Processed
	case STEP_4 = 1 << 4;  // 16  - Inventory Reserved
	case STEP_5 = 1 << 5;  // 32  - Shipping Calculated
	case STEP_6 = 1 << 6;  // 64  - Order Confirmed
	case STEP_7 = 1 << 7;  // 128 - Fulfillment Started
	case STEP_8 = 1 << 8;  // 256 - Shipped
	case STEP_9 = 1 << 9;  // 512 - Delivered
}

class ConfigurableBitmaskExamplesTest extends TestCase
{
	/**
	 * Test Example 1: Basic Linear Workflow
	 */
	public function test_basic_linear_workflow()
	{
		$config = (new BitmaskStateConfiguration(TestPaymentSteps::class))
			->allowTransition([], TestPaymentSteps::STEP_0)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_1)
			->allowTransition([TestPaymentSteps::STEP_1], TestPaymentSteps::STEP_2)
			->allowTransition([TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_3)
			->allowMultipleActive(false);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Should be able to start with STEP_0
		$handler->addState(TestPaymentSteps::STEP_0);
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_0));

		// Should be able to progress to STEP_1
		$handler->addState(TestPaymentSteps::STEP_1);
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_1));

		// Should NOT be able to skip to STEP_3
		$this->expectException(InvalidArgumentException::class);
		$handler->addState(TestPaymentSteps::STEP_3);
	}

	public function test_basic_linear_workflow_proper_progression()
	{
		$config = (new BitmaskStateConfiguration(TestPaymentSteps::class))
			->allowTransition([], TestPaymentSteps::STEP_0)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_1)
			->allowTransition([TestPaymentSteps::STEP_1], TestPaymentSteps::STEP_2)
			->allowTransition([TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_3)
			->allowMultipleActive(false);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Progress through proper sequence
		$handler->addState(TestPaymentSteps::STEP_0);
		$handler->addState(TestPaymentSteps::STEP_1);
		$handler->addState(TestPaymentSteps::STEP_2);

		// Should now be able to proceed to STEP_3
		$this->assertTrue($handler->canTransition(TestPaymentSteps::STEP_3));
		$handler->addState(TestPaymentSteps::STEP_3);
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_3));
	}

	/**
	 * Test Example 2: Parallel Processing Workflow
	 */
	public function test_parallel_processing_workflow()
	{
		$config = (new BitmaskStateConfiguration(TestPaymentSteps::class))
			->allowTransition([], TestPaymentSteps::STEP_0)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_1)
			->allowTransition([TestPaymentSteps::STEP_1], TestPaymentSteps::STEP_2)
			// Parallel processes after step 2
			->allowTransition([TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_3) // Payment
			->allowTransition([TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_4) // Inventory
			->allowTransition([TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_5) // Shipping
			// Final step requires all parallel processes
			->allowTransition([TestPaymentSteps::STEP_3, TestPaymentSteps::STEP_4, TestPaymentSteps::STEP_5], TestPaymentSteps::STEP_6)
			->allowMultipleActive(true)
			->requireAllPrerequisites(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Progress through initial steps
		$handler->addState(TestPaymentSteps::STEP_0);
		$handler->addState(TestPaymentSteps::STEP_1);
		$handler->addState(TestPaymentSteps::STEP_2);

		// Start parallel processes
		$handler->addState(TestPaymentSteps::STEP_3);
		$handler->addState(TestPaymentSteps::STEP_4);
		$handler->addState(TestPaymentSteps::STEP_5);

		// Should have all parallel processes active
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_3));
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_4));
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_5));

		// Should now be able to proceed to final step
		$this->assertTrue($handler->canTransition(TestPaymentSteps::STEP_6));
		$handler->addState(TestPaymentSteps::STEP_6);
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_6));

		// Should have multiple states active
		$activeStates = $handler->getActiveStates();
		$this->assertGreaterThan(1, count($activeStates));
	}

	public function test_parallel_processing_incomplete_prerequisites()
	{
		$config = (new BitmaskStateConfiguration(TestPaymentSteps::class))
			->allowTransition([], TestPaymentSteps::STEP_0)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_1)
			->allowTransition([TestPaymentSteps::STEP_1], TestPaymentSteps::STEP_2)
			->allowTransition([TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_3)
			->allowTransition([TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_4)
			->allowTransition([TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_5)
			->allowTransition([TestPaymentSteps::STEP_3, TestPaymentSteps::STEP_4, TestPaymentSteps::STEP_5], TestPaymentSteps::STEP_6)
			->allowMultipleActive(true)
			->requireAllPrerequisites(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Progress through initial steps
		$handler->addState(TestPaymentSteps::STEP_0);
		$handler->addState(TestPaymentSteps::STEP_1);
		$handler->addState(TestPaymentSteps::STEP_2);

		// Start only some parallel processes
		$handler->addState(TestPaymentSteps::STEP_3);
		$handler->addState(TestPaymentSteps::STEP_4);
		// Missing STEP_5

		// Should NOT be able to proceed to final step
		$this->assertFalse($handler->canTransition(TestPaymentSteps::STEP_6));
	}

	/**
	 * Test Example 3: OR Logic Prerequisites
	 */
	public function test_or_logic_prerequisites()
	{
		$config = (new BitmaskStateConfiguration(TestPaymentSteps::class))
			->allowTransition([], TestPaymentSteps::STEP_0)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_1)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_2)
			// STEP_3 can be reached from either STEP_1 OR STEP_2
			->allowTransition([TestPaymentSteps::STEP_1, TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_3)
			->requireAllPrerequisites(false) // OR logic
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Start with STEP_0
		$handler->addState(TestPaymentSteps::STEP_0);

		// Take path through STEP_1 only
		$handler->addState(TestPaymentSteps::STEP_1);

		// Should be able to proceed to STEP_3 without STEP_2
		$this->assertTrue($handler->canTransition(TestPaymentSteps::STEP_3));
		$handler->addState(TestPaymentSteps::STEP_3);
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_3));
	}

	public function test_or_logic_alternative_path()
	{
		$config = (new BitmaskStateConfiguration(TestPaymentSteps::class))
			->allowTransition([], TestPaymentSteps::STEP_0)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_1)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_2)
			->allowTransition([TestPaymentSteps::STEP_1, TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_3)
			->requireAllPrerequisites(false)
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Start with STEP_0
		$handler->addState(TestPaymentSteps::STEP_0);

		// Take path through STEP_2 only
		$handler->addState(TestPaymentSteps::STEP_2);

		// Should be able to proceed to STEP_3 without STEP_1
		$this->assertTrue($handler->canTransition(TestPaymentSteps::STEP_3));
		$handler->addState(TestPaymentSteps::STEP_3);
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_3));
	}

	/**
	 * Test Example 4: Dynamic Transition Checking
	 */
	public function test_dynamic_transition_checking()
	{
		$config = (new BitmaskStateConfiguration(TestPaymentSteps::class))
			->allowTransition([], TestPaymentSteps::STEP_0)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_1)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_2)
			->allowTransition([TestPaymentSteps::STEP_1], TestPaymentSteps::STEP_3)
			->allowTransition([TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_4)
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Initial state - should only be able to transition to STEP_0
		$possibleTransitions = $handler->getPossibleTransitions();
		$this->assertContains(TestPaymentSteps::STEP_0->value, $possibleTransitions);
		$this->assertNotContains(TestPaymentSteps::STEP_1->value, $possibleTransitions);

		// After STEP_0 - should be able to transition to STEP_1 and STEP_2
		$handler->addState(TestPaymentSteps::STEP_0);
		$this->assertTrue($handler->canTransition(TestPaymentSteps::STEP_1));
		$this->assertTrue($handler->canTransition(TestPaymentSteps::STEP_2));
		$this->assertFalse($handler->canTransition(TestPaymentSteps::STEP_3));

		// After STEP_1 - should be able to transition to STEP_3
		$handler->addState(TestPaymentSteps::STEP_1);
		$this->assertTrue($handler->canTransition(TestPaymentSteps::STEP_3));
		$this->assertFalse($handler->canTransition(TestPaymentSteps::STEP_4));

		// After STEP_2 - should be able to transition to STEP_4
		$handler->addState(TestPaymentSteps::STEP_2);
		$this->assertTrue($handler->canTransition(TestPaymentSteps::STEP_4));
	}

	/**
	 * Test Example 5: State Removal and Validation
	 */
	public function test_state_removal_and_validation()
	{
		$config = (new BitmaskStateConfiguration(TestPaymentSteps::class))
			->allowTransition([], TestPaymentSteps::STEP_0)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_1)
			->allowTransition([TestPaymentSteps::STEP_1], TestPaymentSteps::STEP_2)
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Add states
		$handler->addState(TestPaymentSteps::STEP_0);
		$handler->addState(TestPaymentSteps::STEP_1);
		$handler->addState(TestPaymentSteps::STEP_2);

		// Verify all states are active
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_0));
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_1));
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_2));

		// Remove STEP_1
		$handler->removeState(TestPaymentSteps::STEP_1);
		$this->assertFalse($handler->hasState(TestPaymentSteps::STEP_1));
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_0));
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_2));

		// Test hasAllStates
		$this->assertTrue($handler->hasAllStates([TestPaymentSteps::STEP_0, TestPaymentSteps::STEP_2]));
		$this->assertFalse($handler->hasAllStates([TestPaymentSteps::STEP_0, TestPaymentSteps::STEP_1]));

		// Test hasAnyState
		$this->assertTrue($handler->hasAnyState([TestPaymentSteps::STEP_1, TestPaymentSteps::STEP_2]));
		$this->assertFalse($handler->hasAnyState([TestPaymentSteps::STEP_1, TestPaymentSteps::STEP_3]));
	}

	/**
	 * Test Example 6: Maskable Interface
	 */
	public function test_maskable_interface_compatibility()
	{
		$config = (new BitmaskStateConfiguration(TestPaymentSteps::class))
			->allowTransition([], TestPaymentSteps::STEP_0)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_1)
			->allowTransition([TestPaymentSteps::STEP_1], TestPaymentSteps::STEP_2)
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Test add() method from Maskable interface
		$handler->add(TestPaymentSteps::STEP_0->value);
		$this->assertTrue($handler->has(TestPaymentSteps::STEP_0->value));

		$handler->add(TestPaymentSteps::STEP_1->value);
		$this->assertTrue($handler->has(TestPaymentSteps::STEP_1->value));

		// Test getActiveBits() method
		$activeBits = $handler->getActiveBits();
		$this->assertContains(TestPaymentSteps::STEP_0->value, $activeBits);
		$this->assertContains(TestPaymentSteps::STEP_1->value, $activeBits);

		// Test remove() method
		$handler->remove(TestPaymentSteps::STEP_0->value);
		$this->assertFalse($handler->has(TestPaymentSteps::STEP_0->value));
		$this->assertTrue($handler->has(TestPaymentSteps::STEP_1->value));

		// Test multiple operations
		$handler->add(TestPaymentSteps::STEP_0->value, TestPaymentSteps::STEP_2->value);
		$this->assertTrue($handler->has(TestPaymentSteps::STEP_0->value));
		$this->assertTrue($handler->has(TestPaymentSteps::STEP_1->value));
		$this->assertTrue($handler->has(TestPaymentSteps::STEP_2->value));
	}

	/**
	 * Test Example 7: Array Representation
	 */
	public function test_array_representation()
	{
		$config = (new BitmaskStateConfiguration(TestPaymentSteps::class))
			->allowTransition([], TestPaymentSteps::STEP_0)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_1)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_2)
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);
		$handler->addState(TestPaymentSteps::STEP_0);

		$array = $handler->toArray();

		// Test array structure
		$this->assertArrayHasKey('value', $array);
		$this->assertArrayHasKey('active_states', $array);
		$this->assertArrayHasKey('possible_transitions', $array);
		$this->assertArrayHasKey('binary', $array);

		// Test array values
		$this->assertEquals(TestPaymentSteps::STEP_0->value, $array['value']);
		$this->assertContains(TestPaymentSteps::STEP_0->value, $array['active_states']);
		$this->assertContains(TestPaymentSteps::STEP_1->value, $array['possible_transitions']);
		$this->assertContains(TestPaymentSteps::STEP_2->value, $array['possible_transitions']);
		$this->assertIsString($array['binary']);
	}

	/**
	 * Test Complex Workflow Scenario
	 */
	public function test_complex_workflow_scenario()
	{
		// Create a complex workflow similar to the complete payment process
		$config = (new BitmaskStateConfiguration(TestPaymentSteps::class))
			->allowTransition([], TestPaymentSteps::STEP_0) // Order created
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_1) // Payment method
			->allowTransition([TestPaymentSteps::STEP_1], TestPaymentSteps::STEP_2) // Address validated

			// Parallel processing
			->allowTransition([TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_3) // Payment processed
			->allowTransition([TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_4) // Inventory reserved
			->allowTransition([TestPaymentSteps::STEP_2], TestPaymentSteps::STEP_5) // Shipping calculated

			// Confirmation requires all parallel processes
			->allowTransition([TestPaymentSteps::STEP_3, TestPaymentSteps::STEP_4, TestPaymentSteps::STEP_5], TestPaymentSteps::STEP_6)

			// Fulfillment chain
			->allowTransition([TestPaymentSteps::STEP_6], TestPaymentSteps::STEP_7) // Fulfillment started
			->allowTransition([TestPaymentSteps::STEP_7], TestPaymentSteps::STEP_8) // Shipped
			->allowTransition([TestPaymentSteps::STEP_8], TestPaymentSteps::STEP_9) // Delivered

			->requireAllPrerequisites(true)
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Complete workflow progression
		$handler->addState(TestPaymentSteps::STEP_0);
		$handler->addState(TestPaymentSteps::STEP_1);
		$handler->addState(TestPaymentSteps::STEP_2);

		// Parallel processing
		$handler->addState(TestPaymentSteps::STEP_3);
		$handler->addState(TestPaymentSteps::STEP_4);
		$handler->addState(TestPaymentSteps::STEP_5);

		// Order confirmation
		$this->assertTrue($handler->canTransition(TestPaymentSteps::STEP_6));
		$handler->addState(TestPaymentSteps::STEP_6);

		// Fulfillment chain
		$handler->addState(TestPaymentSteps::STEP_7);
		$handler->addState(TestPaymentSteps::STEP_8);
		$handler->addState(TestPaymentSteps::STEP_9);

		// Verify final state
		$this->assertTrue($handler->hasState(TestPaymentSteps::STEP_9));
		$activeStates = $handler->getActiveStates();
		$this->assertContains(TestPaymentSteps::STEP_9->value, $activeStates);

		// Verify we have multiple states active (due to allowMultipleActive)
		$this->assertGreaterThan(1, count($activeStates));
	}

	/**
	 * Test Edge Cases
	 */
	public function test_edge_cases()
	{
		$config = (new BitmaskStateConfiguration(TestPaymentSteps::class))
			->allowTransition([], TestPaymentSteps::STEP_0)
			->allowTransition([TestPaymentSteps::STEP_0], TestPaymentSteps::STEP_1)
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Test transition to same state (should be allowed)
		$handler->addState(TestPaymentSteps::STEP_0);
		$this->assertTrue($handler->canTransition(TestPaymentSteps::STEP_0));

		// Test empty prerequisites (should allow transition)
		$this->assertTrue($handler->canTransition(TestPaymentSteps::STEP_0));

		// Test getValue method
		$this->assertEquals(TestPaymentSteps::STEP_0->value, $handler->getValue());

		// Test toString method
		$binaryString = $handler->toString();
		$this->assertIsString($binaryString);
		$this->assertStringContainsString('1', $binaryString);
	}
}
