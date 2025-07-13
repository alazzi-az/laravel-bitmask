<?php

namespace Examples;

use Alazziaz\LaravelBitmask\StateMachine\BitmaskStateConfiguration;
use Alazziaz\LaravelBitmask\StateMachine\ConfigurableBitmaskHandler;
use InvalidArgumentException;

/**
 * Comprehensive usage examples for the configurable bitmask state machine.
 * This file demonstrates all the features and capabilities of the new system.
 */
class UsageExamples
{
	/**
	 * Example 1: Basic Linear Workflow
	 * Demonstrates simple step-by-step progression.
	 */
	public function basicLinearWorkflow()
	{
		echo "=== Basic Linear Workflow ===\n";

		// Create a simple linear configuration
		$config = (new BitmaskStateConfiguration(PaymentSteps::class))
			->allowTransition([], PaymentSteps::STEP_0)
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_1)
			->allowTransition([PaymentSteps::STEP_1], PaymentSteps::STEP_2)
			->allowTransition([PaymentSteps::STEP_2], PaymentSteps::STEP_3)
			->allowMultipleActive(false); // Only one step active at a time

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Step through the workflow
		$handler->addState(PaymentSteps::STEP_0);
		echo "Step 0 completed: " . PaymentSteps::STEP_0->description() . "\n";

		$handler->addState(PaymentSteps::STEP_1);
		echo "Step 1 completed: " . PaymentSteps::STEP_1->description() . "\n";

		// Try to skip a step (this should fail)
		try {
			$handler->addState(PaymentSteps::STEP_3);
		} catch (InvalidArgumentException $e) {
			echo "⚠️  Cannot skip steps: " . $e->getMessage() . "\n";
		}

		// Continue properly
		$handler->addState(PaymentSteps::STEP_2);
		echo "Step 2 completed: " . PaymentSteps::STEP_2->description() . "\n";

