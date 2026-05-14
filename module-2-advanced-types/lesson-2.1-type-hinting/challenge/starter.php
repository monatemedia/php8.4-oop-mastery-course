<?php
// TODO Task 1: Add declare(strict_types=1) here as the very first statement

/**
 * CHALLENGE STARTER — Lesson 2.1: Type Hinting & Return Types
 * ─────────────────────────────────────────────────────────────
 * Read CHALLENGE.md before touching this file.
 *
 * This file runs correctly — but has NO type declarations.
 * Silent coercion hides several bugs. Your job:
 *   1. Add declare(strict_types=1)
 *   2. Add parameter types to every method and function
 *   3. Add return types to every method and function
 *   4. Fix the bugs that type declarations surface
 *
 * Do NOT change what gets printed. Do NOT look at solution.php first.
 */


// ─────────────────────────────────────────────────────────────────────────────
// Money — a value object representing an amount in a given currency
// TODO Task 2: Add all type declarations to this class
// ─────────────────────────────────────────────────────────────────────────────

class Money {
    // TODO: type the constructor parameters
    public function __construct(
        private $amountCents,
        private $currency
    ) {
        if ($amountCents < 0) {
            throw new \InvalidArgumentException("Amount cannot be negative.");
        }
    }

    // TODO: add return types to all methods below
    public function getAmountCents() { return $this->amountCents; }
    public function getCurrency()    { return strtoupper($this->currency); }
    public function getAmount()      { return $this->amountCents / 100; }

    // TODO: type the parameter and return type
    // TODO: should use `static` not `self` (so subclasses can chain correctly)
    public function add($other) {
        if ($other->getCurrency() !== $this->getCurrency()) {
            throw new \LogicException("Cannot add different currencies.");
        }
        return new static($this->amountCents + $other->getAmountCents(), $this->currency);
    }

    // TODO: add return type
    public function subtract($other) {
        if ($other->getCurrency() !== $this->getCurrency()) {
            throw new \LogicException("Cannot subtract different currencies.");
        }
        $result = $this->amountCents - $other->getAmountCents();
        return new static(max(0, $result), $this->currency);
    }

    // TODO: add return type
    public function format() {
        return 'R' . number_format($this->getAmount(), 2);
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// OrderLine — a single line item in an order
// TODO Task 3: Add all type declarations to this class
// ─────────────────────────────────────────────────────────────────────────────

class OrderLine {
    // TODO: type the constructor parameters
    public function __construct(
        private $sku,
        private $description,
        private $unitPrice,    // Should be Money
        private $quantity
    ) {}

    // TODO: add parameter type, return type, AND a guard (quantity >= 1)
    public function setQuantity($quantity) {
        // TODO: throw \InvalidArgumentException if $quantity < 1
        $this->quantity = $quantity;
    }

    // TODO: add return types to all getters
    public function getSku()         { return $this->sku; }
    public function getDescription() { return $this->description; }
    public function getUnitPrice()   { return $this->unitPrice; }
    public function getQuantity()    { return $this->quantity; }

    // TODO: add return type — returns a Money object
    public function getSubtotal() {
        $cents = $this->unitPrice->getAmountCents() * $this->quantity;
        return new Money($cents, $this->unitPrice->getCurrency());
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Order — a collection of order lines
// TODO Task 4: Add all type declarations to this class
// ─────────────────────────────────────────────────────────────────────────────

class Order {
    private $lines  = [];    // TODO: type as array
    private $status = 'pending';

    // TODO: type the constructor parameter
    public function __construct(private $id) {}

    // TODO: type the parameter (OrderLine) and return type (void)
    public function addLine($line) {
        $this->lines[] = $line;
    }

    // TODO: add return type (array)
    public function getLines() { return $this->lines; }

    // TODO: add return type (Money)
    public function getTotal() {
        $total = new Money(0, 'ZAR');
        foreach ($this->lines as $line) {
            $total = $total->add($line->getSubtotal());
        }
        return $total;
    }

    // TODO: add return types
    public function getId()     { return $this->id; }
    public function getStatus() { return $this->status; }

    // TODO: add return type (void)
    public function confirm() {
        $this->status = 'confirmed';
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Discount — applies a percentage discount to a Money amount
// TODO Task 5: Add all type declarations and validation
// ─────────────────────────────────────────────────────────────────────────────

class Discount {
    // TODO: type the constructor parameters
    // TODO: validate that $rate is between 0.0 and 1.0 — throw \InvalidArgumentException if not
    public function __construct(
        private $code,
        private $rate
    ) {}

    // TODO: type the parameter (Money) and return type (Money)
    // ❗ BUG: one branch returns a string instead of Money — fix this
    public function apply($total) {
        if ($this->rate <= 0) {
            return "no_discount"; // ❗ BUG: should return a Money object (zero discount)
        }
        $discountCents = (int) round($total->getAmountCents() * $this->rate);
        return new Money($discountCents, $total->getCurrency());
    }

    // TODO: add return types
    public function getCode() { return $this->code; }
    public function getRate() { return $this->rate; }
}


// ─────────────────────────────────────────────────────────────────────────────
// OrderProcessor — orchestrates the order and applies discount
// TODO Task 6: Add all type declarations to this class
// ─────────────────────────────────────────────────────────────────────────────

class OrderProcessor {
    // TODO: type the constructor parameters
    // $discount should be nullable — not every order has a discount
    public function __construct(
        private $order,
        private $discount = null
    ) {}

    // TODO: add return type (Money)
    public function process() {
        $total    = $this->order->getTotal();
        $discount = $this->calculateDiscount($total);

        // ❗ BUG: if $discount is null (no discount), subtract() will fail
        // TODO: handle null discount safely
        return $total->subtract($discount);
    }

    // TODO: add return type — returns Money OR null
    private function calculateDiscount($total) {
        if ($this->discount === null) {
            return null; // ❗ BUG: process() does not handle this null
        }
        return $this->discount->apply($total);
    }

    // TODO: add return type (string)
    public function getSummary() {
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
// Usage — output must remain UNCHANGED after your type additions and bug fixes
// ─────────────────────────────────────────────────────────────────────────────

// ❗ BUG: "29999" and "5000" are strings — will cause TypeError after you add strict_types
// TODO Task 7: Fix these callsites to use integer literals
$order1 = new Order('ORD-001');
$order1->addLine(new OrderLine('WDG-PRO', 'Widget Pro',   new Money("29999", 'ZAR'), 2));
$order1->addLine(new OrderLine('SHIP-STD', 'Shipping',    new Money("5000",  'ZAR'), 1));
$order1->confirm();

$order2 = new Order('ORD-002');
$order2->addLine(new OrderLine('GDG-X', 'Gadget X', new Money(14900, 'ZAR'), 3));

$processor1 = new OrderProcessor($order1, new Discount('SAVE10', 0.10));
$processor2 = new OrderProcessor($order2);

echo $processor1->getSummary();
echo "\n";
echo $processor2->getSummary();

echo "\n=== Validation errors caught by types ===\n";

try {
    $line = new OrderLine('BAD', 'Bad Line', new Money(1000, 'ZAR'), 1);
    $line->setQuantity(0); // TODO: should throw after you add the guard
} catch (\InvalidArgumentException $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

try {
    new Discount('BAD', 1.5); // Rate > 1.0 — invalid
} catch (\InvalidArgumentException $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}