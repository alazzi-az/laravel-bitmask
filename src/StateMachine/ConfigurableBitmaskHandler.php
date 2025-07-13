<?php

namespace Alazziaz\LaravelBitmask\StateMachine;

use Alazziaz\LaravelBitmask\Contracts\ConfigurableBitmaskContract;
use Alazziaz\LaravelBitmask\Facades\BitmaskFacade;
use Alazziaz\Bitmask\Contracts\Maskable;
use BackedEnum;
use InvalidArgumentException;
use UnitEnum;

class ConfigurableBitmaskHandler implements ConfigurableBitmaskContract, Maskable
{
	private int $currentMask;
	private BitmaskStateConfiguration $config;

	public function __construct(int $initialMask = 0, BitmaskStateConfiguration $config)
	{
		$this->currentMask = $initialMask;
		$this->config = $config;
	}

	/**
	 * Get the current bitmask value.
	 */
	public function getValue(): int
	{
		return $this->currentMask;
	}

	/**
	 * Set the bitmask value with transition validation.
	 */
	public function setValue(int $value): self
	{
		if ($this->currentMask !== $value) {
			$this->validateTransition($value);
		}

		$this->currentMask = $value;
		return $this;
	}

	/**
	 * Add bits to the current mask with transition validation.
	 * This is required by the Maskable interface.
	 */
	public function add(int ...$bits): self
	{
		$newMask = $this->currentMask;
		foreach ($bits as $bit) {
			$newMask |= $bit;
		}

		if ($newMask !== $this->currentMask) {
			$this->validateTransition($newMask);
			$this->currentMask = $newMask;
		}

		return $this;
	}

	/**
	 * Remove bits from the current mask with transition validation.
	 * This is required by the Maskable interface.
	 */
	public function remove(int ...$bits): self
	{
		$newMask = $this->currentMask;
		foreach ($bits as $bit) {
			$newMask &= ~$bit;
		}

		if ($newMask !== $this->currentMask) {
			// State removal should always be allowed, so bypass transition validation
			$this->currentMask = $newMask;
		}

		return $this;
	}