		echo "Current progress: " . $handler->getValue() . " (binary: " . $handler->toString() . ")\n";
		echo "Active states: " . implode(', ', $handler->getActiveStates()) . "\n";
		echo "Possible next transitions: " . implode(', ', $handler->getPossibleTransitions()) . "\n";
		echo "\n";
	}

	/**
	 * Example 2: Parallel Processing Workflow
	 * Demonstrates how multiple steps can be active simultaneously.
	 */
	public function parallelProcessingWorkflow()
	{
		echo "=== Parallel Processing Workflow ===\n";

		$config = (new BitmaskStateConfiguration(PaymentSteps::class))
			->allowTransition([], PaymentSteps::STEP_0)
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_1)
			->allowTransition([PaymentSteps::STEP_1], PaymentSteps::STEP_2)

			// After step 2, multiple parallel processes can start
			->allowTransition([PaymentSteps::STEP_2], PaymentSteps::STEP_3) // Payment
			->allowTransition([PaymentSteps::STEP_2], PaymentSteps::STEP_4) // Inventory
			->allowTransition([PaymentSteps::STEP_2], PaymentSteps::STEP_5) // Shipping

			// Final step requires all parallel processes to complete
			->allowTransition([PaymentSteps::STEP_3, PaymentSteps::STEP_4, PaymentSteps::STEP_5], PaymentSteps::STEP_6)
			->allowMultipleActive(true) // Multiple steps can be active
			->requireAllPrerequisites(true); // All prerequisites must be met

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Progress through initial steps
		$handler->addState(PaymentSteps::STEP_0);
		$handler->addState(PaymentSteps::STEP_1);
		$handler->addState(PaymentSteps::STEP_2);

		echo "Initial steps completed. Starting parallel processes...\n";

		// Start parallel processes
		$handler->addState(PaymentSteps::STEP_3);
		echo "✓ Payment processing started\n";

		$handler->addState(PaymentSteps::STEP_4);
		echo "✓ Inventory reservation started\n";

		$handler->addState(PaymentSteps::STEP_5);
		echo "✓ Shipping calculation started\n";

		// Now all prerequisites are met, can proceed to final step
		$handler->addState(PaymentSteps::STEP_6);
		echo "✓ Order confirmed - all parallel processes completed\n";

		echo "Final state: " . $handler->getValue() . " (binary: " . $handler->toString() . ")\n";
		echo "Active states: " . implode(', ', $handler->getActiveStates()) . "\n";
		echo "\n";
	}

	/**
	 * Example 3: OR Logic Prerequisites
	 * Demonstrates how to use OR logic for prerequisites.
	 */
	public function orLogicPrerequisites()
	{
		echo "=== OR Logic Prerequisites ===\n";

		$config = (new BitmaskStateConfiguration(PaymentSteps::class))
			->allowTransition([], PaymentSteps::STEP_0)
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_1)
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_2)

			// STEP_3 can be reached from either STEP_1 OR STEP_2 (not both required)
			->allowTransition([PaymentSteps::STEP_1, PaymentSteps::STEP_2], PaymentSteps::STEP_3)
			->requireAllPrerequisites(false) // OR logic - any one prerequisite is sufficient
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Start with STEP_0
		$handler->addState(PaymentSteps::STEP_0);
		echo "Step 0 completed\n";

		// Take one path
		$handler->addState(PaymentSteps::STEP_1);
		echo "Step 1 completed\n";

		// Can now proceed to STEP_3 without needing STEP_2
		$handler->addState(PaymentSteps::STEP_3);
		echo "✓ Step 3 completed (only needed Step 1, not Step 2)\n";

		echo "Current state: " . $handler->getValue() . " (binary: " . $handler->toString() . ")\n";
		echo "\n";
	}

	/**
	 * Example 4: Dynamic Transition Checking
	 * Demonstrates how to check what transitions are possible at runtime.
	 */
	public function dynamicTransitionChecking()
	{
		echo "=== Dynamic Transition Checking ===\n";

		$config = (new BitmaskStateConfiguration(PaymentSteps::class))
			->allowTransition([], PaymentSteps::STEP_0)
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_1)
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_2)
			->allowTransition([PaymentSteps::STEP_1], PaymentSteps::STEP_3)
			->allowTransition([PaymentSteps::STEP_2], PaymentSteps::STEP_4)
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Helper function to display current state
		$displayState = function () use ($handler) {
			echo "Current state: " . $handler->getValue() . "\n";
			echo "Active states: " . implode(', ', $handler->getActiveStates()) . "\n";
			echo "Possible transitions: " . implode(', ', $handler->getPossibleTransitions()) . "\n";
			echo "---\n";
		};

		echo "Initial state:\n";
		$displayState();

		$handler->addState(PaymentSteps::STEP_0);
		echo "After adding STEP_0:\n";
		$displayState();

		echo "Can transition to STEP_1? " . ($handler->canTransition(PaymentSteps::STEP_1) ? "Yes" : "No") . "\n";
		echo "Can transition to STEP_2? " . ($handler->canTransition(PaymentSteps::STEP_2) ? "Yes" : "No") . "\n";
		echo "Can transition to STEP_3? " . ($handler->canTransition(PaymentSteps::STEP_3) ? "Yes" : "No") . "\n";
		echo "\n";
	}

	/**
	 * Example 5: State Removal and Validation
	 * Demonstrates removing states and validation logic.
	 */
	public function stateRemovalAndValidation()
	{
		echo "=== State Removal and Validation ===\n";

		$config = (new BitmaskStateConfiguration(PaymentSteps::class))
			->allowTransition([], PaymentSteps::STEP_0)
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_1)
			->allowTransition([PaymentSteps::STEP_1], PaymentSteps::STEP_2)
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Add states
		$handler->addState(PaymentSteps::STEP_0);
		$handler->addState(PaymentSteps::STEP_1);
		$handler->addState(PaymentSteps::STEP_2);

		echo "All states added: " . implode(', ', $handler->getActiveStates()) . "\n";

		// Remove a state
		$handler->removeState(PaymentSteps::STEP_1);
		echo "After removing STEP_1: " . implode(', ', $handler->getActiveStates()) . "\n";

		// Check specific states
		echo "Has STEP_0? " . ($handler->hasState(PaymentSteps::STEP_0) ? "Yes" : "No") . "\n";
		echo "Has STEP_1? " . ($handler->hasState(PaymentSteps::STEP_1) ? "Yes" : "No") . "\n";
		echo "Has STEP_2? " . ($handler->hasState(PaymentSteps::STEP_2) ? "Yes" : "No") . "\n";

		// Check multiple states
		echo "Has all [STEP_0, STEP_2]? " . ($handler->hasAllStates([PaymentSteps::STEP_0, PaymentSteps::STEP_2]) ? "Yes" : "No") . "\n";
		echo "Has any [STEP_1, STEP_2]? " . ($handler->hasAnyState([PaymentSteps::STEP_1, PaymentSteps::STEP_2]) ? "Yes" : "No") . "\n";
		echo "\n";
	}

	/**
	 * Example 6: Working with Maskable Interface
	 * Demonstrates using the standard Maskable interface methods.
	 */
	public function maskableInterfaceExample()
	{
		echo "=== Maskable Interface Example ===\n";

		$config = (new BitmaskStateConfiguration(PaymentSteps::class))
			->allowTransition([], PaymentSteps::STEP_0)
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_1)
			->allowTransition([PaymentSteps::STEP_1], PaymentSteps::STEP_2)
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Using Maskable interface methods
		echo "Using add() method:\n";
		$handler->add(PaymentSteps::STEP_0->value);
		$handler->add(PaymentSteps::STEP_1->value);

		echo "Active bits: " . implode(', ', $handler->getActiveBits()) . "\n";

		echo "Using has() method:\n";
		echo "Has " . PaymentSteps::STEP_0->value . "? " . ($handler->has(PaymentSteps::STEP_0->value) ? "Yes" : "No") . "\n";
		echo "Has " . PaymentSteps::STEP_2->value . "? " . ($handler->has(PaymentSteps::STEP_2->value) ? "Yes" : "No") . "\n";

		echo "Using remove() method:\n";
		$handler->remove(PaymentSteps::STEP_0->value);
		echo "After removing " . PaymentSteps::STEP_0->value . ": " . implode(', ', $handler->getActiveBits()) . "\n";

		echo "Current value: " . $handler->getValue() . "\n";
		echo "\n";
	}

	/**
	 * Example 7: Complete Array Representation
	 * Demonstrates the comprehensive array output.
	 */
	public function arrayRepresentationExample()
	{
		echo "=== Array Representation Example ===\n";

		$config = (new BitmaskStateConfiguration(PaymentSteps::class))
			->allowTransition([], PaymentSteps::STEP_0)
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_1)
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_2)
			->allowMultipleActive(true);

		$handler = new ConfigurableBitmaskHandler(0, $config);
		$handler->addState(PaymentSteps::STEP_0);

		$array = $handler->toArray();

		echo "Complete state representation:\n";
		echo "Value: " . $array['value'] . "\n";
		echo "Active states: " . implode(', ', $array['active_states']) . "\n";
		echo "Possible transitions: " . implode(', ', $array['possible_transitions']) . "\n";
		echo "Binary representation: " . $array['binary'] . "\n";
		echo "\n";
	}

	/**
	 * Run all examples
	 */
	public function runAllExamples()
	{
		echo "🚀 Laravel Bitmask State Machine - Usage Examples\n";
		echo "================================================\n\n";

		$this->basicLinearWorkflow();
		$this->parallelProcessingWorkflow();
		$this->orLogicPrerequisites();
		$this->dynamicTransitionChecking();
		$this->stateRemovalAndValidation();
		$this->maskableInterfaceExample();
		$this->arrayRepresentationExample();

		echo "✅ All examples completed successfully!\n";
		echo "You can now use these patterns in your own Laravel applications.\n";
	}
}

// Run the examples if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
	$examples = new UsageExamples();
	$examples->runAllExamples();
}
