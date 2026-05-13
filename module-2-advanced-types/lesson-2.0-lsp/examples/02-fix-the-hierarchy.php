<?php
declare(strict_types=1);

/**
 * LSP Example 02 — Fix the Hierarchy
 * -------------------------------------
 * Each violation from example 01, restructured so substitution is safe.
 * The fix pattern is always the same:
 *   → Model the hierarchy to match what classes can ACTUALLY do.
 *   → Use ISP: split contracts so classes only sign what they can honour.
 */

echo "╔══════════════════════════════════════════════════╗\n";
echo "║  LSP Fixes — Safe Substitution By Design        ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";


// ═══════════════════════════════════════════════════════════
// FIX 1 — Bird hierarchy: separate flying from non-flying birds
// ═══════════════════════════════════════════════════════════
echo "── Fix 1: Bird hierarchy ───────────────────────────\n\n";

// Every bird can eat and move — that is the base contract all birds sign.
interface Bird {
    public function eat(): string;
    public function move(): string;
}

// Only birds that can actually fly sign this additional contract.
interface FlyingBird extends Bird {
    public function fly(): string;
}

class Eagle implements FlyingBird {
    public function eat(): string  { return "Eagle eating fish."; }
    public function move(): string { return $this->fly(); }
    public function fly(): string  { return "Eagle soaring on thermals."; }
}

class Parrot implements FlyingBird {
    public function eat(): string  { return "Parrot eating seeds."; }
    public function move(): string { return $this->fly(); }
    public function fly(): string  { return "Parrot flapping between perches."; }
}

class Penguin implements Bird {
    public function eat(): string  { return "Penguin eating squid."; }
    public function move(): string { return "Penguin waddling on ice."; }
    // No fly() — Penguin never claims it can fly. Contract is honest.
}

class Ostrich implements Bird {
    public function eat(): string  { return "Ostrich eating seeds."; }
    public function move(): string { return "Ostrich sprinting at 70 km/h."; }
}

// Safe for ALL birds — no instanceof guards needed, no exceptions possible.
function describeBird(Bird $bird): void {
    echo "  " . $bird->eat() . "\n";
    echo "  " . $bird->move() . "\n";
}

// Only for birds that actually fly — type system prevents the wrong type being passed.
function sendOnAirMission(FlyingBird $bird): void {
    echo "  [AIR MISSION] " . $bird->fly() . "\n";
}

echo "All birds described safely:\n";
foreach ([new Eagle(), new Parrot(), new Penguin(), new Ostrich()] as $bird) {
    echo get_class($bird) . ":\n";
    describeBird($bird);
}

echo "\nAir missions (PHP prevents passing Penguin here):\n";
sendOnAirMission(new Eagle());
sendOnAirMission(new Parrot());
// sendOnAirMission(new Penguin()); // ← PHP type error — correctly blocked


// ═══════════════════════════════════════════════════════════
// FIX 2 — Logger: Null Object Pattern done correctly
// ═══════════════════════════════════════════════════════════
echo "\n── Fix 2: Logger / Null Object Pattern ─────────────\n\n";

// The contract: logging always records messages.
// If you want a "do nothing" logger, it must honestly implement the contract
// in a way that does not break callers — the NullLogger should not silently
// drop records that callers depend on reading back.
//
// Key insight: a correct NullLogger still records messages (in memory),
// it just does not OUTPUT them anywhere (no console, no file, no network).
// The postcondition (message is stored) is still honoured.

interface Logger {
    public function log(string $message): void;
    public function getLogs(): array;
}

class ConsoleLogger implements Logger {
    private array $logs = [];

    public function log(string $message): void {
        $this->logs[] = $message;
        echo "[CONSOLE] {$message}\n";
    }

    public function getLogs(): array {
        return $this->logs;
    }
}

class FileLogger implements Logger {
    private array $logs = [];

    public function log(string $message): void {
        $this->logs[] = $message;
        // Real implementation would write to a file.
        echo "[FILE] Written to app.log: {$message}\n";
    }

    public function getLogs(): array {
        return $this->logs;
    }
}

// ✅ Correct NullLogger: stores messages (postcondition honoured),
// just does not output them anywhere (silent). Safe to substitute for Logger.
class NullLogger implements Logger {
    private array $logs = [];

    public function log(string $message): void {
        $this->logs[] = $message; // Stored — postcondition met.
        // No output. That is the "null" part — no side effects.
    }

    public function getLogs(): array {
        return $this->logs; // Readable — callers can always inspect the log.
    }
}

function auditAction(Logger $logger, string $action): void {
    $logger->log("Action performed: {$action}");
    $count = count($logger->getLogs());
    echo "Audit trail has {$count} record(s). [Last: {$logger->getLogs()[$count - 1]}]\n";
}

