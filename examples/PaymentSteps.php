<?php

namespace Examples;

/**
 * Example enum representing steps in a payment process.
 * Each step uses bit shifting to ensure unique bit positions.
 */
enum PaymentSteps: int
{
	case STEP_0 = 1 << 0;  // 0000000001 = 1   - Order Created
	case STEP_1 = 1 << 1;  // 0000000010 = 2   - Payment Method Selected
	case STEP_2 = 1 << 2;  // 0000000100 = 4   - Address Validated
	case STEP_3 = 1 << 3;  // 0000001000 = 8   - Payment Processed
	case STEP_4 = 1 << 4;  // 0000010000 = 16  - Inventory Reserved
	case STEP_5 = 1 << 5;  // 0000100000 = 32  - Shipping Calculated
	case STEP_6 = 1 << 6;  // 0001000000 = 64  - Order Confirmed
	case STEP_7 = 1 << 7;  // 0010000000 = 128 - Fulfillment Started
	case STEP_8 = 1 << 8;  // 0100000000 = 256 - Shipped
	case STEP_9 = 1 << 9;  // 1000000000 = 512 - Delivered

	/**
	 * Get a human-readable description of the step.
	 */
	public function description(): string
	{
		return match ($this) {
			self::STEP_0 => 'Order Created',
			self::STEP_1 => 'Payment Method Selected',
			self::STEP_2 => 'Address Validated',
			self::STEP_3 => 'Payment Processed',
			self::STEP_4 => 'Inventory Reserved',
			self::STEP_5 => 'Shipping Calculated',
			self::STEP_6 => 'Order Confirmed',
			self::STEP_7 => 'Fulfillment Started',
			self::STEP_8 => 'Shipped',
			self::STEP_9 => 'Delivered',
		};
	}
}
