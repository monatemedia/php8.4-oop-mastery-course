<?php
declare(strict_types=1);

/**
 * LSP Example 01 — The Violation
 * --------------------------------
 * Three real-world LSP violations, each with a different smell.
 * Run this file and read each section carefully.
 * The fixes are in example 02.
 */

echo "╔══════════════════════════════════════════════════╗\n";
echo "║  LSP Violations — What Broken Substitution Looks Like  ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";


// ═══════════════════════════════════════════════════════════
// VIOLATION 1 — The throwing override
// A subclass inherits a method it cannot support and throws.
// ═══════════════════════════════════════════════════════════
echo "── Violation 1: Throwing override ─────────────────\n\n";

class Bird {
    public function fly(): string {
        return get_class($this) . " is flying.";
    }
    public function eat(): string {
        return get_class($this) . " is eating.";
    }
}

class Eagle extends Bird {
    // Eagle can fly — no problem.
}

class Penguin extends Bird {
    public function fly(): string {
        // ❌ LSP VIOLATED: Any code that calls fly() on a Bird will explode with a Penguin.
        throw new \LogicException("Penguins cannot fly — LSP violated!");
    }
}

// Code written against Bird — perfectly reasonable expectations:
function describeBird(Bird $bird): void {
    echo $bird->eat() . "\n";
    echo $bird->fly() . "\n"; // Any Bird should be able to fly, right?
}

describeBird(new Eagle()); // ✓ Works

echo "\nNow passing a Penguin to the same function:\n";
try {
    describeBird(new Penguin()); // ✗ Crashes
} catch (\LogicException $e) {
    echo "CRASH: " . $e->getMessage() . "\n";
    echo "→ The calling code did nothing wrong. The hierarchy is lying.\n";
}


// ═══════════════════════════════════════════════════════════
// VIOLATION 2 — The silent no-op override
// Same problem, just quieter. Even more dangerous.
// ═══════════════════════════════════════════════════════════
echo "\n── Violation 2: Silent no-op override ─────────────\n\n";

class Logger {
    protected array $logs = [];

    public function log(string $message): void {
        $this->logs[] = $message;
        echo "[LOG] {$message}\n";
    }

    public function getLogs(): array {
        return $this->logs;
    }
}

class NullLogger extends Logger {
    public function log(string $message): void {
        // ❌ Silently does nothing. Postcondition violated:
        // caller expects the message to be recorded, but it is not.
        // No exception, no warning — just wrong data, silently.
    }
}

function auditAction(Logger $logger, string $action): void {
    $logger->log("Action performed: {$action}");
    // Caller reasonably expects this is recorded somewhere.
    $records = $logger->getLogs();
    echo "Audit trail has " . count($records) . " record(s).\n";
}

echo "Using real Logger:\n";
auditAction(new Logger(), "file_uploaded");

echo "\nUsing NullLogger (substituted in place of Logger):\n";
auditAction(new NullLogger(), "file_uploaded");
echo "→ 0 records. The action was lost silently. Substitution broke the postcondition.\n";


// ═══════════════════════════════════════════════════════════
// VIOLATION 3 — The instanceof guard
// Caller code has to check the type because subtypes behave differently.
// This is the symptom, not the cause — but it always signals a violation.
// ═══════════════════════════════════════════════════════════
echo "\n── Violation 3: instanceof guard in caller code ───\n\n";

class PaymentGateway {
    public function charge(float $amount): string {
        return "Charged R{$amount}";
    }
    public function refund(float $amount): string {
        return "Refunded R{$amount}";
    }
}

class ModernGateway extends PaymentGateway {
    public function charge(float $amount): string {
        return "[MODERN] Charged R{$amount}";
    }
    public function refund(float $amount): string {
        return "[MODERN] Refunded R{$amount}";
    }
}

class LegacyGateway extends PaymentGateway {
    public function charge(float $amount): string {
        return "[LEGACY] Charged R{$amount}";
    }
    public function refund(float $amount): string {
        // ❌ Legacy system does not support refunds
        throw new \RuntimeException("Legacy system: refunds must be processed manually!");
    }
}

// ❌ The caller is forced to add instanceof guards because LegacyGateway
// cannot be safely substituted for PaymentGateway.
function processRefund(PaymentGateway $gateway, float $amount): void {
    // This guard is a RED FLAG — it means the hierarchy is broken.
    if ($gateway instanceof LegacyGateway) {
        echo "⚠ Legacy gateway detected — skipping refund, manual process required.\n";
        return;
    }
    echo $gateway->refund($amount) . "\n";
}

processRefund(new ModernGateway(), 299.00);
processRefund(new LegacyGateway(), 299.00);
echo "→ The instanceof guard is a symptom. LegacyGateway cannot truly substitute PaymentGateway.\n";


// ═══════════════════════════════════════════════════════════
// VIOLATION 4 — Strengthened precondition
// Child requires more from the caller than the parent does.
// ═══════════════════════════════════════════════════════════
echo "\n── Violation 4: Strengthened precondition ─────────\n\n";

class Discount {
    public function apply(float $price): float {
        if ($price < 0) throw new \InvalidArgumentException("Price cannot be negative");
        return $price * 0.9; // 10% off
    }
}

class PremiumDiscount extends Discount {
    public function apply(float $price): float {
        // ❌ Child adds a NEW precondition: price must be >= 100
        // Parent only required price >= 0 — this is STRICTER.
        if ($price < 100) {
            throw new \InvalidArgumentException("Premium discount requires price >= R100");
        }
        return $price * 0.8; // 20% off
    }
}

function applyDiscountToBasket(Discount $discount, array $prices): void {
    foreach ($prices as $price) {
        try {
            $result = $discount->apply($price);
            echo "  R{$price} → R" . round($result, 2) . "\n";
        } catch (\InvalidArgumentException $e) {
            echo "  R{$price} → ERROR: " . $e->getMessage() . "\n";
        }
    }
}

$prices = [50.00, 150.00, 75.00, 200.00];

echo "Using base Discount:\n";
applyDiscountToBasket(new Discount(), $prices);

echo "\nUsing PremiumDiscount (substituted):\n";
applyDiscountToBasket(new PremiumDiscount(), $prices);
echo "→ R50 and R75 fail. Code written for Discount now breaks with PremiumDiscount.\n";


echo "\n═══ SUMMARY OF VIOLATIONS ═══\n";
echo "1. Throwing override:        Subtype throws on a method the parent supports.\n";
echo "2. Silent no-op:             Subtype delivers nothing where parent delivered something.\n";
echo "3. instanceof guards needed: Caller must check type because substitution is unsafe.\n";
echo "4. Strengthened precondition: Subtype requires more from caller than parent did.\n\n";
echo "All four are fixed in example 02.\n";