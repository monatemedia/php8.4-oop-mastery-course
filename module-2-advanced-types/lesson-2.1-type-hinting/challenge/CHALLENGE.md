# Code Challenge — Lesson 2.1: Type Hinting & Return Types

> **Add strict type declarations to a loosely typed class hierarchy**

---

## The Brief

You have inherited a small order management system written without any type declarations — no parameter types, no return types, no `strict_types`. It currently "works" because PHP silently coerces values, but it contains several subtle bugs that type declarations would catch immediately. Your job is to add comprehensive typing to every function, method, and file without changing what the code produces.

---

## What is Wrong With the Starter Code

Open `starter.php`. You will find five classes — `Money`, `OrderLine`, `Order`, `Discount`, and `OrderProcessor` — none of which have:

- `declare(strict_types=1)`
- Parameter type declarations
- Return type declarations

**Known bugs hidden by coercion (you will surface these by adding types):**

1. `Money::__construct` receives a quantity string (`"299"`) instead of an integer — coercion hides it.
2. `OrderLine::setQuantity` accepts negative numbers and floats silently.
3. `Order::addLine` has no type guarantee — it could receive anything.
4. `Discount::apply` returns a float in some branches and a string in others — callers cannot rely on a consistent type.
5. `OrderProcessor::process` calls a method that sometimes returns `null` without checking.

---

## Your Tasks

Work in `starter.php`. Do NOT look at `solution.php` until you have made a genuine attempt.

### Task 1 — Add `declare(strict_types=1)` to the file
It must be the very first statement, before anything else.

### Task 2 — Type the `Money` class
- Constructor: `int $amountCents`, `string $currency`
- All getters: appropriate scalar return types
- `add(Money $other): static`
- `format(): string`

### Task 3 — Type the `OrderLine` class
- Constructor: `string $sku`, `string $description`, `Money $unitPrice`, `int $quantity`
- `setQuantity(int $quantity): void` — also add a guard: throw `\InvalidArgumentException` if quantity < 1
- `getSubtotal(): Money`
- All other getters: appropriate return types

### Task 4 — Type the `Order` class
- `addLine(OrderLine $line): void`
- `getLines(): array` — note: PHP does not support typed arrays natively; `array` is fine here
- `getTotal(): Money`
- `getId(): string`
- `getStatus(): string`
- `confirm(): void`

### Task 5 — Type the `Discount` class
- `__construct(string $code, float $rate)` — rate should be between 0.0 and 1.0; throw `\InvalidArgumentException` if out of range
- `apply(Money $total): Money` — the return type must always be `Money`
- `getCode(): string`
- `getRate(): float`

### Task 6 — Type the `OrderProcessor` class
- `__construct(Order $order, ?Discount $discount = null)`
- `process(): Money` — returns the final total after any discount
- `getSummary(): string`
- Any private helpers you add should also be fully typed

### Task 7 — Fix the hidden bugs
Once you add types, PHP will surface the following — fix each one:
1. The `Money` constructor is called with a string in two places — change the callsite to use integers.
2. `setQuantity` should reject quantities less than 1 — add the guard.
3. `Discount::apply` must always return `Money` — fix the branch that returns a string.
4. `OrderProcessor::process` must handle a `null` discount safely.

---

## Acceptance Criteria

- [ ] `declare(strict_types=1)` is the first statement
- [ ] Every parameter has a type declaration
- [ ] Every method and function has a return type declaration
- [ ] `self` vs `static` is used correctly — `Money::add` and any chaining methods use `static`
- [ ] No `mixed` used where a more specific type is possible
- [ ] `setQuantity` throws `\InvalidArgumentException` for quantity < 1
- [ ] `Discount::__construct` throws `\InvalidArgumentException` if rate is not between 0.0 and 1.0
- [ ] `Discount::apply` always returns `Money` — never a string or other type
- [ ] All existing output is preserved exactly

---

## Expected Output

```
Order ORD-001 (confirmed)
Lines:
  x2 Widget Pro       R299.99 each  →  R599.98
  x1 Shipping         R50.00 each   →  R50.00
Subtotal: R649.98
Discount (SAVE10, 10%): -R65.00
Total: R584.98

Order ORD-002 (pending)
Lines:
  x3 Gadget X         R149.00 each  →  R447.00
Subtotal: R447.00
No discount applied.
Total: R447.00

=== Validation errors caught by types ===
Caught: Quantity must be at least 1, got 0
Caught: Discount rate must be between 0.0 and 1.0, got 1.5
```