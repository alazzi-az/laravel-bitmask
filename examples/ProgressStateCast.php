<?php

namespace Examples;

use Alazziaz\LaravelBitmask\Casts\ConfigurableBitmaskCast;
use Alazziaz\LaravelBitmask\StateMachine\BitmaskStateConfiguration;

/**
 * Example implementation of a configurable bitmask state machine
 * for tracking progress through a payment process.
 * 
 * This demonstrates how to extend the base ConfigurableBitmaskCast
 * to create custom state machine logic with transition validation.
 */
class ProgressStateCast extends ConfigurableBitmaskCast
{
	/**
	 * Configure the state machine transitions and rules.
	 * This method defines what transitions are allowed based on prerequisites.
	 */
	public static function config(): BitmaskStateConfiguration
	{
		return (new BitmaskStateConfiguration(PaymentSteps::class))
			// Basic linear progression
			->allowTransition([], PaymentSteps::STEP_0) // Anyone can create an order
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_1) // Must create order first
			->allowTransition([PaymentSteps::STEP_1], PaymentSteps::STEP_2) // Must select payment method

			// Parallel processes after address validation
			->allowTransition([PaymentSteps::STEP_2], PaymentSteps::STEP_3) // Payment processing
			->allowTransition([PaymentSteps::STEP_2], PaymentSteps::STEP_4) // Inventory reservation
			->allowTransition([PaymentSteps::STEP_2], PaymentSteps::STEP_5) // Shipping calculation

			// Order confirmation requires payment + inventory + shipping
			->allowTransition([
				PaymentSteps::STEP_3,
				PaymentSteps::STEP_4,
				PaymentSteps::STEP_5
			], PaymentSteps::STEP_6)

			// Fulfillment chain
			->allowTransition([PaymentSteps::STEP_6], PaymentSteps::STEP_7) // Start fulfillment
			->allowTransition([PaymentSteps::STEP_7], PaymentSteps::STEP_8) // Ship
			->allowTransition([PaymentSteps::STEP_8], PaymentSteps::STEP_9) // Deliver

			// Configure that all prerequisites must be met (AND logic)
			->requireAllPrerequisites(true)

			// Allow multiple states to be active simultaneously
			->allowMultipleActive(true);
	}
}

/**
 * Alternative example with simpler linear workflow
 */
class SimpleProgressStateCast extends ConfigurableBitmaskCast
{
	public static function config(): BitmaskStateConfiguration
	{
		return (new BitmaskStateConfiguration(PaymentSteps::class))
			->allowTransition([], PaymentSteps::STEP_0)
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_1)
			->allowTransition([PaymentSteps::STEP_1], PaymentSteps::STEP_2)
			->allowTransition([PaymentSteps::STEP_2], PaymentSteps::STEP_3)
			->allowTransition([PaymentSteps::STEP_3], PaymentSteps::STEP_6)
			->allowTransition([PaymentSteps::STEP_6], PaymentSteps::STEP_7)
			->allowTransition([PaymentSteps::STEP_7], PaymentSteps::STEP_8)
			->allowTransition([PaymentSteps::STEP_8], PaymentSteps::STEP_9)
			->allowMultipleActive(false); // Only one step active at a time
	}
}

/**
 * Example with OR logic - any one prerequisite is sufficient
 */
class FlexibleProgressStateCast extends ConfigurableBitmaskCast
{
	public static function config(): BitmaskStateConfiguration
	{
		return (new BitmaskStateConfiguration(PaymentSteps::class))
			->allowTransition([], PaymentSteps::STEP_0)
			->allowTransition([PaymentSteps::STEP_0], PaymentSteps::STEP_1)
			->allowTransition([PaymentSteps::STEP_1], PaymentSteps::STEP_2)

			// Either payment OR inventory is sufficient for confirmation
			->allowTransition([PaymentSteps::STEP_3, PaymentSteps::STEP_4], PaymentSteps::STEP_6)

			// Use OR logic for prerequisites
			->requireAllPrerequisites(false)
			->allowMultipleActive(true);
	}
}
