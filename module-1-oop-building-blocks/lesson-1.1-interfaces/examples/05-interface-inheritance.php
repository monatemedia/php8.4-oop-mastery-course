<?php
declare(strict_types=1);

/**
 * Example 05 — Interface Inheritance
 * -------------------------------------
 * An interface can extend one or more other interfaces using `extends`.
 * This lets you build a hierarchy of contracts from small, focused pieces
 * up to larger composite ones — without forcing every class to sign
 * the full contract when it only needs part of it.
 *
 * Scenario: A file storage system with granular capability interfaces.
 */

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  SOLID CALLOUT — I: Interface Segregation Principle (ISP)               ║
// ╠══════════════════════════════════════════════════════════════════════════╣
// ║  "Clients should not be forced to depend on methods they do not use."   ║
// ║                                                                          ║
// ║  This is the most complete ISP demonstration in the course.             ║
// ║  The hierarchy is built bottom-up from granular contracts:              ║
// ║                                                                          ║
// ║    Readable ──┐                                                          ║
// ║    Writable ──┴──▶ ReadWritable ──┐                                     ║
// ║    Listable ──────────────────────┴──▶ FullStorage                      ║
// ║                                                                          ║
// ║  loadConfig()     type-hints Readable  — accepts all 3 backends         ║
// ║  saveUpload()     type-hints ReadWritable — accepts LocalDisk + S3      ║
// ║  printDirectory() type-hints Listable  — accepts LocalDisk only         ║
// ║                                                                          ║
// ║  Each function asks for EXACTLY the capability it needs — no more.      ║
// ║  This is ISP applied to function signatures, not just class design.     ║
// ╚══════════════════════════════════════════════════════════════════════════╝

// ─────────────────────────────────────────────────────────────────────────────
// LEVEL 1 — Granular, single-purpose interfaces (Interface Segregation)
// ─────────────────────────────────────────────────────────────────────────────

interface Readable {
    public function read(string $path): string;
    public function exists(string $path): bool;
}

interface Writable {
    public function write(string $path, string $contents): void;
    public function delete(string $path): void;
}

interface Listable {
    /** @return string[] */
    public function listFiles(string $directory): array;
}


// ─────────────────────────────────────────────────────────────────────────────
// LEVEL 2 — Composite interfaces built by extending the granular ones
// ─────────────────────────────────────────────────────────────────────────────

// ReadWritable extends BOTH Readable and Writable.
// Any class implementing ReadWritable must fulfil ALL methods from
// Readable AND Writable AND any methods declared in ReadWritable itself.
interface ReadWritable extends Readable, Writable {
    public function copy(string $source, string $destination): void;
}

// FullStorage is the "kitchen sink" — everything.
interface FullStorage extends ReadWritable, Listable {
    public function move(string $source, string $destination): void;
    public function size(string $path): int;
}


// ─────────────────────────────────────────────────────────────────────────────
// Implementations — each signs only the contract it actually supports
// ─────────────────────────────────────────────────────────────────────────────

/**
 * A read-only cache — it can only read, not write.
 * Signs only the Readable contract.
 */
class ReadOnlyCache implements Readable {
    private array $store = [
        'config/app.json' => '{"debug":false,"version":"1.0"}',
        'config/db.json'  => '{"host":"localhost","port":3306}',
    ];

    public function read(string $path): string {
        return $this->store[$path] ?? throw new \RuntimeException("Not found: {$path}");
    }

    public function exists(string $path): bool {
        return isset($this->store[$path]);
    }
}

/**
 * A local disk driver — supports reading, writing, copying, and listing.
 * Signs the FullStorage contract (the broadest one).
 */
class LocalDiskDriver implements FullStorage {
    private array $disk = [];

    public function read(string $path): string {
        return $this->disk[$path] ?? throw new \RuntimeException("File not found: {$path}");
    }

    public function exists(string $path): bool {
        return isset($this->disk[$path]);
    }

    public function write(string $path, string $contents): void {
        echo "  [WRITE] {$path} (" . strlen($contents) . " bytes)\n";
        $this->disk[$path] = $contents;
    }

    public function delete(string $path): void {
        unset($this->disk[$path]);
        echo "  [DELETE] {$path}\n";
    }

    public function copy(string $source, string $destination): void {
        $this->disk[$destination] = $this->read($source);
        echo "  [COPY] {$source} → {$destination}\n";
    }

    public function move(string $source, string $destination): void {
        $this->copy($source, $destination);
        $this->delete($source);
        echo "  [MOVE] {$source} → {$destination}\n";
    }

