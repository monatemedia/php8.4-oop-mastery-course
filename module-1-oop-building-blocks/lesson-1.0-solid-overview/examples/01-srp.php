<?php
declare(strict_types=1);

/**
 * SOLID Example — S: Single Responsibility Principle
 * ────────────────────────────────────────────────────
 * "A class should have one, and only one, reason to change."
 *
 * Scenario: User registration in a web application.
 * We will first see the violation (one bloated class), then the fix
 * (three focused classes, each with a single job).
 */

echo "╔══════════════════════════════════════════════╗\n";
echo "║  S — Single Responsibility Principle (SRP)  ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";


// ═══════════════════════════════════════════════════════════
// ❌ VIOLATION — UserService does three unrelated things
// ═══════════════════════════════════════════════════════════
echo "── VIOLATION ──────────────────────────────────\n\n";

class BadUserService {
    public function register(string $email, string $password): void {
        // Job 1: Security — hashing passwords
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        echo "[BAD] Hashed password: " . substr($hashed, 0, 20) . "...\n";

        // Job 2: Persistence — saving to the database
        // (Simulated — no real DB needed)
        echo "[BAD] Saved user '{$email}' to database.\n";

        // Job 3: Communication — sending a welcome email
        echo "[BAD] Sent welcome email to '{$email}'.\n";
    }
}

// This WORKS — but every one of these is a reason to change BadUserService:
//  • Password hashing algorithm changes → edit BadUserService
//  • Database schema changes            → edit BadUserService
//  • Welcome email text changes         → edit BadUserService
//  That is three unrelated reasons.

$bad = new BadUserService();
$bad->register('alice@example.com', 'secret123');


// ═══════════════════════════════════════════════════════════
// ✅ FIX — Each class has exactly one job
// ═══════════════════════════════════════════════════════════
echo "\n── FIX ─────────────────────────────────────────\n\n";

// Responsibility 1: Security
class PasswordHasher {
    public function hash(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public function verify(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}
// Reason to change: only if the hashing strategy changes.

// Responsibility 2: Persistence
class UserRepository {
    private array $store = []; // In-memory stand-in for a real DB

    public function save(string $email, string $hashedPassword): int {
        $id = count($this->store) + 1;
        $this->store[$id] = ['email' => $email, 'password' => $hashedPassword];
        echo "[REPO] Saved user '{$email}' with id={$id}.\n";
        return $id;
    }

    public function findByEmail(string $email): ?array {
        foreach ($this->store as $user) {
            if ($user['email'] === $email) return $user;
        }
        return null;
    }
}
// Reason to change: only if the storage mechanism changes (e.g. switch to MongoDB).

// Responsibility 3: Communication
class WelcomeMailer {
    public function send(string $email): void {
        // Real implementation would call a mail API.
        echo "[MAILER] Sent welcome email to '{$email}'.\n";
    }
}
// Reason to change: only if the welcome email content or mail provider changes.

// Orchestrator — UserService now has one job: coordinate the three collaborators.
class UserService {
    public function __construct(
        private PasswordHasher $hasher,
        private UserRepository $repository,
        private WelcomeMailer  $mailer
    ) {}

    public function register(string $email, string $password): int {
        $hashed = $this->hasher->hash($password);
        $id     = $this->repository->save($email, $hashed);
        $this->mailer->send($email);
        return $id;
    }
}
// Reason to change: only if the registration WORKFLOW changes (the steps themselves).

$service = new UserService(
    new PasswordHasher(),
    new UserRepository(),
    new WelcomeMailer()
);

$userId = $service->register('bob@example.com', 'hunter2');
echo "[OK] Registration complete. User ID: {$userId}\n";


// ═══════════════════════════════════════════════════════════
// WHY THIS MATTERS IN PRACTICE
// ═══════════════════════════════════════════════════════════
echo "\n── Why this matters ──────────────────────────\n\n";

// Because each class has one job, you can swap any one without touching the others.
// Example: swap in a different mailer — UserService and UserRepository are untouched.

class SmsWelcomeNotifier {
    public function send(string $email): void {
        echo "[SMS NOTIFIER] Sent SMS welcome to account registered with '{$email}'.\n";
    }
}

// SmsWelcomeNotifier does not implement WelcomeMailer's interface here (that is Lesson 1.1),
// but the point is clear: swap the third constructor argument and nothing else changes.
$serviceWithSms = new UserService(
    new PasswordHasher(),
    new UserRepository(),
    // Uncomment the line below if you add a shared interface in Lesson 1.1:
    // new SmsWelcomeNotifier()
    new WelcomeMailer() // ← swap this in Lesson 1.1
);

echo "\n--- Recap ---\n";
echo "SRP: Each class has ONE reason to change.\n";
echo "Test: Ask 'what would make me edit this class?' — if there are 2+ answers, split it.\n";
echo "Payoff: Smaller classes are easier to test, reuse, and replace.\n";