<?php
declare(strict_types=1);

/**
 * CHALLENGE SOLUTION — Lesson 2.0: Liskov Substitution Principle
 * ───────────────────────────────────────────────────────────────
 * ⚠️  Only open this file after completing starter.php yourself.
 *
 * Pattern applied in every fix:
 *   1. Identify what each class can ACTUALLY do.
 *   2. Split the contract into granular interfaces (ISP).
 *   3. Each class only implements the interfaces it can honestly honour.
 *   4. Calling functions type-hint at the lowest capability they need.
 *   5. No instanceof. No thrown stubs. No silent data loss.
 */


// ═══════════════════════════════════════════════════════════
// FIX 1 — Renderer hierarchy
// ═══════════════════════════════════════════════════════════

// Granular contracts — render() is universal, renderWithLayout() is not.
interface Renderer {
    public function render(string $content): string;
}

interface LayoutRenderer extends Renderer {
    public function renderWithLayout(string $content, string $layout): string;
}

// HtmlRenderer signs the full contract — it genuinely supports both.
class HtmlRenderer implements LayoutRenderer {
    public function render(string $content): string {
        return "[HTML] <div class=\"content\">{$content}</div>";
    }

    public function renderWithLayout(string $content, string $layout): string {
        return "[HTML+LAYOUT] <html><body><main>{$content}</main></body></html>";
    }
}

// RssRenderer only signs Renderer — it never promised layout support.
// No throwing. No instanceof. Honest contract.
class RssRenderer implements Renderer {
    public function render(string $content): string {
        return "[RSS] <item><description>{$content}</description></item>";
    }
}

// Two focused functions — no instanceof guards needed.
function renderContent(Renderer $renderer, string $content): void {
    echo $renderer->render($content) . "\n";
}

function renderWithLayout(LayoutRenderer $renderer, string $content, string $layout): void {
    echo $renderer->renderWithLayout($content, $layout) . "\n";
}


// ═══════════════════════════════════════════════════════════
// FIX 2 — Storage hierarchy
// ═══════════════════════════════════════════════════════════

interface ReadableStorage {
    public function find(int $id): ?array;
}

interface WritableStorage {
    public function persist(array $record): int;
}

// Full storage: both read and write.
interface Storage extends ReadableStorage, WritableStorage {}

class DatabaseStorage implements Storage {
    private array $db     = [];
    private int   $nextId = 1;

    public function persist(array $record): int {
        $id = $this->nextId++;
        $record['id'] = $id;
        $this->db[$id] = $record;
        echo "[DB] Persisted record with id={$id}\n";
        return $id;
    }

    public function find(int $id): ?array {
        return $this->db[$id] ?? null;
    }
}

// ✅ InMemoryStorage actually stores data now — postcondition honoured.
class InMemoryStorage implements Storage {
    private array $records = []; // Real in-memory store
    private int   $nextId  = 1;

    public function persist(array $record): int {
        $id = $this->nextId++;
        $record['id'] = $id;
        $this->records[$id] = $record; // Actually stored — not discarded.
        echo "[MEMORY] Persisted record with id={$id}\n";
        return $id;
    }

    public function find(int $id): ?array {
        return $this->records[$id] ?? null;
    }
}

// ✅ ReadOnlyFileStorage only signs ReadableStorage — it never promised writes.
class ReadOnlyFileStorage implements ReadableStorage {
    private array $fileData = [
        1 => ['id' => 1, 'name' => 'Alice (from file)'],
    ];

    public function find(int $id): ?array {
        $record = $this->fileData[$id] ?? null;
        if ($record) {
            echo "[FILE] Read record id={$id}: " . json_encode($record) . "\n";
        }
        return $record;
    }
    // No persist() — ReadOnlyFileStorage never signed WritableStorage.
}

// Two focused functions — type-hinted at the capability they actually need.
function persistRecord(WritableStorage $storage, array $record): int {
    return $storage->persist($record);
}

function retrieveRecord(ReadableStorage $storage, int $id): ?array {
    return $storage->find($id);
}


// ═══════════════════════════════════════════════════════════
// FIX 3 — Notification sender hierarchy
// ═══════════════════════════════════════════════════════════

