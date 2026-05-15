<?php
declare(strict_types=1);

/**
 * Example 03 — Enum Methods and Constants
 * -----------------------------------------
 * PHP 8.1+
 *
 * Enums can contain:
 *   - Regular methods (instance) — $this refers to the current case
 *   - Static methods             — useful for factory helpers
 *   - Constants                  — fixed values shared across all cases
 *
 * This makes enums genuinely behavioural — not just labelled values.
 * A well-designed enum contains everything you need to know about that
 * concept, co-located in one place.
 */

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  Enum Methods and Constants (PHP 8.1+)             ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 1 — Instance methods
// $this inside an enum method refers to the current case
// ─────────────────────────────────────────────────────────────────────────────

echo "── Part 1: Instance methods ─────────────────────────\n\n";

enum Suit: string {
    case Hearts   = 'H';
    case Diamonds = 'D';
    case Clubs    = 'C';
    case Spades   = 'S';

    // Instance method — $this is the current case
    public function label(): string {
        return match($this) {
            Suit::Hearts   => '♥ Hearts',
            Suit::Diamonds => '♦ Diamonds',
            Suit::Clubs    => '♣ Clubs',
            Suit::Spades   => '♠ Spades',
        };
    }

    public function colour(): string {
        return match($this) {
            Suit::Hearts, Suit::Diamonds => 'red',
            Suit::Clubs,  Suit::Spades   => 'black',
        };
    }

    public function isRed(): bool   { return $this->colour() === 'red'; }
    public function isBlack(): bool { return $this->colour() === 'black'; }

    public function symbol(): string {
        return match($this) {
            Suit::Hearts   => '♥',
            Suit::Diamonds => '♦',
            Suit::Clubs    => '♣',
            Suit::Spades   => '♠',
        };
    }
}

foreach (Suit::cases() as $suit) {
    echo "  {$suit->label()} [{$suit->colour()}] symbol={$suit->symbol()}"
       . " value={$suit->value}\n";
}

echo "\nIs Hearts red?  " . (Suit::Hearts->isRed()    ? 'YES' : 'NO') . "\n";
echo "Is Spades red?  " . (Suit::Spades->isRed()    ? 'YES' : 'NO') . "\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 2 — Constants in enums
// Constants are shared — not per-case
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 2: Constants ────────────────────────────────\n\n";

enum HttpMethod: string {
    case Get    = 'GET';
    case Post   = 'POST';
    case Put    = 'PUT';
    case Patch  = 'PATCH';
    case Delete = 'DELETE';
    case Head   = 'HEAD';
    case Options = 'OPTIONS';

    // Constants — shared across all cases, not per-case
    const SAFE_METHODS    = [self::Get, self::Head, self::Options];
    const MUTABLE_METHODS = [self::Post, self::Put, self::Patch, self::Delete];
    const DEFAULT         = self::Get;

    public function isSafe(): bool {
        return in_array($this, self::SAFE_METHODS, true);
    }

    public function isMutable(): bool {
        return in_array($this, self::MUTABLE_METHODS, true);
    }

    public function requiresBody(): bool {
        return match($this) {
            self::Post, self::Put, self::Patch => true,
            default                            => false,
        };
    }
}

foreach (HttpMethod::cases() as $method) {
    $safe    = $method->isSafe()       ? '✓ safe'     : '✗ safe';
    $body    = $method->requiresBody() ? '✓ body'     : '✗ body';
    echo "  {$method->value}: {$safe} | {$body}\n";
}

echo "\nDefault method: " . HttpMethod::DEFAULT->value . "\n";
echo "Safe methods:   " . implode(', ', array_map(fn($m) => $m->value, HttpMethod::SAFE_METHODS)) . "\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 3 — Static methods
// Useful for factory helpers, filtering, and aggregation
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 3: Static methods ───────────────────────────\n\n";

enum Currency: string {
    case ZAR = 'ZAR';
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case JPY = 'JPY';

    // Constants
    const BASE = self::USD;

    // Static factory — create from an ISO code, case-insensitive
    public static function fromCode(string $code): self {
        return self::from(strtoupper(trim($code)));
    }

    // Static factory — safe version
    public static function tryFromCode(string $code): ?self {
        return self::tryFrom(strtoupper(trim($code)));
    }

