<?php

namespace Alazziaz\LaravelBitmask\Contracts;

use Alazziaz\LaravelBitmask\StateMachine\BitmaskStateConfiguration;

interface ConfigurableBitmaskContract
{
	/**
	 * Configure the state machine transitions and rules.
	 */
	public static function config(): BitmaskStateConfiguration;

	/**
	 * Check if a transition from current state to target state is allowed.
	 */
	public function canTransition(mixed $targetState): bool;

	/**
	 * Get the current active states.
	 */
	public function getActiveStates(): array;

	/**
	 * Get all possible transitions from the current state.
	 */
	public function getPossibleTransitions(): array;

	/**
	 * Validate if the current state allows the given transition.
	 */
	public function validateTransition(mixed $targetState): void;
}
