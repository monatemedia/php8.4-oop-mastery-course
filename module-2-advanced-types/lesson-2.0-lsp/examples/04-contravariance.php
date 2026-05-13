<?php
declare(strict_types=1);

/**
 * LSP Example 04 — Contravariance (Parameter Types)
 * ---------------------------------------------------
 * Contravariance: an overriding method's parameter type can be a SUPERTYPE (wider/more general).
 * This is safe because the caller passes a T, and a method that accepts a wider type
 * can always handle T (since T IS the wider type, or a subtype of it).
 *
 * PHP supports contravariant parameter types since PHP 7.4.
 * Trying to NARROW (make more specific) a parameter type in an override is a fatal error.
 *
 * Scenario: Event handlers and validators with progressively wider acceptance.
 *
 * Note: Contravariance is less common in everyday PHP than covariance,
 * but understanding it makes you a better interface designer.
 */

echo "╔══════════════════════════════════════════════════╗\n";
echo "║  LSP — Contravariance: Parameter Types          ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";


// ─────────────────────────────────────────────────────────────────────────────
// The type hierarchy we will work with
// ─────────────────────────────────────────────────────────────────────────────

class Event {
    public function __construct(public readonly string $name) {}
}

class UserEvent extends Event {
    public function __construct(
        string $name,
        public readonly int $userId
    ) {
        parent::__construct($name);
    }
}

