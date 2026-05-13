<?php
declare(strict_types=1);

/**
 * SOLID Example — D: Dependency Inversion Principle
 * ───────────────────────────────────────────────────
 * "High-level modules should not depend on low-level modules.
 *  Both should depend on abstractions.
 *  Abstractions should not depend on details.
 *  Details should depend on abstractions."
 *  — Robert C. Martin
 *
 * This is the PREVIEW. Full coverage is in Modules 3 and 4.
 * Here you need to recognise the violation and understand the direction of the fix.
 *
 * Scenario: A report generation service that fetches data and sends it by email.
 */

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  D — Dependency Inversion Principle (DIP)           ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";


// ═══════════════════════════════════════════════════════════
// ❌ VIOLATION — High-level ReportService depends directly
//               on low-level concrete classes
// ═══════════════════════════════════════════════════════════
echo "── VIOLATION ──────────────────────────────────\n\n";

// Low-level modules (details)
class MySqlDatabase {
    public function query(string $sql): array {
        echo "[MYSQL] Running: {$sql}\n";
        return [['id' => 1, 'name' => 'Widget A', 'sales' => 420]];
    }
}

class SmtpMailer {
    public function send(string $to, string $body): void {
        echo "[SMTP] Sending to {$to}: {$body}\n";
    }
}

// High-level module — directly creates its low-level dependencies
class BadReportService {
    private MySqlDatabase $db;     // ❌ Hardwired to MySQL
    private SmtpMailer    $mailer; // ❌ Hardwired to SMTP

    public function __construct() {
        $this->db     = new MySqlDatabase(); // ❌ Cannot be swapped
        $this->mailer = new SmtpMailer();    // ❌ Cannot be swapped
    }

    public function sendWeeklySalesReport(string $recipient): void {
        $rows   = $this->db->query("SELECT * FROM sales WHERE week = CURRENT_WEEK()");
        $report = "Weekly Sales:\n";
        foreach ($rows as $row) {
            $report .= "  {$row['name']}: {$row['sales']} units\n";
        }
        $this->mailer->send($recipient, $report);
    }
}

$bad = new BadReportService();
$bad->sendWeeklySalesReport('manager@example.com');
echo "[BAD] Cannot test without a real MySQL connection and SMTP server.\n";
echo "[BAD] Cannot switch to PostgreSQL or SendGrid without editing ReportService.\n";


// ═══════════════════════════════════════════════════════════
// ✅ FIX — Both sides depend on abstractions (interfaces)
// ═══════════════════════════════════════════════════════════
echo "\n── FIX ─────────────────────────────────────────\n\n";

// Abstractions (the layer both sides depend on)
interface DataSource {
    public function fetchSalesData(): array;
}

interface Mailer {
    public function send(string $to, string $subject, string $body): void;
}

// Low-level details depend on the abstractions (implement the interfaces)
class MySqlDataSource implements DataSource {
    public function fetchSalesData(): array {
        echo "[MYSQL] Fetching sales data...\n";
        return [
            ['name' => 'Widget A', 'sales' => 420],
            ['name' => 'Widget B', 'sales' => 310],
        ];
    }
}

class PostgresDataSource implements DataSource {
    public function fetchSalesData(): array {
        echo "[POSTGRES] Fetching sales data...\n";
        return [
            ['name' => 'Widget A', 'sales' => 418],
            ['name' => 'Widget B', 'sales' => 312],
        ];
    }
}

class SmtpMailerService implements Mailer {
    public function send(string $to, string $subject, string $body): void {
        echo "[SMTP] To: {$to} | Subject: {$subject}\n{$body}\n";
    }
}

class LogMailer implements Mailer {
    public function send(string $to, string $subject, string $body): void {
        echo "[LOG MAILER] (Not sent — logged only) To: {$to} | Subject: {$subject}\n";
    }
}

// In-memory fake — perfect for tests, no infrastructure needed
class FakeDataSource implements DataSource {
    public function fetchSalesData(): array {
        return [['name' => 'Test Widget', 'sales' => 999]];
    }
}

// High-level module — depends only on abstractions
class ReportService {
    // ✅ Both dependencies are interfaces — no concrete class names here
    public function __construct(
        private DataSource $dataSource,
        private Mailer     $mailer
    ) {}

    public function sendWeeklySalesReport(string $recipient): void {
        $rows   = $this->dataSource->fetchSalesData();
        $report = "Weekly Sales Report:\n";
        foreach ($rows as $row) {
            $report .= "  {$row['name']}: {$row['sales']} units\n";
        }
        $this->mailer->send($recipient, 'Weekly Sales Report', $report);
    }
}

// Production wiring — swap either dependency with one line change
echo "Production (MySQL + SMTP):\n";
$service = new ReportService(
    new MySqlDataSource(),
    new SmtpMailerService()
);
$service->sendWeeklySalesReport('manager@example.com');

echo "\nAlternate wiring (Postgres + Log):\n";
$service = new ReportService(
    new PostgresDataSource(),
    new LogMailer()
);
$service->sendWeeklySalesReport('manager@example.com');

echo "\nTest wiring (Fake data + Log — no infrastructure needed):\n";
$service = new ReportService(
    new FakeDataSource(),
    new LogMailer()
);
$service->sendWeeklySalesReport('test@example.com');


// ═══════════════════════════════════════════════════════════
// THE INVERSION VISUALISED
// ═══════════════════════════════════════════════════════════
echo "\n── The inversion ─────────────────────────────\n";
echo "\n  BEFORE (violation):\n";
echo "  ReportService ──depends on──▶ MySqlDatabase (concrete)\n";
echo "  ReportService ──depends on──▶ SmtpMailer    (concrete)\n";
echo "\n  AFTER (fix):\n";
echo "  ReportService  ──depends on──▶ DataSource (interface)\n";
echo "  MySqlDatabase  ──depends on──▶ DataSource (interface) [implements it]\n";
echo "  ReportService  ──depends on──▶ Mailer     (interface)\n";
echo "  SmtpMailer     ──depends on──▶ Mailer     (interface) [implements it]\n";
echo "\n  Both high-level and low-level modules now point AT the abstraction.\n";
echo "  The direction of the dependency has been inverted.\n";

echo "\n--- Recap ---\n";
echo "DIP: High-level modules depend on interfaces, not concrete classes.\n";
echo "Test: Ask 'does this constructor call new?' — if yes in a business-logic class, apply DIP.\n";
echo "Payoff: Testable, swappable, infrastructure-agnostic business logic.\n";
echo "Depth: Full treatment in Module 3 (manual DI) and Module 4 (container automation).\n";