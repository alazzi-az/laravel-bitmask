<?php

namespace Alazziaz\LaravelBitmask\Tests\Unit\StateMachine;

use Alazziaz\LaravelBitmask\StateMachine\BitmaskStateConfiguration;
use Alazziaz\LaravelBitmask\StateMachine\ConfigurableBitmaskHandler;
use Alazziaz\LaravelBitmask\Tests\TestCase;

enum LargeTestSteps: int
{
	case STEP_0 = 1 << 0;
	case STEP_1 = 1 << 1;
	case STEP_2 = 1 << 2;
	case STEP_3 = 1 << 3;
	case STEP_4 = 1 << 4;
	case STEP_5 = 1 << 5;
	case STEP_6 = 1 << 6;
	case STEP_7 = 1 << 7;
	case STEP_8 = 1 << 8;
	case STEP_9 = 1 << 9;
	case STEP_10 = 1 << 10;
	case STEP_11 = 1 << 11;
	case STEP_12 = 1 << 12;
	case STEP_13 = 1 << 13;
	case STEP_14 = 1 << 14;
	case STEP_15 = 1 << 15;
	case STEP_16 = 1 << 16;
	case STEP_17 = 1 << 17;
	case STEP_18 = 1 << 18;
	case STEP_19 = 1 << 19;
	case STEP_20 = 1 << 20;
	case STEP_21 = 1 << 21;
	case STEP_22 = 1 << 22;
	case STEP_23 = 1 << 23;
	case STEP_24 = 1 << 24;
	case STEP_25 = 1 << 25;
	case STEP_26 = 1 << 26;
	case STEP_27 = 1 << 27;
	case STEP_28 = 1 << 28;
	case STEP_29 = 1 << 29;
	case STEP_30 = 1 << 30;
}

class ConfigurableBitmaskPerformanceTest extends TestCase
{
	/**
	 * Test performance with many states
	 */
	public function test_performance_with_many_states()
	{
		$config = new BitmaskStateConfiguration(LargeTestSteps::class);

		// Create a linear chain of all states
		$steps = LargeTestSteps::cases();
		$config->allowTransition([], $steps[0]);

		for ($i = 1; $i < count($steps); $i++) {
			$config->allowTransition([$steps[$i - 1]], $steps[$i]);
		}

		$handler = new ConfigurableBitmaskHandler(0, $config);

		$startTime = microtime(true);

		// Progress through all states
		foreach ($steps as $step) {
			$handler->addState($step);
		}

		$endTime = microtime(true);
		$duration = $endTime - $startTime;

		// Should complete within reasonable time (less than 1 second)
		$this->assertLessThan(1.0, $duration, "Performance test took too long: {$duration} seconds");

		// Verify all states are active
		$this->assertCount(count($steps), $handler->getActiveStates());
	}

	/**
	 * Test performance with complex transition checking
	 */
	public function test_performance_with_complex_transitions()
	{
		$config = new BitmaskStateConfiguration(LargeTestSteps::class);
		$steps = LargeTestSteps::cases();

		// Create complex prerequisite patterns
		$config->allowTransition([], $steps[0]);
		$config->allowTransition([$steps[0]], $steps[1]);
		$config->allowTransition([$steps[0]], $steps[2]);
		$config->allowTransition([$steps[1], $steps[2]], $steps[3]);

		// Create parallel branches
		for ($i = 4; $i < 10; $i++) {
			$config->allowTransition([$steps[3]], $steps[$i]);
		}

		// Create convergence point
		$prerequisites = array_slice($steps, 4, 6);
		$config->allowTransition($prerequisites, $steps[10]);

		$handler = new ConfigurableBitmaskHandler(0, $config);

		$startTime = microtime(true);

		// Build up the state
		$handler->addState($steps[0]);
		$handler->addState($steps[1]);
		$handler->addState($steps[2]);
		$handler->addState($steps[3]);

		// Add parallel branches
		for ($i = 4; $i < 10; $i++) {
			$handler->addState($steps[$i]);
		}

		// Test transition checking performance
		for ($i = 0; $i < 1000; $i++) {
			$handler->canTransition($steps[10]);
		}

		$endTime = microtime(true);
		$duration = $endTime - $startTime;

		// Should complete within reasonable time
		$this->assertLessThan(1.0, $duration, "Complex transition checking took too long: {$duration} seconds");
	}

	/**
	 * Test performance with many possible transitions
	 */
	public function test_performance_with_many_possible_transitions()
	{
		$config = new BitmaskStateConfiguration(LargeTestSteps::class);
		$steps = LargeTestSteps::cases();

		// Create a hub state that can transition to many others
		$config->allowTransition([], $steps[0]);

		// From step 0, can go to all other steps
		for ($i = 1; $i < count($steps); $i++) {
			$config->allowTransition([$steps[0]], $steps[$i]);
		}

		$handler = new ConfigurableBitmaskHandler(0, $config);
		$handler->addState($steps[0]);

		$startTime = microtime(true);

		// Test getting possible transitions many times
		for ($i = 0; $i < 1000; $i++) {
			$possibleTransitions = $handler->getPossibleTransitions();
			$this->assertNotEmpty($possibleTransitions);
		}

		$endTime = microtime(true);
		$duration = $endTime - $startTime;

		// Should complete within reasonable time
		$this->assertLessThan(1.0, $duration, "Getting possible transitions took too long: {$duration} seconds");
	}

