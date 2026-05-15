<?php
declare(strict_types=1);

/**
 * CHALLENGE STARTER — Lesson 2.3: Enums
 * ───────────────────────────────────────
 * PHP 8.1+. Read CHALLENGE.md before touching this file.
 *
 * This module uses magic string constants everywhere.
 * Your job: replace them with three backed enums and update all calling code.
 *
 * Do NOT look at solution.php until you have made a genuine attempt.
 */


// ─────────────────────────────────────────────────────────────────────────────
// TODO Task 1: Define enum NotificationChannel: string
// Cases: Email='email', SMS='sms', Push='push', Slack='slack'
// Methods: label(), supportsRichText(), maxMessageLength()
// ─────────────────────────────────────────────────────────────────────────────


// ─────────────────────────────────────────────────────────────────────────────
// TODO Task 2: Define enum NotificationPriority: int
// Cases: Low=1, Normal=5, High=10, Urgent=99
// Methods: label(), shouldAlert(), retryAttempts()
// ─────────────────────────────────────────────────────────────────────────────


// ─────────────────────────────────────────────────────────────────────────────
// TODO Task 3: Define enum NotificationStatus: string
// Cases: Pending='pending', Sent='sent', Failed='failed', Cancelled='cancelled'
// Methods: label(), isTerminal(), canRetry()
// ─────────────────────────────────────────────────────────────────────────────


// ─────────────────────────────────────────────────────────────────────────────
// Notification — represents a single notification record
// TODO Task 4: Replace string properties with enum types
// ─────────────────────────────────────────────────────────────────────────────

class Notification {
    // TODO: change these string properties to enum types
    public string $channel;
    public string $priority;
    public string $status = 'pending';  // TODO: NotificationStatus::Pending

