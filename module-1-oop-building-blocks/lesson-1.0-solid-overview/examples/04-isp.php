<?php
declare(strict_types=1);

/**
 * SOLID Example — I: Interface Segregation Principle
 * ────────────────────────────────────────────────────
 * "Clients should not be forced to depend on methods they do not use."
 *
 * Fat interfaces punish implementing classes. A class that can only read
 * should never be forced to stub out write() and delete() just to satisfy
 * a bloated contract. Keep interfaces small and focused.
 *
 * Scenario: A document management system with different storage backends.
 */

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  I — Interface Segregation Principle (ISP)          ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";


// ═══════════════════════════════════════════════════════════
// ❌ VIOLATION — One fat interface forces all implementors
//               to provide methods they cannot meaningfully support
// ═══════════════════════════════════════════════════════════
echo "── VIOLATION ──────────────────────────────────\n\n";

interface BadStorage {
    public function read(string $path): string;
    public function write(string $path, string $data): void;
    public function delete(string $path): void;
    public function listFiles(string $dir): array;
    public function compress(string $path): string;
    public function encrypt(string $path): string;
}

// A read-only in-memory cache CANNOT write, delete, compress, or encrypt.
// But the fat interface forces it to pretend it can.
class BadReadOnlyCache implements BadStorage {
    private array $data = ['config/app' => '{"env":"prod"}'];

    public function read(string $path): string {
        return $this->data[$path] ?? '';
    }

    // ❌ Forced stub — this class has no business implementing these:
    public function write(string $path, string $data): void {
        throw new \BadMethodCallException("Cache is read-only!");
    }
    public function delete(string $path): void {
        throw new \BadMethodCallException("Cache is read-only!");
    }
    public function listFiles(string $dir): array {
        throw new \BadMethodCallException("Listing not supported!");
    }
    public function compress(string $path): string {
        throw new \BadMethodCallException("Compression not supported!");
    }
    public function encrypt(string $path): string {
        throw new \BadMethodCallException("Encryption not supported!");
    }
}

$cache = new BadReadOnlyCache();
echo "[BAD] Read: " . $cache->read('config/app') . "\n";
echo "[BAD] Four other methods exist only to throw exceptions.\n";
echo "      Any caller that uses BadStorage and calls write() will crash.\n";


// ═══════════════════════════════════════════════════════════
// ✅ FIX — Small, focused interfaces. Each class implements only what it supports.
// ═══════════════════════════════════════════════════════════
echo "\n── FIX ─────────────────────────────────────────\n\n";

// Granular interfaces — each represents one capability
interface Readable {
    public function read(string $path): string;
    public function exists(string $path): bool;
}

interface Writable {
    public function write(string $path, string $data): void;
    public function delete(string $path): void;
}

interface Listable {
    /** @return string[] */
    public function listFiles(string $directory): array;
}

interface Compressible {
    public function compress(string $path): string;
}

// Composite — for backends that support full read/write
interface ReadWritable extends Readable, Writable {}


// Each implementation only signs the contracts it can honour.

class ReadOnlyCache implements Readable {
    private array $store = [
        'config/app'  => '{"env":"prod","version":"2.1"}',
        'config/db'   => '{"host":"db.internal","port":3306}',
    ];

    public function read(string $path): string {
        return $this->store[$path] ?? throw new \RuntimeException("Not found: {$path}");
    }

    public function exists(string $path): bool {
        return isset($this->store[$path]);
    }
    // Nothing else. No stubs. No exceptions. Just the two methods it actually supports.
}

class LocalDisk implements ReadWritable, Listable, Compressible {
    private array $disk = [];

    public function read(string $path): string {
        return $this->disk[$path] ?? throw new \RuntimeException("Not found: {$path}");
    }

    public function exists(string $path): bool {
        return isset($this->disk[$path]);
    }

    public function write(string $path, string $data): void {
        $this->disk[$path] = $data;
        echo "[DISK] Written: {$path}\n";
    }

    public function delete(string $path): void {
        unset($this->disk[$path]);
        echo "[DISK] Deleted: {$path}\n";
    }

    public function listFiles(string $directory): array {
        return array_values(array_filter(
            array_keys($this->disk),
            fn(string $p) => str_starts_with($p, $directory)
        ));
    }

    public function compress(string $path): string {
        $compressed = "COMPRESSED({$path})";
        echo "[DISK] Compressed: {$path}\n";
        return $compressed;
    }
}

class S3Bucket implements ReadWritable {
    private array $bucket = [];

    public function read(string $path): string {
        return $this->bucket[$path] ?? throw new \RuntimeException("S3 key not found: {$path}");
    }

    public function exists(string $path): bool {
        return isset($this->bucket[$path]);
    }

    public function write(string $path, string $data): void {
        $this->bucket[$path] = $data;
        echo "[S3] Put: s3://bucket/{$path}\n";
    }

    public function delete(string $path): void {
        unset($this->bucket[$path]);
        echo "[S3] Deleted: s3://bucket/{$path}\n";
    }
    // No listFiles() or compress() — S3 in this example doesn't need them.
}


// Functions type-hinted at the lowest capability level they actually need.
function loadConfiguration(Readable $storage, string $path): array {
    if (!$storage->exists($path)) {
        throw new \RuntimeException("Config not found: {$path}");
    }
    return json_decode($storage->read($path), true);
}

function backupFile(ReadWritable $storage, string $src, string $dest): void {
    $data = $storage->read($src);
    $storage->write($dest, $data);
    echo "[BACKUP] {$src} → {$dest}\n";
}

function printDirectory(Listable $storage, string $dir): void {
    echo "[LIST] Contents of '{$dir}':\n";
    foreach ($storage->listFiles($dir) as $file) {
        echo "  - {$file}\n";
    }
}


// Using all three backends:
echo "ReadOnlyCache:\n";
$cache = new ReadOnlyCache();
$config = loadConfiguration($cache, 'config/app');
echo "  env={$config['env']}, version={$config['version']}\n";

echo "\nLocalDisk:\n";
$disk = new LocalDisk();
$disk->write('uploads/image.png', '<binary data>');
$disk->write('uploads/report.pdf', '<pdf data>');
$disk->write('logs/app.log', 'INFO started');
printDirectory($disk, 'uploads/');
$disk->compress('uploads/image.png');

echo "\nS3Bucket:\n";
$s3 = new S3Bucket();
$s3->write('data/export.csv', 'id,name\n1,Alice');
backupFile($s3, 'data/export.csv', 'data/export-backup.csv');


// ═══════════════════════════════════════════════════════════
// Demonstrating why type-hinting at the right level matters
// ═══════════════════════════════════════════════════════════
echo "\n── Type-hint at the lowest needed capability ──\n\n";

// loadConfiguration() only needs Readable.
// It works with all three: ReadOnlyCache, LocalDisk, S3Bucket.
echo "loadConfiguration() with LocalDisk:\n";
$disk->write('config/app', '{"env":"dev","version":"3.0"}');
$devConfig = loadConfiguration($disk, 'config/app');
echo "  env={$devConfig['env']}, version={$devConfig['version']}\n";

echo "\n--- Recap ---\n";
echo "ISP: Keep interfaces small and focused — one capability per interface.\n";
echo "Test: Ask 'does any implementing class have to throw for any method?' — if yes, split the interface.\n";
echo "Payoff: No more stubs. No more surprise exceptions. Every method on every type is real.\n";
echo "Depth: Already demonstrated fully in Lesson 1.1 Examples 02 and 05.\n";