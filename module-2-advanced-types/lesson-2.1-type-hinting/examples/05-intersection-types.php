<?php
declare(strict_types=1);

/**
 * Example 05 — Intersection Types (PHP 8.1+)
 * --------------------------------------------
 * An intersection type requires a value to satisfy ALL listed types at once.
 * Written with & between types: Countable&Traversable
 *
 * This is the logical AND to union's logical OR:
 *   int|string      → accepts int OR string (either one is fine)
 *   Countable&Iterator → must implement BOTH Countable AND Iterator
 *
 * Intersection types only work with named types (class/interface names).
 * You cannot intersect scalars: int&string is a fatal error.
 *
 * Scenario: A collection pipeline that enforces multiple interface contracts.
 */

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  Intersection Types (PHP 8.1+)                     ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";


// ─────────────────────────────────────────────────────────────────────────────
// The interfaces we will intersect
// ─────────────────────────────────────────────────────────────────────────────

interface Countable2 {   // Named Countable2 to avoid conflict with PHP's built-in
    public function count(): int;
}

interface Iterable2 {    // Named Iterable2 — built-in Traversable is used below
    public function toArray(): array;
}

interface Filterable {
    /** @return static */
    public function filter(callable $predicate): static;
}

interface Sortable {
    /** @return static */
    public function sortBy(callable $comparator): static;
}

interface Serialisable2 {
    public function toJson(): string;
}


// ─────────────────────────────────────────────────────────────────────────────
// A collection class that implements multiple interfaces
// ─────────────────────────────────────────────────────────────────────────────

class Collection implements Countable2, Iterable2, Filterable, Sortable, Serialisable2 {
    public function __construct(private array $items = []) {}

    public function count(): int { return count($this->items); }

    public function toArray(): array { return $this->items; }

    public function filter(callable $predicate): static {
        return new static(array_values(array_filter($this->items, $predicate)));
    }

    public function sortBy(callable $comparator): static {
        $items = $this->items;
        usort($items, $comparator);
        return new static($items);
    }

    public function toJson(): string {
        return json_encode($this->items, JSON_PRETTY_PRINT);
    }