    public function listFiles(string $directory): array {
        return array_filter(
            array_keys($this->disk),
            fn(string $path) => str_starts_with($path, $directory)
        );
    }

    public function size(string $path): int {
        return strlen($this->read($path));
    }
}

/**
 * A remote S3-style driver — read/write but no local listing.
 * Signs ReadWritable (not FullStorage).
 */
class S3Driver implements ReadWritable {
    private array $bucket = [];

    public function read(string $path): string {
        return $this->bucket[$path] ?? throw new \RuntimeException("S3 key not found: {$path}");
    }

    public function exists(string $path): bool {
        return isset($this->bucket[$path]);
    }

    public function write(string $path, string $contents): void {
        echo "  [S3 PUT] s3://my-bucket/{$path}\n";
        $this->bucket[$path] = $contents;
    }

    public function delete(string $path): void {
        unset($this->bucket[$path]);
        echo "  [S3 DELETE] s3://my-bucket/{$path}\n";
    }

    public function copy(string $source, string $destination): void {
        $this->bucket[$destination] = $this->read($source);
        echo "  [S3 COPY] {$source} → {$destination}\n";
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Functions type-hinted against different levels of the interface hierarchy
// ─────────────────────────────────────────────────────────────────────────────

// Only needs to read — accepts ReadOnlyCache, LocalDiskDriver, S3Driver (all Readable)
function loadConfig(Readable $storage, string $path): array {
    if (!$storage->exists($path)) {
        throw new \RuntimeException("Config file missing: {$path}");
    }
    return json_decode($storage->read($path), true);
}

// Needs to write — accepts LocalDiskDriver and S3Driver (both ReadWritable or FullStorage)
function saveUpload(ReadWritable $storage, string $path, string $content): void {
    $storage->write($path, $content);
    echo "  Upload saved to: {$path}\n";
}

// Needs listing — only LocalDiskDriver qualifies (implements FullStorage which includes Listable)
function printDirectory(Listable $storage, string $dir): void {
    $files = $storage->listFiles($dir);
    echo "  Files in '{$dir}':\n";
    foreach ($files as $file) {
        echo "    - {$file}\n";
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Running everything
// ─────────────────────────────────────────────────────────────────────────────

echo "=== ReadOnlyCache (implements Readable) ===\n";
$cache = new ReadOnlyCache();
$config = loadConfig($cache, 'config/app.json');
echo "  App version: {$config['version']}\n\n";

echo "=== LocalDiskDriver (implements FullStorage) ===\n";
$disk = new LocalDiskDriver();
saveUpload($disk, 'uploads/photo.jpg', str_repeat('x', 204800)); // 200 KB of 'x'
saveUpload($disk, 'uploads/doc.pdf',   str_repeat('y', 51200));  // 50 KB of 'y'
saveUpload($disk, 'logs/app.log',      "2024-01-15 INFO Started\n");

echo "\n";
printDirectory($disk, 'uploads/');

echo "\n=== S3Driver (implements ReadWritable) ===\n";
$s3 = new S3Driver();
saveUpload($s3, 'backups/db.sql', 'sql dump content here');
$s3->copy('backups/db.sql', 'backups/db-archive.sql');

// ─────────────────────────────────────────────────────────────────────────────
// instanceof checks — an object satisfies ALL interfaces in its hierarchy
// ─────────────────────────────────────────────────────────────────────────────

echo "\n=== instanceof hierarchy checks ===\n\n";

$drivers = [
    'ReadOnlyCache'  => $cache,
    'LocalDiskDriver'=> $disk,
    'S3Driver'       => $s3,
];

$interfaces = [Readable::class, Writable::class, Listable::class, ReadWritable::class, FullStorage::class];

foreach ($drivers as $name => $driver) {
    echo "{$name}:\n";
    foreach ($interfaces as $iface) {
        $short = (new \ReflectionClass($iface))->getShortName();
        echo "  {$short}: " . ($driver instanceof $iface ? '✓' : '✗') . "\n";
    }
    echo "\n";
}

echo "--- Recap ---\n";
echo "1. Interface extends interface — builds a hierarchy of contracts.\n";
echo "2. An interface can extend MULTIPLE interfaces at once.\n";
echo "3. Implementing a child interface requires ALL methods from ALL parents too.\n";
echo "4. Type-hint at the LOWEST level your function actually needs (ISP).\n";
echo "5. instanceof returns true for every interface in the object's contract chain.\n";