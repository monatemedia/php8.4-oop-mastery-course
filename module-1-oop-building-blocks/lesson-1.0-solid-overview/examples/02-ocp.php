<?php
declare(strict_types=1);

/**
 * SOLID Example — O: Open/Closed Principle
 * ──────────────────────────────────────────
 * "Open for extension, closed for modification."
 *
 * Once a class is tested and working, you should never need to edit it
 * to add new behaviour. Instead, add new classes. The existing class
 * stays untouched — zero regression risk.
 *
 * Scenario: A discount engine for an e-commerce store.
 */

echo "╔══════════════════════════════════════════╗\n";
echo "║  O — Open/Closed Principle (OCP)        ║\n";
echo "╚══════════════════════════════════════════╝\n\n";


// ═══════════════════════════════════════════════════════════
// ❌ VIOLATION — Adding a new discount type requires editing
//               the existing DiscountCalculator class
// ═══════════════════════════════════════════════════════════
echo "── VIOLATION ──────────────────────────────────\n\n";

class BadDiscountCalculator {
    public function calculate(string $type, float $price): float {
        // Every new discount type forces you to edit THIS method.
        // That is the violation: the class is not closed for modification.
        if ($type === 'percentage') {
            return $price * 0.10; // 10% off
        } elseif ($type === 'fixed') {
            return 5.00; // R5 off
        } elseif ($type === 'bogo') {
            return $price / 2; // Buy one get one
        }
        // To add 'loyalty' discount: come back and edit this method.
        // Every edit risks breaking the existing cases.
        return 0.0;
    }
}

$calc = new BadDiscountCalculator();
echo "[BAD] Percentage discount on R200: R" . $calc->calculate('percentage', 200) . "\n";
echo "[BAD] Fixed discount on R200:      R" . $calc->calculate('fixed', 200) . "\n";
echo "[BAD] BOGO discount on R200:       R" . $calc->calculate('bogo', 200) . "\n";


// ═══════════════════════════════════════════════════════════
// ✅ FIX — Each discount type is a new class.
//          DiscountCalculator never changes.
// ═══════════════════════════════════════════════════════════
echo "\n── FIX ─────────────────────────────────────────\n\n";

// The contract: every discount strategy must implement this.
interface DiscountStrategy {
    public function calculate(float $price): float;
    public function describe(): string;
}

// Existing discount types — these never need to change.
class PercentageDiscount implements DiscountStrategy {
    public function __construct(private float $percent) {}

    public function calculate(float $price): float {
        return $price * ($this->percent / 100);
    }

    public function describe(): string {
        return "{$this->percent}% off";
    }
}

class FixedDiscount implements DiscountStrategy {
    public function __construct(private float $amount) {}

    public function calculate(float $price): float {
        return min($this->amount, $price); // Cannot discount more than the price
    }

    public function describe(): string {
        return "R{$this->amount} off";
    }
}

class BogoDiscount implements DiscountStrategy {
    public function calculate(float $price): float {
        return $price / 2;
    }

    public function describe(): string {
        return "Buy one get one (50% off)";
    }
}

// DiscountCalculator is CLOSED for modification — it will never be edited again.
class DiscountCalculator {
    public function apply(DiscountStrategy $strategy, float $price): float {
        $discount    = $strategy->calculate($price);
        $finalPrice  = $price - $discount;
        echo "[OK] {$strategy->describe()}: R{$price} → R" . round($finalPrice, 2) . " (saved R" . round($discount, 2) . ")\n";
        return $finalPrice;
    }
}

$calculator = new DiscountCalculator();
$calculator->apply(new PercentageDiscount(10), 200);
$calculator->apply(new FixedDiscount(5), 200);
$calculator->apply(new BogoDiscount(), 200);


// ═══════════════════════════════════════════════════════════
// EXTENSION — New discount added WITHOUT touching any existing code
// ═══════════════════════════════════════════════════════════
echo "\n── Extension (zero modifications to existing code) ──\n\n";

// New requirement: a loyalty discount — 15% off for members.
// No existing file is edited. We just add this new class.
class LoyaltyDiscount implements DiscountStrategy {
    private const LOYALTY_PERCENT = 15.0;

    public function calculate(float $price): float {
        return $price * (self::LOYALTY_PERCENT / 100);
    }

    public function describe(): string {
        return self::LOYALTY_PERCENT . "% loyalty member discount";
    }
}

// New requirement: a flash sale — 30% off, but only for prices above R100.
class FlashSaleDiscount implements DiscountStrategy {
    public function calculate(float $price): float {
        return $price > 100 ? $price * 0.30 : 0.0;
    }

    public function describe(): string {
        return "30% flash sale (items over R100 only)";
    }
}

// DiscountCalculator::apply() is called identically — it was never touched.
$calculator->apply(new LoyaltyDiscount(), 200);
$calculator->apply(new FlashSaleDiscount(), 200);
$calculator->apply(new FlashSaleDiscount(), 80); // Under R100, no discount

echo "\n--- Recap ---\n";
echo "OCP: Add new behaviour by adding new classes, not by editing existing ones.\n";
echo "Test: Ask 'do I need to edit a working class to add this feature?' — if yes, apply OCP.\n";
echo "Payoff: Working code stays working. New code carries all the risk, nothing else.\n";