    public function first(): mixed {
        return $this->items[0] ?? null;
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Functions using intersection types
// ─────────────────────────────────────────────────────────────────────────────

// Requires BOTH Countable2 AND Serialisable2 — must satisfy both contracts
function exportWithCount(Countable2&Serialisable2 $data): string {
    $count = $data->count();
    echo "  Exporting {$count} item(s)...\n";
    return $data->toJson();
}

// Requires BOTH Filterable AND Countable2 — must support both operations
function filterAndReport(Filterable&Countable2 $collection, callable $predicate): void {
    $before   = $collection->count();
    $filtered = $collection->filter($predicate);
    $after    = $filtered->count();
    echo "  Before: {$before} | After filter: {$after} | Removed: " . ($before - $after) . "\n";
}

// Requires ALL FOUR — only classes implementing all four are accepted
function processCollection(Countable2&Filterable&Sortable&Serialisable2 $collection): string {
    echo "  Items: " . $collection->count() . "\n";
    $sorted = $collection->sortBy(fn($a, $b) => $a['value'] <=> $b['value']);
    return $sorted->toJson();
}


// ─────────────────────────────────────────────────────────────────────────────
// Using the functions
// ─────────────────────────────────────────────────────────────────────────────

echo "── Basic intersection type usage ─────────────────────\n\n";

$products = new Collection([
    ['name' => 'Widget C', 'value' => 430,  'stock' => 12],
    ['name' => 'Widget A', 'value' => 1200, 'stock' => 5],
    ['name' => 'Widget B', 'value' => 850,  'stock' => 0],
    ['name' => 'Widget D', 'value' => 299,  'stock' => 20],
]);

echo "exportWithCount:\n";
$json = exportWithCount($products);
echo substr($json, 0, 80) . "...\n";

echo "\nfilterAndReport (in-stock only):\n";
filterAndReport($products, fn($p) => $p['stock'] > 0);

echo "\nprocessCollection (sort by value):\n";
$result = processCollection($products);
echo "First item after sort: "
   . json_encode(json_decode($result, true)[0]) . "\n";


// ─────────────────────────────────────────────────────────────────────────────
// Intersection with PHP built-in interfaces
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Intersection with PHP built-ins ───────────────────\n\n";

// PHP's standard \Countable and \Traversable (Iterator extends Traversable)
class NumberRange implements \Countable, \Iterator {
    private int $current;

    public function __construct(
        private int $start,
        private int $end
    ) {
        $this->current = $start;
    }

    // Countable
    public function count(): int { return max(0, $this->end - $this->start + 1); }

    // Iterator
    public function current(): int    { return $this->current; }
    public function key(): int        { return $this->current - $this->start; }
    public function next(): void      { $this->current++; }
    public function rewind(): void    { $this->current = $this->start; }
    public function valid(): bool     { return $this->current <= $this->end; }
}

// This function requires BOTH \Countable AND \Traversable
function paginate(\Countable&\Traversable $items, int $perPage): array {
    $total = count($items);
    $pages = (int) ceil($total / $perPage);
    $result = [];

    echo "  Total: {$total} | Per page: {$perPage} | Pages: {$pages}\n";

    $i = 0;
    $page = 1;
    $bucket = [];

    foreach ($items as $item) {
        $bucket[] = $item;
        $i++;
        if ($i % $perPage === 0) {
            $result[$page++] = $bucket;
            $bucket = [];
        }
    }
    if (!empty($bucket)) {
        $result[$page] = $bucket;
    }

    return $result;
}

$range = new NumberRange(1, 10);
echo "NumberRange(1–10):\n";
$pages = paginate($range, 3);
foreach ($pages as $pageNum => $items) {
    echo "  Page {$pageNum}: " . implode(', ', $items) . "\n";
}


// ─────────────────────────────────────────────────────────────────────────────
// Intersection types with null — use (A&B)|null
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Nullable intersection: (A&B)|null ────────────────\n\n";

// Pure intersection types cannot include null directly.
// Use a union with null instead: (Countable&Traversable)|null
// This is called a "DNF type" (Disjunctive Normal Form) — PHP 8.2+

function findCollection(string $key): (\Countable&\Traversable)|null {
    $store = ['range' => new NumberRange(1, 5)];
    return $store[$key] ?? null;
}

$found = findCollection('range');
if ($found !== null) {
    echo "Found — count: " . count($found) . "\n";
    foreach ($found as $n) {
        echo "  {$n}";
    }
    echo "\n";
}

$notFound = findCollection('missing');
echo "Missing: " . var_export($notFound, true) . "\n";


// ─────────────────────────────────────────────────────────────────────────────
// What intersection types CANNOT do
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── What intersection types cannot do ────────────────\n\n";

echo "Cannot intersect scalar types:\n";
echo "  int&string       ← Fatal error — scalars cannot be intersected\n";
echo "  string&Countable ← Fatal error — cannot mix scalar and object type\n\n";

echo "Cannot use nullable shorthand:\n";
echo "  ?Countable&Iterator  ← Parse error — use (Countable&Iterator)|null\n\n";

echo "Cannot intersect the same type with itself:\n";
echo "  Countable&Countable  ← Fatal error — redundant intersection\n\n";

echo "Must use named types only:\n";
echo "  array&Countable      ← Fatal error — array is not a named type\n";


// ─────────────────────────────────────────────────────────────────────────────
// Real-world example: repository that is both readable and cacheable
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Real-world: readable + cacheable repository ──────\n\n";

interface Readable {
    public function findById(int $id): ?array;
    public function findAll(): array;
}

interface Cacheable {
    public function getCacheKey(string $method, mixed ...$args): string;
    public function getCacheTtl(): int;
}

class UserRepository implements Readable, Cacheable {
    private array $users = [
        1 => ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
        2 => ['id' => 2, 'name' => 'Bob',   'email' => 'bob@example.com'],
    ];

    public function findById(int $id): ?array {
        return $this->users[$id] ?? null;
    }

    public function findAll(): array {
        return array_values($this->users);
    }

    public function getCacheKey(string $method, mixed ...$args): string {
        return 'user_repo:' . $method . ':' . implode(':', $args);
    }

    public function getCacheTtl(): int { return 300; }
}

// Requires a repository that is BOTH readable AND cacheable
function warmRepositoryCache(Readable&Cacheable $repo): void {
    $ttl = $repo->getCacheTtl();
    $all = $repo->findAll();

    foreach ($all as $record) {
        $key = $repo->getCacheKey('findById', $record['id']);
        echo "  [CACHE] SET {$key} (ttl={$ttl}s)\n";
    }

    echo "  [CACHE] Warmed " . count($all) . " records.\n";
}

$userRepo = new UserRepository();
warmRepositoryCache($userRepo);

echo "\n--- Recap ---\n";
echo "A&B: value must implement ALL listed types — the logical AND.\n";
echo "Only works with named types (interfaces, class names) — not scalars.\n";
echo "Nullable: use (A&B)|null — cannot use ?A&B.\n";
echo "Use case: enforcing multiple capability contracts on a single parameter.\n";
echo "Pairs well with ISP: small interfaces composed via intersection at the call site.\n";