	/**
	 * Test performance with frequent state changes
	 */
	public function test_performance_with_frequent_state_changes()
	{
		$config = new BitmaskStateConfiguration(LargeTestSteps::class);
		$steps = LargeTestSteps::cases();

		// Create bidirectional transitions between adjacent steps
		$config->allowTransition([], $steps[0]);
		for ($i = 1; $i < min(10, count($steps)); $i++) {
			$config->allowTransition([$steps[$i - 1]], $steps[$i]);
			$config->allowTransition([$steps[$i]], $steps[$i - 1]);
		}

		$handler = new ConfigurableBitmaskHandler(0, $config);

		$startTime = microtime(true);

		// Perform many state changes
		for ($i = 0; $i < 1000; $i++) {
			$stepIndex = $i % 10;
			$step = $steps[$stepIndex];

			if (!$handler->hasState($step)) {
				if ($handler->canTransition($step)) {
					$handler->addState($step);
				}
			} else {
				$handler->removeState($step);
			}
		}

		$endTime = microtime(true);
		$duration = $endTime - $startTime;

		// Should complete within reasonable time
		$this->assertLessThan(2.0, $duration, "Frequent state changes took too long: {$duration} seconds");
	}

	/**
	 * Test memory usage with large configurations
	 */
	public function test_memory_usage_with_large_configurations()
	{
		$memoryBefore = memory_get_usage();

		$config = new BitmaskStateConfiguration(LargeTestSteps::class);
		$steps = LargeTestSteps::cases();

		// Create many transitions
		for ($i = 0; $i < count($steps); $i++) {
			for ($j = 0; $j < count($steps); $j++) {
				if ($i !== $j) {
					$config->allowTransition([$steps[$i]], $steps[$j]);
				}
			}
		}

		$handler = new ConfigurableBitmaskHandler(0, $config);

		$memoryAfter = memory_get_usage();
		$memoryUsed = $memoryAfter - $memoryBefore;

		// Should use reasonable amount of memory (less than 10MB)
		$this->assertLessThan(10 * 1024 * 1024, $memoryUsed, "Memory usage too high: " . ($memoryUsed / 1024 / 1024) . " MB");
	}

	/**
	 * Test performance with OR logic and many prerequisites
	 */
	public function test_performance_with_or_logic_many_prerequisites()
	{
		$config = new BitmaskStateConfiguration(LargeTestSteps::class);
		$steps = LargeTestSteps::cases();

		// Create initial states
		for ($i = 0; $i < 10; $i++) {
			$config->allowTransition([], $steps[$i]);
		}

		// Create target state with many OR prerequisites
		$prerequisites = array_slice($steps, 0, 20);
		$config->allowTransition($prerequisites, $steps[25]);
		$config->requireAllPrerequisites(false); // OR logic

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Add one prerequisite
		$handler->addState($steps[0]);

		$startTime = microtime(true);

		// Test transition checking many times
		for ($i = 0; $i < 1000; $i++) {
			$canTransition = $handler->canTransition($steps[25]);
			$this->assertTrue($canTransition);
		}

		$endTime = microtime(true);
		$duration = $endTime - $startTime;

		// Should complete within reasonable time
		$this->assertLessThan(1.0, $duration, "OR logic checking took too long: {$duration} seconds");
	}

	/**
	 * Test performance with array representation
	 */
	public function test_performance_with_array_representation()
	{
		$config = new BitmaskStateConfiguration(LargeTestSteps::class);
		$steps = LargeTestSteps::cases();

		// Create some transitions
		$config->allowTransition([], $steps[0]);
		for ($i = 1; $i < 10; $i++) {
			$config->allowTransition([$steps[$i - 1]], $steps[$i]);
		}

		$handler = new ConfigurableBitmaskHandler(0, $config);

		// Add several states
		for ($i = 0; $i < 5; $i++) {
			$handler->addState($steps[$i]);
		}

		$startTime = microtime(true);

		// Test array representation generation many times
		for ($i = 0; $i < 1000; $i++) {
			$array = $handler->toArray();
			$this->assertArrayHasKey('value', $array);
			$this->assertArrayHasKey('active_states', $array);
			$this->assertArrayHasKey('possible_transitions', $array);
			$this->assertArrayHasKey('binary', $array);
		}

		$endTime = microtime(true);
		$duration = $endTime - $startTime;

		// Should complete within reasonable time
		$this->assertLessThan(1.0, $duration, "Array representation generation took too long: {$duration} seconds");
	}

	/**
	 * Test performance with maskable interface operations
	 */
	public function test_performance_with_maskable_interface_operations()
	{
		$config = new BitmaskStateConfiguration(LargeTestSteps::class);
		$steps = LargeTestSteps::cases();

		// Create transitions for all steps
		$config->allowTransition([], $steps[0]);
		for ($i = 1; $i < count($steps); $i++) {
			$config->allowTransition([$steps[0]], $steps[$i]);
		}

		$handler = new ConfigurableBitmaskHandler(0, $config);
		$handler->addState($steps[0]);

		$startTime = microtime(true);

		// Perform many maskable interface operations
		for ($i = 0; $i < 1000; $i++) {
			$stepIndex = ($i % (count($steps) - 1)) + 1;
			$step = $steps[$stepIndex];

			// Test add/remove/has operations
			$handler->add($step->value);
			$handler->has($step->value);
			$handler->remove($step->value);
			$handler->getActiveBits();
		}

		$endTime = microtime(true);
		$duration = $endTime - $startTime;

		// Should complete within reasonable time
		$this->assertLessThan(1.0, $duration, "Maskable interface operations took too long: {$duration} seconds");
	}
}
