<?php

namespace Examples;

use Illuminate\Database\Eloquent\Model;
use Alazziaz\LaravelBitmask\StateMachine\ConfigurableBitmaskHandler;

/**
 * Example Laravel model demonstrating the usage of configurable bitmask casts.
 * This model represents an order with a progress state tracked using bitmasks.
 */
class OrderModel extends Model
{
	protected $table = 'orders';

	protected $fillable = [
		'customer_id',
		'total_amount',
		'progress_state',
		'simple_progress_state',
		'flexible_progress_state',
	];

	/**
	 * Define the casts for the model.
	 * Each cast uses a different configuration for demonstration.
	 */
	protected $casts = [
		'progress_state' => ProgressStateCast::class,
		'simple_progress_state' => SimpleProgressStateCast::class,
		'flexible_progress_state' => FlexibleProgressStateCast::class,
	];

	/**
	 * Example: Check if the order can proceed to payment processing.
	 */
	public function canProcessPayment(): bool
	{
		return $this->progress_state instanceof ConfigurableBitmaskHandler
			&& $this->progress_state->canTransition(PaymentSteps::STEP_3);
	}

	/**
	 * Example: Get the current progress description.
	 */
	public function getCurrentProgressDescription(): array
	{
		if (!$this->progress_state instanceof ConfigurableBitmaskHandler) {
			return [];
		}

		$activeStates = $this->progress_state->getActiveStates();
		$descriptions = [];

		foreach ($activeStates as $state) {
			foreach (PaymentSteps::cases() as $step) {
				if ($step->value === $state) {
					$descriptions[] = $step->description();
					break;
				}
			}
		}

		return $descriptions;
	}

	/**
	 * Example: Advance the order to the next valid step.
	 */
	public function advanceToStep(PaymentSteps $step): bool
	{
		if (!$this->progress_state instanceof ConfigurableBitmaskHandler) {
			return false;
		}

		if ($this->progress_state->canTransition($step)) {
			$this->progress_state->addState($step);
			$this->save();
			return true;
		}

		return false;
	}

	/**
	 * Example: Get all possible next steps.
	 */
	public function getPossibleNextSteps(): array
	{
		if (!$this->progress_state instanceof ConfigurableBitmaskHandler) {
			return [];
		}

		$possibleTransitions = $this->progress_state->getPossibleTransitions();
		$nextSteps = [];

		foreach ($possibleTransitions as $transition) {
			foreach (PaymentSteps::cases() as $step) {
				if ($step->value === $transition) {
					$nextSteps[] = $step;
					break;
				}
			}
		}

		return $nextSteps;
	}

	/**
	 * Example: Check if the order is complete.
	 */
	public function isComplete(): bool
	{
		return $this->progress_state instanceof ConfigurableBitmaskHandler
			&& $this->progress_state->hasState(PaymentSteps::STEP_9);
	}

	/**
	 * Example: Get progress percentage.
	 */
	public function getProgressPercentage(): float
	{
		if (!$this->progress_state instanceof ConfigurableBitmaskHandler) {
			return 0.0;
		}

		$activeStates = $this->progress_state->getActiveStates();
		$totalSteps = count(PaymentSteps::cases());

		return (count($activeStates) / $totalSteps) * 100;
	}

	/**
	 * Example: Scope for orders at a specific step.
	 */
	public function scopeAtStep($query, PaymentSteps $step)
	{
		return $query->whereRaw('progress_state & ? = ?', [$step->value, $step->value]);
	}

	/**
	 * Example: Scope for completed orders.
	 */
	public function scopeCompleted($query)
	{
		return $query->whereRaw('progress_state & ? = ?', [PaymentSteps::STEP_9->value, PaymentSteps::STEP_9->value]);
	}
}
