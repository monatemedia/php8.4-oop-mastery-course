<?php
declare(strict_types=1); // Task 1 — must be the very first statement

/**
 * CHALLENGE SOLUTION — Lesson 2.1: Type Hinting & Return Types
 * ─────────────────────────────────────────────────────────────
 * ⚠️  Only open this file after completing starter.php yourself.
 *
 * Compare your solution against these key points:
 *   1. declare(strict_types=1) is the FIRST line
 *   2. Every parameter and return type is declared
 *   3. Money::add/subtract use `static` not `self`
 *   4. Discount::apply always returns Money
 *   5. OrderProcessor::process handles null discount
 *   6. setQuantity throws for quantity < 1
 *   7. Discount validates rate range in constructor
 *   8. Money callsites use integer literals, not strings
 */


// ─────────────────────────────────────────────────────────────────────────────
// Money — fully typed
// ─────────────────────────────────────────────────────────────────────────────

class Money {
    public function __construct(
        private int    $amountCents,  // Task 2: int not untyped
        private string $currency
    ) {
        if ($amountCents < 0) {
            throw new \InvalidArgumentException("Amount cannot be negative.");
        }
    }

    public function getAmountCents(): int    { return $this->amountCents; }
    public function getCurrency(): string    { return strtoupper($this->currency); }
    public function getAmount(): float       { return $this->amountCents / 100; }

    // static — so subclasses that call add() get their own type back (LSP + covariance)
    public function add(Money $other): static {
        if ($other->getCurrency() !== $this->getCurrency()) {
            throw new \LogicException("Cannot add different currencies.");
        }
        return new static($this->amountCents + $other->getAmountCents(), $this->currency);
    }

    public function subtract(Money $other): static {
        if ($other->getCurrency() !== $this->getCurrency()) {
            throw new \LogicException("Cannot subtract different currencies.");
        }
        $result = $this->amountCents - $other->getAmountCents();
        return new static(max(0, $result), $this->currency);
    }

