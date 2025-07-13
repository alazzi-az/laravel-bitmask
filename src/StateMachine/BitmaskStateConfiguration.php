<?php

namespace Alazziaz\LaravelBitmask\StateMachine;

use BackedEnum;
use UnitEnum;

class BitmaskStateConfiguration
{
	/** @var array<string, array<int, array<int>>> */
	private array $transitions = [];

	/** @var array<string, string> */
	private array $enumClasses = [];

	/** @var array<string, bool> */
	private array $allowMultipleActive = [];

	/** @var array<string, bool> */
	private array $requireAllPrerequisites = [];

	public function __construct(private readonly ?string $defaultEnumClass = null)
	{
		if ($this->defaultEnumClass) {
			$this->enumClasses['default'] = $this->defaultEnumClass;
		}
	}

	/**
	 * Define an allowed transition with prerequisites and target state.
	 * 
	 * @param array<UnitEnum|BackedEnum|int> $prerequisites Required states to be active
	 * @param UnitEnum|BackedEnum|int $targetState State to transition to
	 * @param string|null $context Optional context for grouping transitions
	 */
	public function allowTransition(
		array $prerequisites,
		UnitEnum|BackedEnum|int $targetState,
		?string $context = null
	): self {
		$context = $context ?? 'default';

		if (!isset($this->transitions[$context])) {
			$this->transitions[$context] = [];
		}

		$targetValue = $this->getStateValue($targetState);
		$prerequisiteValues = array_map(fn($state) => $this->getStateValue($state), $prerequisites);

		$this->transitions[$context][$targetValue] = $prerequisiteValues;

		return $this;
	}

	/**
	 * Set the enum class for a specific context.
	 */
	public function setEnumClass(string $enumClass, string $context = 'default'): self
	{
		$this->enumClasses[$context] = $enumClass;
		return $this;
	}

	/**
	 * Configure if multiple states can be active simultaneously.
	 */
	public function allowMultipleActive(bool $allow = true, string $context = 'default'): self
	{
		$this->allowMultipleActive[$context] = $allow;
		return $this;
	}

	/**
	 * Configure if all prerequisites must be met (AND logic) or just one (OR logic).
	 */
	public function requireAllPrerequisites(bool $require = true, string $context = 'default'): self
	{
		$this->requireAllPrerequisites[$context] = $require;
		return $this;
	}

	/**
	 * Get the configured transitions.
	 */
	public function getTransitions(string $context = 'default'): array
	{
		return $this->transitions[$context] ?? [];
	}

	/**
	 * Get the enum class for a context.
	 */
	public function getEnumClass(string $context = 'default'): ?string
	{
		return $this->enumClasses[$context] ?? null;
	}

	/**
	 * Check if multiple states can be active.
	 */
	public function canHaveMultipleActive(string $context = 'default'): bool
	{
		return $this->allowMultipleActive[$context] ?? true;
	}

	/**
	 * Check if all prerequisites are required.
	 */
	public function areAllPrerequisitesRequired(string $context = 'default'): bool
	{
		return $this->requireAllPrerequisites[$context] ?? true;
	}

	/**
	 * Check if a transition is allowed given the current state mask.
	 */
	public function isTransitionAllowed(int $currentMask, UnitEnum|BackedEnum|int $targetState, string $context = 'default'): bool
	{
		$targetValue = $this->getStateValue($targetState);
		$transitions = $this->getTransitions($context);

		// If no transitions are defined for this target, disallow it
		// Only explicitly defined transitions are allowed
		if (!isset($transitions[$targetValue])) {
			return false;
		}

		$prerequisites = $transitions[$targetValue];
		$requireAll = $this->areAllPrerequisitesRequired($context);

		// Check if prerequisites are met
		return $this->checkPrerequisites($currentMask, $prerequisites, $requireAll);
	}

	/**
	 * Get possible transitions from current state.
	 */
	public function getPossibleTransitions(int $currentMask, string $context = 'default'): array
	{
		$transitions = $this->getTransitions($context);
		$possible = [];

		foreach ($transitions as $targetValue => $prerequisites) {
			$requireAll = $this->areAllPrerequisitesRequired($context);
			if ($this->checkPrerequisites($currentMask, $prerequisites, $requireAll)) {
				$possible[] = $targetValue;
			}
		}

		return $possible;
	}

	/**
	 * Get all states that can transition to a target state.
	 * This is used by the HasBitmask trait for database queries.
	 */
	public function getStatesCanTransitionTo(int $targetValue, string $context = 'default'): array
	{
		$transitions = $this->getTransitions($context);
		$possibleStates = [];

		// Check if target state has prerequisites
		if (!isset($transitions[$targetValue])) {
			// If no prerequisites are defined, any state can transition to it
			// Return common states that might exist
			return [0]; // Empty state can always transition
		}

		$prerequisites = $transitions[$targetValue];
		$requireAll = $this->areAllPrerequisitesRequired($context);

		if (empty($prerequisites)) {
			// No prerequisites means any state can transition
			return [0];
		}

		if ($requireAll) {
			// All prerequisites must be active
			// Calculate the minimum mask that satisfies all prerequisites
			$requiredMask = array_reduce($prerequisites, fn($carry, $prereq) => $carry | $prereq, 0);
			$possibleStates[] = $requiredMask;

			// Also include any superset of the required mask
			// For simplicity, we'll just return the exact required mask
			// In a real implementation, you might want to enumerate more possibilities
		} else {
			// OR logic - any one prerequisite is sufficient
			foreach ($prerequisites as $prerequisite) {
				$possibleStates[] = $prerequisite;
			}
		}

		return array_unique($possibleStates);
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

		// For UnitEnum, we need to determine the value based on ordinal position
		// This is a simplified approach - in reality, you'd want to define values explicitly
		if ($state instanceof UnitEnum) {
			$cases = $state::cases();
			$index = array_search($state, $cases, true);
			return 1 << $index; // Use bit shifting for ordinal position
		}

		return 0;
	}

	/**
	 * Check if prerequisites are met in the current mask.
	 */
	private function checkPrerequisites(int $currentMask, array $prerequisites, bool $requireAll): bool
	{
		if (empty($prerequisites)) {
			// Empty prerequisites means this is an initial state
			// It can be reached from state 0 (empty state) or when removing states
			// But not from arbitrary non-empty states in a single-state workflow
			if ($currentMask === 0) {
				return true; // Initial transition
			}

			// For multiple active states, allow transitions to initial states
			// This is needed for state removal operations
			if ($this->canHaveMultipleActive()) {
				return true;
			}

			// For single active state workflows, prevent arbitrary transitions back to initial states
			// This maintains the linear progression
			return false;
		}

		if ($requireAll) {
			// All prerequisites must be active (AND logic)
			foreach ($prerequisites as $prerequisite) {
				if (($currentMask & $prerequisite) === 0) {
					return false;
				}
			}
			return true;
		} else {
			// At least one prerequisite must be active (OR logic)
			foreach ($prerequisites as $prerequisite) {
				if (($currentMask & $prerequisite) !== 0) {
					return true;
				}
			}
			return false;
		}
	}
}
