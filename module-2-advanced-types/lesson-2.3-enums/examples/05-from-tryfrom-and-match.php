<?php
declare(strict_types=1);

/**
 * Example 05 — from(), tryFrom(), and match Exhaustiveness
 * ----------------------------------------------------------
 * PHP 8.1+
 *
 * This example covers three closely related topics:
 *   1. from()     — convert a scalar to an enum case, throws on failure
 *   2. tryFrom()  — convert a scalar to an enum or null, safe for untrusted data
 *   3. match exhaustiveness — adding a new enum case forces you to handle it
 *
 * Understanding these together is what makes enums a genuine safety net
 * rather than just prettier string constants.
 */

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  from(), tryFrom(), and match Exhaustiveness       ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 1 — from() in depth
// ─────────────────────────────────────────────────────────────────────────────

echo "── Part 1: from() — trusted data ────────────────────\n\n";

enum DocumentStatus: string {
    case Draft     = 'draft';
    case Review    = 'review';
    case Published = 'published';
    case Archived  = 'archived';
}

// from() — use when the value MUST exist (trusted internal source)
// Throws \ValueError if the value is not a valid backing value
echo "from() with valid values:\n";
$statuses = ['draft', 'review', 'published', 'archived'];
foreach ($statuses as $raw) {
    $enum = DocumentStatus::from($raw);
    echo "  from('{$raw}') → {$enum->name}\n";
}

echo "\nfrom() with an invalid value:\n";
try {
    DocumentStatus::from('deleted');
} catch (\ValueError $e) {
    echo "  ValueError: " . $e->getMessage() . "\n";
}

// When to use from(): reading from YOUR OWN database or config
// The value is trusted — if it's wrong, that's a programming bug worth crashing for
function loadDocumentFromDb(array $row): array {
    return [
        'id'     => $row['id'],
        'title'  => $row['title'],
        'status' => DocumentStatus::from($row['status']), // Trust the DB
    ];
}

$dbRow = ['id' => 42, 'title' => 'PHP 8.1 Guide', 'status' => 'published'];
$doc   = loadDocumentFromDb($dbRow);
echo "\nLoaded from DB: {$doc['title']} → {$doc['status']->name}\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 2 — tryFrom() in depth
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 2: tryFrom() — untrusted data ───────────────\n\n";

enum NotificationChannel: string {
    case Email = 'email';
    case SMS   = 'sms';
    case Push  = 'push';
    case Slack = 'slack';
}

// tryFrom() — use when the value comes from OUTSIDE your system
// Returns null instead of throwing — you handle null explicitly
echo "tryFrom() with mixed input:\n";
$inputs = ['email', 'SMS', 'push', 'twitter', '', 'EMAIL', 'slack'];

foreach ($inputs as $input) {
    $channel = NotificationChannel::tryFrom($input);
    if ($channel !== null) {
        echo "  '{$input}' → {$channel->name} ✓\n";
    } else {
        echo "  '{$input}' → null (invalid) ✗\n";
    }
}

// Pattern: parse-then-validate from a form/API
function parseChannelFromRequest(array $request): NotificationChannel {
    $raw     = $request['channel'] ?? '';
    $channel = NotificationChannel::tryFrom($raw);

    if ($channel === null) {
        $valid = implode(', ', array_map(fn($c) => $c->value, NotificationChannel::cases()));
        throw new \InvalidArgumentException(
            "Invalid channel '{$raw}'. Valid options: {$valid}"
        );
    }

    return $channel;
}

echo "\nParsing from request:\n";

$requests = [
    ['channel' => 'email'],
    ['channel' => 'slack'],
    ['channel' => 'telegram'],
];