	/**
	 * Check if all specified bits are set in the current mask.
	 * This is required by the Maskable interface.
	 */
	public function has(int ...$bits): bool
	{
		foreach ($bits as $bit) {
			if (($this->currentMask & $bit) === 0) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get the active bits in the current mask.
	 * This is required by the Maskable interface.
	 */
	public function getActiveBits(): array
	{
		return BitmaskFacade::getActiveBits($this->currentMask);
	}

	/**
	 * Add a state to the current mask.
	 */
	public function addState(UnitEnum|BackedEnum|int $state): self
	{
		$stateValue = $this->getStateValue($state);

		// For single-state workflows, replace the current state instead of adding to it
		if (!$this->config->canHaveMultipleActive()) {
			$newMask = $stateValue;
		} else {
			$newMask = $this->currentMask | $stateValue;
		}

		if ($newMask !== $this->currentMask) {
			$this->validateTransition($newMask);
			$this->currentMask = $newMask;
		}

		return $this;
	}

	/**
	 * Remove a state from the current mask.
	 */
	public function removeState(UnitEnum|BackedEnum|int $state): self
	{
		$stateValue = $this->getStateValue($state);
		$newMask = $this->currentMask & ~$stateValue;

		if ($newMask !== $this->currentMask) {
			// State removal should always be allowed, so bypass transition validation
			$this->currentMask = $newMask;
		}

		return $this;
	}

	/**
	 * Check if a state is active.
	 */
	public function hasState(UnitEnum|BackedEnum|int $state): bool
	{
		$stateValue = $this->getStateValue($state);
		return ($this->currentMask & $stateValue) !== 0;
	}

	/**
	 * Check if all given states are active.
	 */
	public function hasAllStates(array $states): bool
	{
		foreach ($states as $state) {
			if (!$this->hasState($state)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if any of the given states are active.
	 */
	public function hasAnyState(array $states): bool
	{
		foreach ($states as $state) {
			if ($this->hasState($state)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if transition to a target state/mask is allowed.
	 */
	public function canTransitionTo(UnitEnum|BackedEnum|int $target): bool
	{
		$targetValue = is_int($target) ? $target : $this->getStateValue($target);

		// If target is the same as current, it's always allowed
		if ($targetValue === $this->currentMask) {
			return true;
		}

		// For single state transitions (when allowMultipleActive is false)
		if (!$this->config->canHaveMultipleActive()) {
			// Direct transition check - can we go from current state to target state?
			return $this->config->isTransitionAllowed($this->currentMask, $targetValue);
		}

		// If transitioning by adding a single state (for multiple active states)
		if (($targetValue & $this->currentMask) === $this->currentMask) {
			$newState = $targetValue ^ $this->currentMask;
			// Check if this is a single bit (power of 2)
			if (($newState & ($newState - 1)) === 0 && $newState > 0) {
				return $this->config->isTransitionAllowed($this->currentMask, $newState);
			}
		}

		// For complex transitions, check if all new states are allowed
		$newStates = $this->getNewStates($this->currentMask, $targetValue);
		if (empty($newStates)) {
			// If no new states, this might be a removal or replacement
			// Check if the target state is explicitly allowed from current state
			return $this->config->isTransitionAllowed($this->currentMask, $targetValue);
		}

		foreach ($newStates as $newState) {
			if (!$this->config->isTransitionAllowed($this->currentMask, $newState)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get active states as an array of values.
	 */
	public function getActiveStates(): array
	{
		return BitmaskFacade::getActiveBits($this->currentMask);
	}

	/**
	 * Get possible transitions from current state.
	 */
	public function getPossibleTransitions(): array
	{
		return $this->config->getPossibleTransitions($this->currentMask);
	}

	/**
	 * Validate if the transition to target state is allowed.
	 * 
	 * @throws InvalidArgumentException
	 */
	public function validateTransition(mixed $targetState): void
	{
		if (!$this->canTransition($targetState)) {
			$currentStates = implode(', ', $this->getActiveStates());
			$targetValue = is_int($targetState) ? $targetState : $this->getStateValue($targetState);

			throw new InvalidArgumentException(
				"Transition from states [{$currentStates}] to target [{$targetValue}] is not allowed by the state machine configuration."
			);
		}
	}

	/**
	 * Check if transition is allowed (alias for canTransitionTo).
	 */
	public function canTransition(mixed $targetState): bool
	{
		return $this->canTransitionTo($targetState);
	}

	/**
	 * Get the configuration object.
	 */
	public function getConfig(): BitmaskStateConfiguration
	{
		return $this->config;
	}

	/**
	 * Convert state to binary string representation.
	 */
	public function toString(): string
	{
		return BitmaskFacade::convertToBinaryString($this->currentMask);
	}

	/**
	 * Convert state to array representation.
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->currentMask,
			'active_states' => $this->getActiveStates(),
			'possible_transitions' => $this->getPossibleTransitions(),
			'binary' => $this->toString(),
		];
	}

	/**
	 * Static method to create configuration instance.
	 */
	public static function config(): BitmaskStateConfiguration
	{
		return new BitmaskStateConfiguration();
	}

	/**
	 * Convert state to integer value.
	 */
	private function getStateValue(UnitEnum|BackedEnum|int $state): int
	{
		if ($state instanceof BackedEnum) {
			return $state->value;
		}

		if (is_int($state)) {
			return $state;
		}

		// For UnitEnum, use ordinal position with bit shifting
		if ($state instanceof UnitEnum) {
			$cases = $state::cases();
			$index = array_search($state, $cases, true);
			return 1 << $index;
		}

		return 0;
	}

	/**
	 * Get new states that would be added in a transition.
	 */
	private function getNewStates(int $currentMask, int $targetMask): array
	{
		$newBits = $targetMask & ~$currentMask;
		return BitmaskFacade::getActiveBits($newBits);
	}
}
