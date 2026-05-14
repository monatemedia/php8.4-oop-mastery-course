<?php
declare(strict_types=1);

/**
 * Example 01 — Scalar Types and strict_types=1
 * ----------------------------------------------
 * PHP has two modes for scalar type checking:
 *   - Coercive (default): PHP silently converts compatible values
 *   - Strict (declare strict_types=1): PHP throws TypeError for wrong types
 *
 * This file runs in STRICT mode. Read each section and the comments carefully.
 * The "coercive mode" sections are simulated with a workaround so you can see
 * both behaviours in one file without switching modes.
 */

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  Scalar Types and strict_types=1                   ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";


// ─────────────────────────────────────────────────────────────────────────────
// The four scalar types
// ─────────────────────────────────────────────────────────────────────────────

echo "── The four scalar types ────────────────────────────\n\n";

function addInts(int $a, int $b): int {
    return $a + $b;
}

function formatPrice(float $amount, string $currency): string {
    return $currency . number_format($amount, 2);
}

function isEligible(bool $hasAccount, int $age): bool {
    return $hasAccount && $age >= 18;
}

echo addInts(3, 4)                        . "\n"; // 7
echo formatPrice(1499.9, 'R')             . "\n"; // R1,499.90
echo (isEligible(true, 25) ? 'YES' : 'NO') . "\n"; // YES
echo (isEligible(false, 25) ? 'YES' : 'NO') . "\n"; // NO


// ─────────────────────────────────────────────────────────────────────────────
// Strict mode in action — TypeError on wrong types
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Strict mode: TypeError on wrong scalar types ─────\n\n";

function strictDouble(int $n): int {
    return $n * 2;
}

// ✅ Correct usage
echo strictDouble(5) . "\n"; // 10

// ❌ Wrong type — in strict mode, PHP throws TypeError
$tests = [
    fn() => strictDouble("5"),    // string instead of int
    fn() => strictDouble(2.9),    // float instead of int
    fn() => strictDouble(true),   // bool instead of int
];

foreach ($tests as $i => $test) {
    try {
        $result = $test();
        echo "Test " . ($i + 1) . ": returned {$result} (no error)\n";
    } catch (\TypeError $e) {
        echo "Test " . ($i + 1) . " TypeError: " . $e->getMessage() . "\n";
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// What coercive mode would have done (demonstration using settype())
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── What coercive mode silently does ─────────────────\n\n";

echo "Coercive conversions PHP performs silently (without strict_types):\n";
echo "  '5' → int: ";   $v = "5";   settype($v, 'integer'); echo var_export($v, true) . "\n";
echo "  2.9 → int: ";   $v = 2.9;   settype($v, 'integer'); echo var_export($v, true) . " (truncated, not rounded!)\n";
echo "  true → int: ";  $v = true;  settype($v, 'integer'); echo var_export($v, true) . "\n";
echo "  '' → bool: ";   $v = '';    settype($v, 'boolean'); echo var_export($v, true) . "\n";
echo "  '0' → bool: ";  $v = '0';   settype($v, 'boolean'); echo var_export($v, true) . "\n";
echo "  '1' → bool: ";  $v = '1';   settype($v, 'boolean'); echo var_export($v, true) . "\n";
echo "  'five' → int: ";$v = 'five';settype($v, 'integer'); echo var_export($v, true) . " ← silent bug!\n";


// ─────────────────────────────────────────────────────────────────────────────
// Return type enforcement
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Return type enforcement ──────────────────────────\n\n";

function calculateTax(float $amount, float $rate): float {
    return $amount * $rate; // float * float = float ✓
}

function getUsername(int $id): string {
    $names = [1 => 'Alice', 2 => 'Bob'];
    return $names[$id] ?? 'unknown'; // always returns string ✓
}

echo "Tax on R1000 at 15%: R" . calculateTax(1000.00, 0.15) . "\n";
echo "Username 1: " . getUsername(1) . "\n";
echo "Username 99: " . getUsername(99) . "\n";

// What happens when a return type is violated?
function brokenReturn(): int {
    try {
        // This would cause TypeError:
        // return "not an int";
        return 42; // Correct — return an int
    } catch (\TypeError $e) {
        echo "Return TypeError: " . $e->getMessage() . "\n";
        return 0;
    }
}

echo "brokenReturn(): " . brokenReturn() . "\n";

// Demonstrate a real return TypeError
function badReturn(): int {
    return 42; // Fine — demonstrating that return types ARE enforced
}

try {
    // Simulate what would happen with a wrong return type:
    $fn = function(): int { return "oops"; }; // Closure with wrong return
    $fn();
} catch (\TypeError $e) {
    echo "Return TypeError caught: " . $e->getMessage() . "\n";
}


// ─────────────────────────────────────────────────────────────────────────────
// strict_types only affects the CALLING file
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── strict_types affects the calling file only ───────\n\n";

echo "This file has declare(strict_types=1).\n";
echo "Therefore all calls FROM this file to any function are strict.\n\n";
echo "If another file WITHOUT strict_types=1 called strictDouble('5'),\n";
echo "PHP would coerce '5' to 5 silently — no TypeError.\n";
echo "strict_types is a per-file opt-in, not a global setting.\n";


// ─────────────────────────────────────────────────────────────────────────────
// A realistic strict-typed class to show the pattern in context
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Realistic example: Money value object ────────────\n\n";

class Money {
    public function __construct(
        private int    $amountCents,   // Store as cents to avoid float precision issues
        private string $currency
    ) {
        if ($amountCents < 0) {
            throw new \InvalidArgumentException("Amount cannot be negative.");
        }
        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException("Currency must be a 3-letter ISO code.");
        }
    }

    public function getAmountCents(): int    { return $this->amountCents; }
    public function getCurrency(): string    { return strtoupper($this->currency); }
    public function getAmount(): float       { return $this->amountCents / 100; }

    public function add(Money $other): Money {
        if ($other->currency !== $this->currency) {
            throw new \LogicException("Cannot add different currencies.");
        }
        return new Money($this->amountCents + $other->amountCents, $this->currency);
    }

    public function format(): string {
        return $this->getCurrency() . ' ' . number_format($this->getAmount(), 2);
    }
}

$price    = new Money(29999, 'ZAR'); // R299.99
$shipping = new Money(5000,  'ZAR'); // R50.00
$total    = $price->add($shipping);

echo "Price:    " . $price->format() . "\n";
echo "Shipping: " . $shipping->format() . "\n";
echo "Total:    " . $total->format() . "\n";

// Try wrong currency
try {
    $usd = new Money(1000, 'USD');
    $price->add($usd);
} catch (\LogicException $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

// Try wrong type for amountCents
try {
    new Money("free", 'ZAR'); // TypeError — string instead of int
} catch (\TypeError $e) {
    echo "TypeError: " . $e->getMessage() . "\n";
}

echo "\n--- Recap ---\n";
echo "declare(strict_types=1): always the FIRST statement, disables silent coercion.\n";
echo "Scalar types: int, float, string, bool — enforced strictly in strict mode.\n";
echo "Return types: declared after the closing ): — PHP enforces them on return.\n";
echo "strict_types affects the calling file only — not the function definition file.\n";