    public function format(): string {
        return 'R' . number_format($this->getAmount(), 2);
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// OrderLine — fully typed with quantity guard
// ─────────────────────────────────────────────────────────────────────────────

class OrderLine {
    public function __construct(
        private string $sku,
        private string $description,
        private Money  $unitPrice,    // Task 3: typed as Money
        private int    $quantity
    ) {}

    public function setQuantity(int $quantity): void {
        // Task 3: guard — quantity must be at least 1
        if ($quantity < 1) {
            throw new \InvalidArgumentException(
                "Quantity must be at least 1, got {$quantity}"
            );
        }
        $this->quantity = $quantity;
    }

    public function getSku(): string         { return $this->sku; }
    public function getDescription(): string { return $this->description; }
    public function getUnitPrice(): Money     { return $this->unitPrice; }
    public function getQuantity(): int        { return $this->quantity; }

    public function getSubtotal(): Money {
        $cents = $this->unitPrice->getAmountCents() * $this->quantity;
        return new Money($cents, $this->unitPrice->getCurrency());
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Order — fully typed
// ─────────────────────────────────────────────────────────────────────────────

class Order {
    /** @var OrderLine[] */
    private array  $lines  = [];
    private string $status = 'pending';

    public function __construct(private string $id) {}

    public function addLine(OrderLine $line): void {
        $this->lines[] = $line;
    }

    /** @return OrderLine[] */
    public function getLines(): array { return $this->lines; }

    public function getTotal(): Money {
        $total = new Money(0, 'ZAR');
        foreach ($this->lines as $line) {
            $total = $total->add($line->getSubtotal());
        }
        return $total;
    }

    public function getId(): string     { return $this->id; }
    public function getStatus(): string { return $this->status; }

    public function confirm(): void {
        $this->status = 'confirmed';
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Discount — fully typed with rate validation and fixed apply() return type
// ─────────────────────────────────────────────────────────────────────────────

class Discount {
    public function __construct(
        private string $code,
        private float  $rate          // 0.0 to 1.0
    ) {
        // Task 5: validate rate range
        if ($rate < 0.0 || $rate > 1.0) {
            throw new \InvalidArgumentException(
                "Discount rate must be between 0.0 and 1.0, got {$rate}"
            );
        }
    }

    // Task 5: always returns Money — the bug (returning string) is fixed
    public function apply(Money $total): Money {
        if ($this->rate <= 0) {
            // Return zero discount Money — NOT a string
            return new Money(0, $total->getCurrency());
        }
        $discountCents = (int) round($total->getAmountCents() * $this->rate);
        return new Money($discountCents, $total->getCurrency());
    }

    public function getCode(): string { return $this->code; }
    public function getRate(): float  { return $this->rate; }
}


// ─────────────────────────────────────────────────────────────────────────────
// OrderProcessor — fully typed with null discount handled
// ─────────────────────────────────────────────────────────────────────────────

class OrderProcessor {
    public function __construct(
        private Order    $order,
        private ?Discount $discount = null   // Task 6: nullable — not every order has one
    ) {}

    public function process(): Money {
        $total    = $this->order->getTotal();
        $discount = $this->calculateDiscount($total);

        // Task 6: handle null discount — subtract zero if no discount
        if ($discount === null) {
            return $total;
        }

        return $total->subtract($discount);
    }

    // Task 6: return type is nullable — Money|null — null means no discount
    private function calculateDiscount(Money $total): ?Money {
        if ($this->discount === null) {
            return null;
        }
        return $this->discount->apply($total);
    }

    public function getSummary(): string {
        $order    = $this->order;
        $subtotal = $order->getTotal();
        $final    = $this->process();

        $lines = '';
        foreach ($order->getLines() as $line) {
            $sub    = $line->getSubtotal();
            $desc   = str_pad($line->getDescription(), 18);
            $unit   = str_pad($line->getUnitPrice()->format() . ' each', 14);
            $lines .= "  x{$line->getQuantity()} {$desc}  {$unit}  →  {$sub->format()}\n";
        }

        $discountLine = '';
        if ($this->discount !== null) {
            $discountAmt  = $this->calculateDiscount($subtotal);
            $discountLine = "Discount ({$this->discount->getCode()}, "
                          . (int)($this->discount->getRate() * 100) . "%): "
                          . "-{$discountAmt->format()}\n";
        } else {
            $discountLine = "No discount applied.\n";
        }

        return "Order {$order->getId()} ({$order->getStatus()})\n"
             . "Lines:\n{$lines}"
             . "Subtotal: {$subtotal->format()}\n"
             . $discountLine
             . "Total: {$final->format()}\n";
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Usage — Task 7: fixed callsites use integer literals, not strings
// ─────────────────────────────────────────────────────────────────────────────

$order1 = new Order('ORD-001');
$order1->addLine(new OrderLine('WDG-PRO',  'Widget Pro', new Money(29999, 'ZAR'), 2)); // 29999 not "29999"
$order1->addLine(new OrderLine('SHIP-STD', 'Shipping',   new Money(5000,  'ZAR'), 1)); // 5000  not "5000"
$order1->confirm();

$order2 = new Order('ORD-002');
$order2->addLine(new OrderLine('GDG-X', 'Gadget X', new Money(14900, 'ZAR'), 3));

$processor1 = new OrderProcessor($order1, new Discount('SAVE10', 0.10));
$processor2 = new OrderProcessor($order2); // No discount — null by default

echo $processor1->getSummary();
echo "\n";
echo $processor2->getSummary();

echo "\n=== Validation errors caught by types ===\n";

try {
    $line = new OrderLine('BAD', 'Bad Line', new Money(1000, 'ZAR'), 1);
    $line->setQuantity(0);
} catch (\InvalidArgumentException $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

try {
    new Discount('BAD', 1.5);
} catch (\InvalidArgumentException $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}


// ─────────────────────────────────────────────────────────────────────────────
// SELF-REVIEW CHECKLIST
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- Self-review checklist ---\n";
echo "[ ] declare(strict_types=1) is the first statement in the file?\n";
echo "[ ] Every parameter has a type declaration?\n";
echo "[ ] Every method has a return type declaration?\n";
echo "[ ] Money::add and Money::subtract use 'static' not 'self'?\n";
echo "[ ] Discount::apply always returns Money (not a string)?\n";
echo "[ ] OrderProcessor::process handles null discount (returns full total)?\n";
echo "[ ] calculateDiscount() is typed as ?Money (nullable)?\n";
echo "[ ] setQuantity() throws InvalidArgumentException for quantity < 1?\n";
echo "[ ] Discount constructor throws for rate outside 0.0–1.0?\n";
echo "[ ] Money callsites use 29999 and 5000 (integers), not strings?\n";