<?php
declare(strict_types=1);

/**
 * Example 04 — Enums and Interfaces
 * ------------------------------------
 * PHP 8.1+
 *
 * Enums can implement interfaces. This means enum cases can be used
 * anywhere the interface is expected as a type hint.
 *
 * This is powerful because it lets enums participate in polymorphic code
 * that was designed for classes — without any class inheritance.
 *
 * Common patterns:
 *   - HasLabel interface: all enum cases produce a display string
 *   - Comparable: all cases can be compared
 *   - Coloured, Describable, Serialisable — any domain concept
 */

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  Enums and Interfaces (PHP 8.1+)                   ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 1 — A basic interface implemented by an enum
// ─────────────────────────────────────────────────────────────────────────────

echo "── Part 1: Basic interface implementation ───────────\n\n";

interface HasLabel {
    public function label(): string;
}

interface HasDescription {
    public function description(): string;
}

// Enum implements both interfaces
enum Priority: int implements HasLabel, HasDescription {
    case Low      = 1;
    case Medium   = 5;
    case High     = 10;
    case Critical = 99;

    public function label(): string {
        return match($this) {
            self::Low      => '🟢 Low',
            self::Medium   => '🟡 Medium',
            self::High     => '🔴 High',
            self::Critical => '🚨 Critical',
        };
    }

    public function description(): string {
        return match($this) {
            self::Low      => 'Can be addressed in the next sprint.',
            self::Medium   => 'Should be addressed this sprint.',
            self::High     => 'Must be addressed this week.',
            self::Critical => 'Drop everything — address immediately.',
        };
    }
}

// Type-safe functions using the interface — accepts ANY HasLabel, including enums
function printLabel(HasLabel $item): void {
    echo "  " . $item->label() . "\n";
}

function printDetails(HasLabel&HasDescription $item): void {
    echo "  " . $item->label() . ": " . $item->description() . "\n";
}

echo "Labels:\n";
foreach (Priority::cases() as $p) {
    printLabel($p);
}

echo "\nDetails:\n";
foreach (Priority::cases() as $p) {
    printDetails($p);
}


// ─────────────────────────────────────────────────────────────────────────────
// PART 2 — Mixing classes and enums via a shared interface
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 2: Enums and classes sharing an interface ───\n\n";

interface Displayable {
    public function displayName(): string;
    public function badgeColour(): string;
}

enum UserRole: string implements Displayable {
    case Guest     = 'guest';
    case Member    = 'member';
    case Admin     = 'admin';

    public function displayName(): string {
        return match($this) {
            self::Guest  => 'Guest',
            self::Member => 'Member',
            self::Admin  => 'Administrator',
        };
    }

    public function badgeColour(): string {
        return match($this) {
            self::Guest  => 'grey',
            self::Member => 'blue',
            self::Admin  => 'gold',
        };
    }
}

// A class that also implements Displayable
class CustomBadge implements Displayable {
    public function __construct(
        private string $name,
        private string $colour
    ) {}

    public function displayName(): string  { return $this->name; }
    public function badgeColour(): string  { return $this->colour; }
}

// This function accepts BOTH enum cases AND class instances
function renderBadge(Displayable $item): void {
    echo "  [{$item->badgeColour()}] {$item->displayName()}\n";
}

echo "Badges (mix of enum cases and class instances):\n";
renderBadge(UserRole::Guest);
renderBadge(UserRole::Member);
renderBadge(UserRole::Admin);
renderBadge(new CustomBadge('Verified', 'green'));
renderBadge(new CustomBadge('Partner',  'purple'));


// ─────────────────────────────────────────────────────────────────────────────
// PART 3 — A richer real-world example: payment methods
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 3: Payment methods with interface ───────────\n\n";

interface PaymentContract {
    public function label(): string;
    public function processingFeePercent(): float;
    public function isAvailableInRegion(string $region): bool;
    public function supportsRefunds(): bool;
}

enum PaymentMethod: string implements PaymentContract {
    case CreditCard  = 'credit_card';
    case DebitCard   = 'debit_card';
    case EFT         = 'eft';
    case PayFast     = 'payfast';
    case Crypto      = 'crypto';

    public function label(): string {
        return match($this) {
            self::CreditCard => 'Credit Card',
            self::DebitCard  => 'Debit Card',
            self::EFT        => 'EFT / Bank Transfer',
            self::PayFast    => 'PayFast',
            self::Crypto     => 'Cryptocurrency',
        };
    }

    public function processingFeePercent(): float {
        return match($this) {
            self::CreditCard => 3.5,
            self::DebitCard  => 1.5,
            self::EFT        => 0.0,
            self::PayFast    => 2.9,
            self::Crypto     => 1.0,
        };
    }

    public function isAvailableInRegion(string $region): bool {
        return match($this) {
            self::CreditCard => true,
            self::DebitCard  => true,
            self::EFT        => in_array($region, ['ZA', 'UK', 'EU'], true),
            self::PayFast    => $region === 'ZA',
            self::Crypto     => in_array($region, ['ZA', 'US', 'EU', 'UK'], true),
        };
    }

    public function supportsRefunds(): bool {
        return match($this) {
            self::CreditCard,
            self::DebitCard,
            self::PayFast   => true,
            self::EFT,
            self::Crypto    => false,
        };
    }

    public function calculateFee(float $amount): float {
        return round($amount * ($this->processingFeePercent() / 100), 2);
    }
}

// Type-safe checkout function using the interface
function checkout(PaymentContract $method, float $amount, string $region): void {
    if (!$method->isAvailableInRegion($region)) {
        echo "  ✗ {$method->label()} not available in {$region}\n";
        return;
    }
    $fee   = $method->processingFeePercent();
    $total = $amount + ($amount * $fee / 100);
    echo "  ✓ {$method->label()}: "
       . "R" . number_format($amount, 2)
       . " + {$fee}% fee = R" . number_format($total, 2)
       . ($method->supportsRefunds() ? " [refundable]" : " [non-refundable]")
       . "\n";
}

echo "Checkout options for ZA region, R500:\n";
foreach (PaymentMethod::cases() as $method) {
    checkout($method, 500.00, 'ZA');
}

echo "\nCheckout options for US region, R500:\n";
foreach (PaymentMethod::cases() as $method) {
    checkout($method, 500.00, 'US');
}


// ─────────────────────────────────────────────────────────────────────────────
// PART 4 — interface instanceof checks with enums
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 4: instanceof with enum interfaces ──────────\n\n";

$case = PaymentMethod::CreditCard;

var_dump($case instanceof PaymentMethod);    // true
var_dump($case instanceof PaymentContract);  // true — because enum implements it

echo "\ncases() with interface filtering:\n";
$refundable = array_filter(
    PaymentMethod::cases(),
    fn(PaymentMethod $m) => $m->supportsRefunds()
);

foreach ($refundable as $m) {
    echo "  {$m->label()} supports refunds\n";
}

echo "\n--- Recap ---\n";
echo "Enums implement interfaces using 'implements', same syntax as classes.\n";
echo "Enum cases satisfy interface type hints — pass them wherever the interface is expected.\n";
echo "Enum cases are instanceof both the enum AND any interface it implements.\n";
echo "Mix enums and classes: a function typed against an interface accepts both.\n";