    // Static filter — only the major currencies
    /** @return self[] */
    public static function major(): array {
        return array_filter(
            self::cases(),
            fn(self $c) => in_array($c, [self::USD, self::EUR, self::GBP], true)
        );
    }

    // Instance method — symbol for display
    public function symbol(): string {
        return match($this) {
            self::ZAR => 'R',
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
            self::JPY => '¥',
        };
    }

    // Instance method — decimal places used
    public function decimalPlaces(): int {
        return match($this) {
            self::JPY => 0,   // Yen has no decimal places
            default   => 2,
        };
    }

    public function format(float $amount): string {
        return $this->symbol() . number_format($amount, $this->decimalPlaces());
    }
}

echo "Currency formatting:\n";
echo "  " . Currency::ZAR->format(1499.99) . "\n";
echo "  " . Currency::USD->format(99.95) . "\n";
echo "  " . Currency::JPY->format(5000) . "\n";

echo "\nStatic factory fromCode():\n";
$eur = Currency::fromCode('eur'); // Case-insensitive
echo "  fromCode('eur') → {$eur->name} ({$eur->symbol()})\n";

echo "\nSafe tryFromCode():\n";
$result = Currency::tryFromCode('xyz');
echo "  tryFromCode('xyz') → " . ($result ? $result->name : 'null') . "\n";

echo "\nMajor currencies:\n";
foreach (Currency::major() as $c) {
    echo "  {$c->name} ({$c->symbol()})\n";
}

echo "\nBase currency: " . Currency::BASE->name . "\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 4 — A rich enum: all features together
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 4: A rich real-world enum ───────────────────\n\n";

enum UserRole: string {
    case Guest     = 'guest';
    case Member    = 'member';
    case Moderator = 'moderator';
    case Admin     = 'admin';
    case SuperAdmin = 'super_admin';

    // Constants
    const DEFAULT     = self::Guest;
    const STAFF_ROLES = [self::Moderator, self::Admin, self::SuperAdmin];

    // Privilege level — higher = more access
    public function level(): int {
        return match($this) {
            self::Guest      => 0,
            self::Member     => 1,
            self::Moderator  => 2,
            self::Admin      => 3,
            self::SuperAdmin => 4,
        };
    }

    public function label(): string {
        return match($this) {
            self::Guest      => 'Guest User',
            self::Member     => 'Registered Member',
            self::Moderator  => 'Community Moderator',
            self::Admin      => 'Administrator',
            self::SuperAdmin => 'Super Administrator',
        };
    }

    // Can this role perform an action that requires a minimum role?
    public function canDo(self $minimumRequired): bool {
        return $this->level() >= $minimumRequired->level();
    }

    public function isStaff(): bool {
        return in_array($this, self::STAFF_ROLES, true);
    }

    public static function fromLevel(int $level): self {
        foreach (self::cases() as $case) {
            if ($case->level() === $level) return $case;
        }
        throw new \ValueError("No role with level {$level}");
    }
}

// Demonstrate role-based access
$currentUser = UserRole::Member;
$actions = [
    ['label' => 'View public content', 'requires' => UserRole::Guest],
    ['label' => 'Post a comment',      'requires' => UserRole::Member],
    ['label' => 'Remove comments',     'requires' => UserRole::Moderator],
    ['label' => 'Ban users',           'requires' => UserRole::Admin],
    ['label' => 'Manage all admins',   'requires' => UserRole::SuperAdmin],
];

echo "User role: {$currentUser->label()} (level {$currentUser->level()})\n\n";
foreach ($actions as $action) {
    $allowed = $currentUser->canDo($action['requires']) ? '✓' : '✗';
    $req     = $action['requires']->name;
    echo "  {$allowed} {$action['label']} (requires {$req})\n";
}

echo "\nIs staff? " . ($currentUser->isStaff() ? 'YES' : 'NO') . "\n";
echo "Role from level 3: " . UserRole::fromLevel(3)->name . "\n";

echo "\n--- Recap ---\n";
echo "Instance methods: \$this is the current case — use match(\$this) to vary by case.\n";
echo "Constants: shared values, not per-case — access via EnumName::CONST or self::CONST.\n";
echo "Static methods: class-level operations — factories, filters, aggregations.\n";
echo "Rule: put behaviour IN the enum that belongs to the concept it models.\n";