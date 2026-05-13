<?php
declare(strict_types=1);

/**
 * CHALLENGE STARTER — Lesson 2.0: Liskov Substitution Principle
 * ───────────────────────────────────────────────────────────────
 * Read CHALLENGE.md before touching this file.
 *
 * This file runs without fatal errors — but three hierarchies are
 * LSP violations waiting to cause bugs in production.
 *
 * YOUR JOB: Restructure each hierarchy so substitution is safe.
 * See CHALLENGE.md for exact tasks and acceptance criteria.
 *
 * Rules:
 *  - Remove ALL instanceof guards from calling functions.
 *  - Remove ALL thrown BadMethodCallException / LogicException stubs.
 *  - Remove ALL silent no-op methods that discard data.
 *  - Do NOT look at solution.php until you have made a genuine attempt.
 */


// ═══════════════════════════════════════════════════════════
// VIOLATION 1 — Content Renderer hierarchy
// ═══════════════════════════════════════════════════════════

// TODO: Replace this with a split interface (Renderer + LayoutRenderer)

class HtmlRenderer {
    public function render(string $content): string {
        return "<div class=\"content\">{$content}</div>";
    }

    public function renderWithLayout(string $content, string $layout): string {
        return "<html><body><main>{$content}</main></body></html>";
    }
}

class RssRenderer extends HtmlRenderer {
    public function render(string $content): string {
        return "<item><description>{$content}</description></item>";
    }

    public function renderWithLayout(string $content, string $layout): string {
        // ❌ LSP VIOLATION: RSS has no layout. Throws instead of substituting safely.
        throw new \BadMethodCallException("RSS does not support layouts!");
    }
}

// ❌ This function has to guard against RssRenderer with instanceof.
function renderPage(HtmlRenderer $renderer, string $content): void {
    echo $renderer->render($content) . "\n";

    // ❌ instanceOf guard — a red flag that the hierarchy is broken.
    if (!($renderer instanceof RssRenderer)) {
        echo $renderer->renderWithLayout($content, 'default') . "\n";
    }
}

// TODO: Replace renderPage() with two focused functions:
//   renderContent(Renderer $r, string $content)
//   renderWithLayout(LayoutRenderer $r, string $content, string $layout)


// ═══════════════════════════════════════════════════════════
// VIOLATION 2 — Storage backend hierarchy
// ═══════════════════════════════════════════════════════════

// TODO: Replace this with split interfaces (ReadableStorage, WritableStorage, Storage)

class DatabaseStorage {
    private array $db = [];
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

class InMemoryStorage extends DatabaseStorage {
    public function persist(array $record): int {
        // ❌ LSP VIOLATION: Postcondition broken — data is silently discarded.
        // Callers that save and then read back will get null.
        echo "[MEMORY] Pretending to persist...\n";
        return 0; // Fake ID — nothing was actually stored.
    }
}

class ReadOnlyFileStorage extends DatabaseStorage {
    private array $fileData = [
        1 => ['id' => 1, 'name' => 'Alice (from file)'],
    ];

    public function find(int $id): ?array {
        return $this->fileData[$id] ?? null;
    }

    public function persist(array $record): int {
        // ❌ LSP VIOLATION: File storage is read-only. Throws instead of substituting.
        throw new \BadMethodCallException("File storage is read-only!");
    }
}

function saveAndRetrieve(DatabaseStorage $storage, array $record): void {
    $id = $storage->persist($record);
    $found = $storage->find($id);
    echo "[FOUND] " . ($found ? json_encode($found) : "NOTHING — data was lost!") . "\n";
}

// TODO: Replace saveAndRetrieve() with focused functions:
//   persistRecord(WritableStorage $s, array $record): int
//   retrieveRecord(ReadableStorage $s, int $id): ?array


// ═══════════════════════════════════════════════════════════
// VIOLATION 3 — Notification sender hierarchy
// ═══════════════════════════════════════════════════════════

// TODO: Replace this with split interfaces (BasicNotificationSender + RichNotificationSender)

class EmailNotificationSender {
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

class SmsNotificationSender extends EmailNotificationSender {
    public function addCcRecipient(string $cc): void {
        // ❌ LSP VIOLATION: SMS has no CC. Throws when caller uses the inherited method.
        throw new \BadMethodCallException("SMS does not support CC recipients!");
    }

    public function send(string $to, string $message): bool {
        echo "[SMS]   To: {$to} | Msg: {$message}\n";
        return true;
    }
}

// ❌ This function has an instanceof guard — a red flag.
function sendRichNotification(EmailNotificationSender $sender, string $to, string $message): void {
    // ❌ Must guard against SmsNotificationSender to avoid a crash.
    if (!($sender instanceof SmsNotificationSender)) {
        $sender->addCcRecipient('manager@example.com');
    }
    $sender->send($to, $message);
}

// TODO: Replace sendRichNotification() with two focused functions:
//   sendBasicNotification(BasicNotificationSender $s, string $to, string $msg)
//   sendRichNotification(RichNotificationSender $s, string $to, string $msg)


// ═══════════════════════════════════════════════════════════
// CURRENT (BROKEN) USAGE — shows the violations in action
// ═══════════════════════════════════════════════════════════

echo "=== BROKEN USAGE (before your fix) ===\n\n";

echo "Renderers:\n";
$html = new HtmlRenderer();
$rss  = new RssRenderer();
renderPage($html, "Hello World");
renderPage($rss,  "Hello World"); // Only renders once — layout silently skipped

echo "\nStorage:\n";
$db     = new DatabaseStorage();
$memory = new InMemoryStorage();
saveAndRetrieve($db,     ['name' => 'Alice']);
saveAndRetrieve($memory, ['name' => 'Alice']); // Data is lost

echo "\nNotifications:\n";
$email = new EmailNotificationSender();
$sms   = new SmsNotificationSender();
sendRichNotification($email, 'alice@example.com', 'Hello Alice');
sendRichNotification($sms,   '+27821234567',      'Hello Bob'); // CC silently skipped

echo "\n\n=== YOUR FIXED USAGE BELOW ===\n\n";

// TODO: Add your fixed interfaces, classes, and function calls here.
// When you are done, the output should match the expected output in CHALLENGE.md.
// No instanceof guards. No thrown stubs. No silent data loss.