interface BasicNotificationSender {
    public function send(string $to, string $message): bool;
}

interface RichNotificationSender extends BasicNotificationSender {
    public function addCcRecipient(string $cc): void;
}

// Email supports CC — signs the rich contract.
class EmailNotificationSender implements RichNotificationSender {
    private array $ccRecipients = [];

    public function addCcRecipient(string $cc): void {
        $this->ccRecipients[] = $cc;
    }

    public function send(string $to, string $message): bool {
        $cc = $this->ccRecipients ? ' | CC: ' . implode(', ', $this->ccRecipients) : '';
        echo "[EMAIL] To: {$to}{$cc} | Msg: {$message}\n";
        return true;
    }
}

// SMS only signs the basic contract — it never promised CC support.
class SmsNotificationSender implements BasicNotificationSender {
    public function send(string $to, string $message): bool {
        echo "[SMS]   To: {$to} | Msg: {$message}\n";
        return true;
    }
    // No addCcRecipient() — SmsNotificationSender never signed RichNotificationSender.
}

// Two focused functions — no instanceof guards.
function sendBasicNotification(BasicNotificationSender $sender, string $to, string $message): void {
    $sender->send($to, $message);
}

function sendRichNotification(RichNotificationSender $sender, string $to, string $message): void {
    $sender->addCcRecipient('manager@example.com');
    $sender->send($to, $message);
}


// ═══════════════════════════════════════════════════════════
// FIXED USAGE — clean, no guards, no surprises
// ═══════════════════════════════════════════════════════════

echo "=== Renderers ===\n";

$html = new HtmlRenderer();
$rss  = new RssRenderer();

// Both accept any Renderer:
renderContent($html, "Hello World");
renderContent($rss,  "Hello World");

// Only HtmlRenderer can be passed here — PHP prevents RssRenderer:
renderWithLayout($html, "Hello World", 'default');
// renderWithLayout($rss, "Hello World", 'default'); // ← PHP type error

echo "\n=== Storage ===\n";

$db     = new DatabaseStorage();
$memory = new InMemoryStorage();
$file   = new ReadOnlyFileStorage();

// Persist and retrieve — both full storage backends work:
$dbId  = persistRecord($db,     ['name' => 'Alice']);
$found = retrieveRecord($db, $dbId);
echo "[DB] Found: " . json_encode($found) . "\n";

$memId = persistRecord($memory, ['name' => 'Alice']);
$found = retrieveRecord($memory, $memId);
echo "[MEMORY] Found: " . json_encode($found) . "\n";

// ReadOnlyFileStorage can only be passed to retrieveRecord — not persistRecord:
$found = retrieveRecord($file, 1);
echo "(ReadOnlyFileStorage cannot be passed to persistRecord() — PHP prevents it)\n";
// persistRecord($file, ['name' => 'test']); // ← PHP type error

echo "\n=== Notifications ===\n";

$email = new EmailNotificationSender();
$sms   = new SmsNotificationSender();

// Rich notification — only email qualifies:
sendRichNotification($email, 'alice@example.com', 'Hello Alice');
// sendRichNotification($sms, ...); // ← PHP type error — correctly prevented

// Basic notification — both qualify:
sendBasicNotification($sms,   '+27821234567',    'Hello Bob');
sendBasicNotification($email, 'bob@example.com', 'Hello from basic sender');

echo "(SmsNotificationSender cannot be passed to sendRichNotification() — PHP prevents it)\n";


// ═══════════════════════════════════════════════════════════
// SELF-REVIEW CHECKLIST
// ═══════════════════════════════════════════════════════════
echo "\n--- Self-review checklist ---\n";
echo "[ ] No class throws BadMethodCallException for a method in its interface?\n";
echo "[ ] InMemoryStorage actually stores data in \$this->records (not silent no-op)?\n";
echo "[ ] ReadOnlyFileStorage only implements ReadableStorage (not WritableStorage)?\n";
echo "[ ] SmsNotificationSender only implements BasicNotificationSender?\n";
echo "[ ] Zero instanceof checks in any calling function?\n";
echo "[ ] PHP's type system (not runtime exceptions) prevents wrong types being passed?\n";