    public function __construct(
        public readonly string $id,
        public string $recipient,
        public string $message,
        string $channel,    // TODO: NotificationChannel $channel
        string $priority    // TODO: NotificationPriority $priority
    ) {
        $this->channel  = $channel;
        $this->priority = $priority;
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// NotificationService — sends notifications
// TODO Task 5: Remove string constants, update method signatures to enum types,
//              use tryFrom() for external parsing, use match() for branching
// ─────────────────────────────────────────────────────────────────────────────

class NotificationService {
    // ❗ Remove these — the enum cases replace them
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS   = 'sms';
    const CHANNEL_PUSH  = 'push';
    const CHANNEL_SLACK = 'slack';

    const PRIORITY_LOW    = 1;
    const PRIORITY_NORMAL = 5;
    const PRIORITY_HIGH   = 10;
    const PRIORITY_URGENT = 99;

    private array $sent = [];

    // TODO: Change string $channel and string $priority to enum types
    public function send(
        string $recipient,
        string $message,
        string $channel,
        string $priority
    ): Notification {
        $notification = new Notification(
            id:        uniqid('INF-'),
            recipient: $recipient,
            message:   $message,
            channel:   $channel,
            priority:  $priority
        );

        // ❗ Replace these string comparisons with enum-aware logic
        $channelLabel   = strtoupper($channel);
        $priorityLabel  = match((int)$priority) {
            1  => '🟢 Low',
            5  => '📢 Normal',
            10 => '🔴 High',
            99 => '🚨 Urgent',
            default => 'Unknown',
        };

        // ❗ These string comparisons should use enum methods
        $retries        = match((int)$priority) {
            1  => 1,
            5  => 3,
            10 => 5,
            99 => 10,
            default => 1,
        };

        $richText = in_array($channel, ['email', 'slack'], true);
        $maxLen   = match($channel) {
            'email' => 10000,
            'sms'   => 160,
            'push'  => 256,
            'slack' => 4000,
            default => 0,
        };

        echo "[{$channelLabel}] {$priorityLabel}: {$message} (retries: {$retries})\n";
        echo "  Channel: " . ucfirst($channel)
           . " | Rich text: " . ($richText ? 'YES' : 'NO')
           . " | Max length: {$maxLen} chars\n";

        $notification->status = 'sent';  // TODO: NotificationStatus::Sent
        $this->sent[]         = $notification;
        return $notification;
    }

    // TODO: Parse from external request using tryFrom()
    public function sendFromRequest(array $request): ?Notification {
        // ❗ This is unsafe — no validation on channel or priority
        $channel  = $request['channel']  ?? '';
        $priority = $request['priority'] ?? '5';
        // TODO: use NotificationChannel::tryFrom() and NotificationPriority::tryFrom()
        // Return null and print an error if either is invalid
        return null; // placeholder
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// NotificationRepository — query notifications
// TODO Task 6: Change string parameters to enum types, update filter logic
// ─────────────────────────────────────────────────────────────────────────────

class NotificationRepository {
    /** @var Notification[] */
    private array $records = [];

    public function add(Notification $n): void {
        $this->records[] = $n;
    }

    // TODO: change string $channel to NotificationChannel $channel
    public function findByChannel(string $channel): array {
        return array_values(array_filter(
            $this->records,
            // ❗ String comparison — replace with enum identity (===)
            fn(Notification $n) => $n->channel === $channel
        ));
    }

    // TODO: change string $status to NotificationStatus $status
    public function findByStatus(string $status): array {
        return array_values(array_filter(
            $this->records,
            fn(Notification $n) => $n->status === $status
        ));
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// CURRENT usage — output must remain UNCHANGED after your refactor
// TODO Task 7: Replace all string literals with enum cases
// ─────────────────────────────────────────────────────────────────────────────

$service = new NotificationService();
$repo    = new NotificationRepository();

echo "=== Sending notifications ===\n";

// ❗ These string literals should be enum cases after your refactor
$n1 = $service->send('ops@example.com', 'Server is down!',          'email', '99');
$n2 = $service->send('+27821234567',     'Disk usage above 90%',     'sms',   '10');
$n3 = $service->send('device-token-abc', 'Weekly summary is ready',  'push',  '5');
$n4 = $service->send('#ops-channel',     'Cron job completed',       'slack', '1');

$repo->add($n1);
$repo->add($n2);
$repo->add($n3);
$repo->add($n4);

echo "\n=== Status transitions ===\n";

// Create two notifications to show status transitions
$inf1 = new Notification('INF-001', 'alice@example.com', 'Test message', 'email', '5');
$inf2 = new Notification('INF-002', 'bob@example.com',   'Test message', 'sms',   '10');

// ❗ String comparisons — should use NotificationStatus enum methods
echo "{$inf1->id}: " . ucfirst($inf1->status) . " → ";
$inf1->status = 'sent';      // TODO: NotificationStatus::Sent
echo ucfirst($inf1->status) . "\n";

echo "{$inf2->id}: " . ucfirst($inf2->status) . " → ";
$inf2->status = 'failed';    // TODO: NotificationStatus::Failed
echo ucfirst($inf2->status) . "\n";

// ❗ This string comparison should use enum methods (canRetry(), isTerminal())
if ($inf2->status === 'failed') {
    echo "{$inf2->id}: canRetry=YES → ";
    $inf2->status = 'pending'; // TODO: NotificationStatus::Pending
    echo ucfirst($inf2->status) . " → ";
    $inf2->status = 'sent';    // TODO: NotificationStatus::Sent
    echo ucfirst($inf2->status) . "\n";
}

echo "\n=== Repository queries ===\n";
// ❗ String arguments — should be enum cases
$emailNotifications  = $repo->findByChannel('email');
$failedNotifications = $repo->findByStatus('sent'); // Note: all sent in this demo
echo "Email notifications: " . count($emailNotifications) . "\n";
echo "Failed notifications: " . count($repo->findByStatus('failed')) . "\n";

echo "\n=== Parsing from external request ===\n";

$requests = [
    ['channel' => 'email',    'priority' => '10',  'message' => 'Test'],
    ['channel' => 'telegram', 'priority' => '10',  'message' => 'Test'],
    ['channel' => 'sms',      'priority' => '7',   'message' => 'Test'],
];

foreach ($requests as $req) {
    // TODO: implement sendFromRequest() using tryFrom() with error messages
    // Expected output lines are in CHALLENGE.md
    echo "(TODO: implement sendFromRequest)\n";
}