foreach ($requests as $req) {
    try {
        $ch = parseChannelFromRequest($req);
        echo "  ✓ {$ch->name}\n";
    } catch (\InvalidArgumentException $e) {
        echo "  ✗ " . $e->getMessage() . "\n";
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// PART 3 — from() vs tryFrom() decision guide
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 3: from() vs tryFrom() — when to use each ──\n\n";

echo "Use from() when:\n";
echo "  • The value comes from YOUR OWN system (DB, config, serialised object)\n";
echo "  • An invalid value is a programming bug — crash loudly\n";
echo "  • Example: \$status = Status::from(\$dbRow['status']);\n\n";

echo "Use tryFrom() when:\n";
echo "  • The value comes from OUTSIDE your system (form, API, CSV, user input)\n";
echo "  • An invalid value is expected (bad input, version mismatch, typo)\n";
echo "  • You want to handle null gracefully\n";
echo "  • Example: \$channel = NotificationChannel::tryFrom(\$request['channel']);\n\n";

echo "Rule of thumb:\n";
echo "  Trusted source → from()    (crash = bug found early)\n";
echo "  Untrusted source → tryFrom() (null = user error, handle it)\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 4 — match exhaustiveness
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 4: match exhaustiveness ─────────────────────\n\n";

enum InvoiceStatus: string {
    case Draft   = 'draft';
    case Sent    = 'sent';
    case Paid    = 'paid';
    case Overdue = 'overdue';
    case Void    = 'void';
}

// A match that covers ALL cases — exhaustive
function describeInvoice(InvoiceStatus $status): string {
    return match($status) {
        InvoiceStatus::Draft   => "Invoice is in draft — not yet sent to customer.",
        InvoiceStatus::Sent    => "Invoice has been sent — awaiting payment.",
        InvoiceStatus::Paid    => "Invoice is fully paid. ✓",
        InvoiceStatus::Overdue => "Payment overdue — follow up with customer.",
        InvoiceStatus::Void    => "Invoice has been voided and is no longer valid.",
    };
}

foreach (InvoiceStatus::cases() as $status) {
    echo "  {$status->name}: " . describeInvoice($status) . "\n";
}

// What happens when match is NOT exhaustive?
function badDescribe(InvoiceStatus $status): string {
    return match($status) {
        InvoiceStatus::Draft => "Draft",
        InvoiceStatus::Sent  => "Sent",
        // Paid, Overdue, Void are not covered
        // If $status is Paid: UnhandledMatchError is thrown at runtime
    };
}

echo "\nUnhandled match — UnhandledMatchError:\n";
try {
    echo badDescribe(InvoiceStatus::Paid) . "\n";
} catch (\UnhandledMatchError $e) {
    echo "  UnhandledMatchError: " . $e->getMessage() . "\n";
    echo "  (Static analysers like PHPStan catch this at compile time)\n";
}


// ─────────────────────────────────────────────────────────────────────────────
// PART 5 — Exhaustiveness in switch — less safe than match
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 5: switch vs match exhaustiveness ───────────\n\n";

function describeWithSwitch(InvoiceStatus $status): string {
    switch ($status) {
        case InvoiceStatus::Draft:
            return "Draft";
        case InvoiceStatus::Sent:
            return "Sent";
        // Missing Paid, Overdue, Void — switch silently returns '' (no default)
        // No error — just wrong behaviour
        default:
            return "Unknown"; // switch requires explicit default for safety
    }
}

function describeWithMatch(InvoiceStatus $status): string {
    return match($status) {
        InvoiceStatus::Draft   => "Draft",
        InvoiceStatus::Sent    => "Sent",
        InvoiceStatus::Paid    => "Paid",
        InvoiceStatus::Overdue => "Overdue",
        InvoiceStatus::Void    => "Void",
        // No default needed — all cases covered. If you add a new case
        // and forget it here, UnhandledMatchError catches it at runtime.
        // PHPStan/Psalm catch it at static analysis time.
    };
}

echo "switch (with explicit default — safe but verbose):\n";
echo "  Paid    → " . describeWithSwitch(InvoiceStatus::Paid) . "\n";
echo "  Overdue → " . describeWithSwitch(InvoiceStatus::Overdue) . "\n";

echo "\nmatch (exhaustive — no default needed, error if missed):\n";
echo "  Paid    → " . describeWithMatch(InvoiceStatus::Paid) . "\n";
echo "  Overdue → " . describeWithMatch(InvoiceStatus::Overdue) . "\n";

echo "\nKey insight:\n";
echo "  match + enum = safety net when you add a new case.\n";
echo "  If you add InvoiceStatus::Cancelled and forget to update describeWithMatch(),\n";
echo "  you get an UnhandledMatchError immediately — not a silent wrong result.\n";
echo "  A static analyser (PHPStan/Psalm) flags it before you even run the code.\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 6 — Combining from/tryFrom with match in a real pipeline
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 6: Full pipeline — parse → validate → act ──\n\n";

enum WebhookEvent: string {
    case PaymentSucceeded = 'payment.succeeded';
    case PaymentFailed    = 'payment.failed';
    case RefundCreated    = 'refund.created';
    case DisputeOpened    = 'dispute.opened';
}

function handleWebhook(array $payload): void {
    // Step 1: Parse — tryFrom because webhooks come from third parties
    $event = WebhookEvent::tryFrom($payload['event'] ?? '');

    if ($event === null) {
        echo "  [WEBHOOK] Unknown event '{$payload['event']}' — ignored.\n";
        return;
    }

    // Step 2: Act — match is exhaustive, new events are caught immediately
    $action = match($event) {
        WebhookEvent::PaymentSucceeded => fn() => "Mark order as paid.",
        WebhookEvent::PaymentFailed    => fn() => "Send payment failure email.",
        WebhookEvent::RefundCreated    => fn() => "Reduce revenue report.",
        WebhookEvent::DisputeOpened    => fn() => "Alert support team.",
    };

    echo "  [WEBHOOK] {$event->name}: " . $action() . "\n";
}

$webhooks = [
    ['event' => 'payment.succeeded'],
    ['event' => 'refund.created'],
    ['event' => 'dispute.opened'],
    ['event' => 'subscription.created'],  // Unknown — gracefully ignored
    ['event' => 'payment.failed'],
];

foreach ($webhooks as $payload) {
    handleWebhook($payload);
}

echo "\n--- Recap ---\n";
echo "from():      throws ValueError — use for trusted/internal data.\n";
echo "tryFrom():   returns null — use for untrusted/external data.\n";
echo "match + enum: exhaustive by default — UnhandledMatchError if a case is missed.\n";
echo "switch + enum: requires explicit default — less safe, more verbose.\n";
echo "Pattern: tryFrom() to parse, match() to act — the safest enum pipeline.\n";