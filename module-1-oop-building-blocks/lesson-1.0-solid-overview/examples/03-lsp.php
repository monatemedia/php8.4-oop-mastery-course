<?php
declare(strict_types=1);

/**
 * SOLID Example — L: Liskov Substitution Principle
 * ──────────────────────────────────────────────────
 * "Objects of a subtype must be substitutable for objects of their supertype
 *  without altering the correctness of the program."
 *
 * This is the PREVIEW. The full lesson is in Lesson 2.0.
 * Here you just need to recognise the violation and understand the fix at a high level.
 *
 * Scenario: A bird hierarchy — the classic LSP illustration.
 */

echo "╔══════════════════════════════════════════════════╗\n";
echo "║  L — Liskov Substitution Principle (LSP)        ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";


// ═══════════════════════════════════════════════════════════
// ❌ VIOLATION — Penguin extends Bird but breaks the fly() contract
// ═══════════════════════════════════════════════════════════
echo "── VIOLATION ──────────────────────────────────\n\n";

class BadBird {
    public function fly(): void {
        echo get_class($this) . " is flying.\n";
    }
}

class BadEagle extends BadBird {
    // Eagle can fly — no problem here.
}

class BadPenguin extends BadBird {
    public function fly(): void {
        // LSP VIOLATION: This subtype breaks the supertype's contract.
        // Any code that calls fly() on a Bird will crash with a Penguin.
        throw new \LogicException("Penguins cannot fly!");
    }
}

function makeBirdFly(BadBird $bird): void {
    $bird->fly(); // Caller reasonably expects this to always work on a Bird.
}

makeBirdFly(new BadEagle()); // ✓ Works fine

// Uncomment to see the violation crash the program:
// makeBirdFly(new BadPenguin()); // ✗ Fatal — breaks the Bird contract

echo "(Penguin::fly() would throw — see comment above to test)\n";


// ═══════════════════════════════════════════════════════════
// ✅ FIX — Model the hierarchy so every substitution is safe
// ═══════════════════════════════════════════════════════════
echo "\n── FIX ─────────────────────────────────────────\n\n";

// All birds can move — but not all birds can fly.
// Split the contract to match reality.
interface Bird {
    public function move(): string; // Safe for ALL birds
}

interface FlyingBird extends Bird {
    public function fly(): string; // Only for birds that can actually fly
}

class Eagle implements FlyingBird {
    public function move(): string { return $this->fly(); }
    public function fly(): string  { return "Eagle soaring on thermals."; }
}

class Parrot implements FlyingBird {
    public function move(): string { return $this->fly(); }
    public function fly(): string  { return "Parrot flapping between perches."; }
}

class Penguin implements Bird {
    public function move(): string { return "Penguin waddling on ice."; }
    // No fly() — Penguin never promised it could fly. Contract honoured.
}

class Ostrich implements Bird {
    public function move(): string { return "Ostrich sprinting across savanna."; }
}

// Safe: accepts ANY Bird — Penguin and Ostrich are fully valid here.
function moveAnimal(Bird $bird): void {
    echo "[MOVE] " . $bird->move() . "\n";
}

// More specific: only accepts birds that can actually fly.
function sendOnAirMission(FlyingBird $bird): void {
    echo "[FLY]  " . $bird->fly() . "\n";
}

echo "All birds moved safely:\n";
moveAnimal(new Eagle());
moveAnimal(new Parrot());
moveAnimal(new Penguin());
moveAnimal(new Ostrich());

echo "\nAir missions (FlyingBird only):\n";
sendOnAirMission(new Eagle());
sendOnAirMission(new Parrot());
// sendOnAirMission(new Penguin()); // PHP type error — correctly prevented at compile time


// ═══════════════════════════════════════════════════════════
// THE RULE IN ONE SENTENCE
// ═══════════════════════════════════════════════════════════
echo "\n── The rule ──────────────────────────────────\n\n";
echo "If function F works with type T, it must also work with any subtype of T.\n";
echo "If a subtype breaks that, the hierarchy is wrongly modelled — fix the hierarchy.\n";

echo "\n--- Recap ---\n";
echo "LSP: Subtypes must honour the full contract of their supertype.\n";
echo "Test: Ask 'could I pass a subtype anywhere the parent is expected without surprises?'\n";
echo "Payoff: Polymorphism only works safely when LSP is respected.\n";
echo "Depth: Full coverage in Lesson 2.0 — covariance, contravariance, PHP enforcement.\n";