echo "ConsoleLogger:\n";
auditAction(new ConsoleLogger(), "user_login");

echo "\nFileLogger:\n";
auditAction(new FileLogger(), "file_uploaded");

echo "\nNullLogger (no output, but postcondition honoured):\n";
auditAction(new NullLogger(), "background_job");
echo "→ 1 record stored — substitution is safe.\n";


// ═══════════════════════════════════════════════════════════
// FIX 3 — Payment gateway: separate refundable from non-refundable
// ═══════════════════════════════════════════════════════════
echo "\n── Fix 3: Payment gateway hierarchy ───────────────\n\n";

// Split the contract: not every gateway supports refunds.
interface ChargeableGateway {
    public function charge(float $amount): string;
}

interface RefundableGateway extends ChargeableGateway {
    public function refund(float $amount): string;
}

class ModernGateway implements RefundableGateway {
    public function charge(float $amount): string {
        return "[MODERN] Charged R{$amount}";
    }
    public function refund(float $amount): string {
        return "[MODERN] Refunded R{$amount}";
    }
}

class LegacyGateway implements ChargeableGateway {
    public function charge(float $amount): string {
        return "[LEGACY] Charged R{$amount}";
    }
    // No refund() — LegacyGateway honestly only claims to be Chargeable.
}

// Charging works with both gateways — safe substitution.
function chargeCustomer(ChargeableGateway $gateway, float $amount): void {
    echo $gateway->charge($amount) . "\n";
}

// Refunding only accepts RefundableGateway — LegacyGateway cannot be passed here.
// No instanceof guards. No runtime surprises. PHP prevents the mistake at the call site.
function refundCustomer(RefundableGateway $gateway, float $amount): void {
    echo $gateway->refund($amount) . "\n";
}

echo "Charging (both gateways):\n";
chargeCustomer(new ModernGateway(), 499.00);
chargeCustomer(new LegacyGateway(), 499.00);

echo "\nRefunding (only RefundableGateway):\n";
refundCustomer(new ModernGateway(), 499.00);
// refundCustomer(new LegacyGateway(), 499.00); // ← PHP type error — correctly blocked
echo "(LegacyGateway cannot be passed to refundCustomer — PHP prevents it)\n";


// ═══════════════════════════════════════════════════════════
// FIX 4 — Discount: honour the parent's precondition
// ═══════════════════════════════════════════════════════════
echo "\n── Fix 4: Discount preconditions ──────────────────\n\n";

interface DiscountStrategy {
    /**
     * Apply the discount to the given price.
     * Precondition:  $price >= 0
     * Postcondition: return value >= 0 and <= $price
     */
    public function apply(float $price): float;
    public function isApplicable(float $price): bool;
}

class StandardDiscount implements DiscountStrategy {
    public function isApplicable(float $price): bool {
        return $price >= 0;
    }

    public function apply(float $price): float {
        return $price * 0.9; // 10% off — works for any non-negative price
    }
}

class PremiumDiscount implements DiscountStrategy {
    private const MIN_PRICE = 100.0;

    public function isApplicable(float $price): bool {
        // ✅ The eligibility check lives HERE — not as a thrown exception inside apply().
        // apply() only gets called when the caller already knows it is applicable.
        return $price >= self::MIN_PRICE;
    }

    public function apply(float $price): float {
        // Precondition inherited from interface: $price >= 0. We do not strengthen it.
        // The minimum-price constraint is a business rule communicated via isApplicable().
        return $price * 0.8; // 20% off
    }
}

function applyDiscountToBasket(DiscountStrategy $discount, array $prices): void {
    foreach ($prices as $price) {
        if ($discount->isApplicable($price)) {
            $result = $discount->apply($price);
            echo "  R{$price} → R" . round($result, 2) . " ✓\n";
        } else {
            echo "  R{$price} → not applicable for " . get_class($discount) . "\n";
        }
    }
}

$prices = [50.00, 150.00, 75.00, 200.00];

echo "StandardDiscount:\n";
applyDiscountToBasket(new StandardDiscount(), $prices);

echo "\nPremiumDiscount:\n";
applyDiscountToBasket(new PremiumDiscount(), $prices);
echo "→ No exceptions. Caller uses isApplicable() to check eligibility first.\n";

echo "\n--- Recap ---\n";
echo "Fix 1: Split Bird into Bird + FlyingBird — only flying birds sign the fly() contract.\n";
echo "Fix 2: NullLogger stores messages (postcondition) but skips output (null behaviour).\n";
echo "Fix 3: Split ChargeableGateway + RefundableGateway — legacy only signs what it supports.\n";
echo "Fix 4: Move eligibility logic to isApplicable() — never strengthen apply()'s precondition.\n";