class UserLoginEvent extends UserEvent {
    public function __construct(int $userId, public readonly string $ipAddress) {
        parent::__construct('user.login', $userId);
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Handler interfaces with contravariant parameter types
// ─────────────────────────────────────────────────────────────────────────────

// The specific contract: handle UserLoginEvents specifically.
interface LoginEventHandler {
    public function handle(UserLoginEvent $event): void;
}

// ✅ CONTRAVARIANT: A handler that accepts UserEvent (wider than UserLoginEvent).
// Safe to substitute because a UserLoginEvent IS a UserEvent — the wider handler
// can always handle it.
class UserEventHandler implements LoginEventHandler {
    public function handle(UserEvent $event): void { // Wider param — allowed
        echo "[UserEventHandler] Handling event '{$event->name}' for user #{$event->userId}\n";
    }
}

// ✅ CONTRAVARIANT again: accepts ANY Event (even wider).
class AnyEventHandler implements LoginEventHandler {
    public function handle(Event $event): void { // Even wider param — still allowed
        echo "[AnyEventHandler] Handling event '{$event->name}'\n";
    }
}

// ✅ Exact match — always fine.
class SpecificLoginHandler implements LoginEventHandler {
    public function handle(UserLoginEvent $event): void {
        echo "[SpecificLoginHandler] Login from IP {$event->ipAddress} for user #{$event->userId}\n";
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Using the handlers — contravariance in action
// ─────────────────────────────────────────────────────────────────────────────

echo "── Handler contravariance ──────────────────────────\n\n";

$loginEvent = new UserLoginEvent(userId: 42, ipAddress: '196.25.100.5');

// All three implement LoginEventHandler — all can be substituted safely.
$handlers = [
    new SpecificLoginHandler(),
    new UserEventHandler(),
    new AnyEventHandler(),
];

foreach ($handlers as $handler) {
    $handler->handle($loginEvent);
}

echo "\n── Why this is safe ────────────────────────────────\n\n";
echo "The caller always passes a UserLoginEvent.\n";
echo "A handler that accepts UserEvent can handle it (UserLoginEvent IS a UserEvent).\n";
echo "A handler that accepts Event can handle it (UserLoginEvent IS an Event).\n";
echo "No information needed by any handler is missing — safe substitution.\n";


// ─────────────────────────────────────────────────────────────────────────────
// Validator example — contravariance in a more practical setting
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Validator contravariance ────────────────────────\n\n";

class FormData {
    public function __construct(public readonly array $fields) {}
}

class RegistrationFormData extends FormData {
    public function __construct(
        array  $fields,
        public readonly string $email,
        public readonly string $password
    ) {
        parent::__construct($fields);
    }
}

// Contract: validate a RegistrationFormData specifically.
interface RegistrationValidator {
    public function validate(RegistrationFormData $data): array; // returns array of errors
}

// ✅ A general form validator — accepts any FormData (contravariant).
// Still satisfies the RegistrationValidator contract because RegistrationFormData IS FormData.
class GeneralFormValidator implements RegistrationValidator {
    public function validate(FormData $data): array { // Wider param — contravariant ✓
        $errors = [];
        if (empty($data->fields)) {
            $errors[] = "Form cannot be empty.";
        }
        return $errors;
    }
}

// ✅ Exact match — validates registration-specific fields.
class StrictRegistrationValidator implements RegistrationValidator {
    public function validate(RegistrationFormData $data): array {
        $errors = [];
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email address.";
        }
        if (strlen($data->password) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        }
        return $errors;
    }
}

function runValidation(RegistrationValidator $validator, RegistrationFormData $data): void {
    $errors = $validator->validate($data);
    if (empty($errors)) {
        echo "  ✓ Validation passed.\n";
    } else {
        foreach ($errors as $error) {
            echo "  ✗ {$error}\n";
        }
    }
}

$validData = new RegistrationFormData(
    fields:   ['email' => 'alice@example.com', 'password' => 'securepass'],
    email:    'alice@example.com',
    password: 'securepass'
);

$invalidData = new RegistrationFormData(
    fields:   ['email' => 'not-an-email', 'password' => 'abc'],
    email:    'not-an-email',
    password: 'abc'
);

echo "GeneralFormValidator with valid data:\n";
runValidation(new GeneralFormValidator(), $validData);

echo "StrictRegistrationValidator with valid data:\n";
runValidation(new StrictRegistrationValidator(), $validData);

echo "StrictRegistrationValidator with invalid data:\n";
runValidation(new StrictRegistrationValidator(), $invalidData);

echo "GeneralFormValidator with invalid data (only checks non-empty):\n";
runValidation(new GeneralFormValidator(), $invalidData);


// ─────────────────────────────────────────────────────────────────────────────
// What PHP rejects — narrowing parameter type (anti-contravariance = error)
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── What PHP prevents: narrowing parameter type ─────\n\n";
echo "The following would be a PHP fatal error (commented out — uncomment to test):\n\n";

echo <<<'CODE'
interface EventHandler {
    public function handle(Event $event): void; // Accepts any Event
}

class BadNarrowHandler implements EventHandler {
    public function handle(UserLoginEvent $event): void { // ❌ NARROWER than Event
        // Caller expects to pass any Event — but BadNarrowHandler
        // only accepts UserLoginEvent. A plain Event would fail.
    }
    // Fatal error: Declaration of BadNarrowHandler::handle(UserLoginEvent)
    // must be compatible with EventHandler::handle(Event)
}
CODE;
echo "\n";


// ─────────────────────────────────────────────────────────────────────────────
// The covariance/contravariance rule visualised
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── The direction rules ─────────────────────────────\n\n";
echo "  RETURN TYPES (covariance):    go DOWN the hierarchy (narrower ✓)\n";
echo "    Interface promises: Animal\n";
echo "    Override returns:   Dog        ← Dog IS an Animal — safe ✓\n\n";
echo "  PARAMETER TYPES (contravariance): go UP the hierarchy (wider ✓)\n";
echo "    Interface accepts: Dog\n";
echo "    Override accepts:  Animal      ← Can always handle a Dog — safe ✓\n\n";
echo "  BOTH RULES serve the same goal:\n";
echo "  The caller's expectations are ALWAYS met, regardless of which subtype is used.\n";

echo "\n--- Recap ---\n";
echo "Contravariance: overriding param types can be WIDER (more general). Always safe.\n";
echo "PHP enforces this: you cannot narrow (restrict) a parameter type in an override.\n";
echo "Combined with covariance: PHP's type system is designed to enforce LSP at compile time.\n";