<?php
declare(strict_types=1);

/**
 * Example 04 — self, static, and parent Return Types
 * ----------------------------------------------------
 * Three special return type keywords for use inside class methods:
 *
 *   self    — returns an instance of the class where the method is DEFINED
 *   static  — returns an instance of the class that was CALLED at runtime (LSB)
 *   parent  — returns an instance of the parent class (rare)
 *
 * The self vs static distinction is the most important thing to understand here.
 * Getting it wrong breaks method chaining in subclasses.
 *
 * Scenario: A fluent query builder to show the difference clearly,
 * then a real-world model factory pattern.
 */

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  self, static, and parent Return Types             ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 1 — The problem: why self breaks in subclasses
// ─────────────────────────────────────────────────────────────────────────────

echo "── Part 1: Why self breaks in subclasses ────────────\n\n";

class BadQueryBuilder {
    protected array $conditions = [];
    protected ?int  $limitVal   = null;

    // ❌ self means "always return BadQueryBuilder" — even for subclasses
    public function where(string $col, mixed $val): self {
        $this->conditions[] = "{$col} = " . var_export($val, true);
        return $this;
    }

    public function limit(int $n): self {
        $this->limitVal = $n;
        return $this;
    }

    public function toSql(): string {
        $where = empty($this->conditions) ? '' : ' WHERE ' . implode(' AND ', $this->conditions);
        $limit = $this->limitVal !== null ? " LIMIT {$this->limitVal}" : '';
        return "SELECT *{$where}{$limit}";
    }
}

class BadUserQueryBuilder extends BadQueryBuilder {
    // This method is on the CHILD class
    public function active(): self {
        return $this->where('status', 'active');
        // where() returns BadQueryBuilder (self of parent) — not BadUserQueryBuilder
        // So calling ->active() returns a BadQueryBuilder, not a BadUserQueryBuilder
    }

    public function admins(): self {
        return $this->where('role', 'admin');
    }
}

// This WORKS fine at runtime in PHP — but a static analyser would complain
// because ->where() claims to return BadQueryBuilder, making ->admins() invisible.
$q = (new BadUserQueryBuilder())->active()->where('age', 25)->limit(10);
echo $q->toSql() . "\n";
echo "Works at runtime, but static analysis sees where() returning BadQueryBuilder\n";
echo "so it would report that ->admins() is undefined on the returned type.\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 2 — The fix: static (Late Static Binding)
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 2: static — the correct return type ─────────\n\n";

class QueryBuilder {
    protected array  $conditions = [];
    protected array  $columns    = ['*'];
    protected ?int   $limitVal   = null;
    protected ?int   $offsetVal  = null;
    protected ?string $orderCol  = null;
    protected string  $orderDir  = 'ASC';

    // ✅ static means "return whatever concrete class was actually called"
    public function select(string ...$columns): static {
        $this->columns = $columns;
        return $this;
    }

    public function where(string $col, mixed $val): static {
        $this->conditions[] = "{$col} = " . var_export($val, true);
        return $this;
    }

    public function limit(int $n): static {
        $this->limitVal = $n;
        return $this;
    }

    public function offset(int $n): static {
        $this->offsetVal = $n;
        return $this;
    }

    public function orderBy(string $col, string $dir = 'ASC'): static {
        $this->orderCol = $col;
        $this->orderDir = strtoupper($dir);
        return $this;
    }

    public function toSql(): string {
        $cols   = implode(', ', $this->columns);
        $where  = empty($this->conditions) ? '' : ' WHERE ' . implode(' AND ', $this->conditions);
        $order  = $this->orderCol ? " ORDER BY {$this->orderCol} {$this->orderDir}" : '';
        $limit  = $this->limitVal  !== null ? " LIMIT {$this->limitVal}"   : '';
        $offset = $this->offsetVal !== null ? " OFFSET {$this->offsetVal}" : '';
        return "SELECT {$cols}{$where}{$order}{$limit}{$offset}";
    }
}

class UserQueryBuilder extends QueryBuilder {
    private string $table = 'users';

    // ✅ Returns static — so callers get UserQueryBuilder back from where() etc.
    public function active(): static {
        return $this->where('status', 'active');
    }

    public function withRole(string $role): static {
        return $this->where('role', $role);
    }

    public function toSql(): string {
        return str_replace('SELECT', "SELECT FROM {$this->table}", parent::toSql());
    }
}

class ProductQueryBuilder extends QueryBuilder {
    private string $table = 'products';

    public function inStock(): static {
        return $this->where('stock', '>0');
    }

    public function underPrice(float $price): static {
        return $this->where('price', "< {$price}");
    }

    public function toSql(): string {
        return str_replace('SELECT', "SELECT FROM {$this->table}", parent::toSql());
    }
}

// Full chain — every method returns the correct subclass type
$userQuery = (new UserQueryBuilder())
    ->active()
    ->withRole('admin')
    ->select('id', 'email', 'role')
    ->orderBy('email')
    ->limit(20);

echo "UserQuery: " . $userQuery->toSql() . "\n";
echo "Type: " . get_class($userQuery) . " ✓\n"; // UserQueryBuilder — not QueryBuilder

$productQuery = (new ProductQueryBuilder())
    ->inStock()
    ->underPrice(500.00)
    ->select('sku', 'name', 'price')
    ->orderBy('price', 'ASC')
    ->limit(10)
    ->offset(20);

echo "\nProductQuery: " . $productQuery->toSql() . "\n";
echo "Type: " . get_class($productQuery) . " ✓\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 3 — self IS correct when you don't want subclasses to change the type
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 3: When self IS the right choice ────────────\n\n";

// A singleton: we want to return THIS class, not a subclass
class Config {
    private static ?self $instance = null;  // self here = Config
    private array $data = [];

    private function __construct() {}

    // self is correct: getInstance() always returns Config (not a subclass)
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set(string $key, mixed $value): static {
        // But fluent setters should use static so subclasses chain correctly
        $this->data[$key] = $value;
        return $this;
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->data[$key] ?? $default;
    }
}

$config = Config::getInstance();
$config->set('app.name', 'PHP Mastery')->set('app.env', 'production');

echo "App name: " . $config->get('app.name') . "\n";
echo "App env:  " . $config->get('app.env') . "\n";
echo "Same instance? " . ($config === Config::getInstance() ? 'YES' : 'NO') . "\n";


// ─────────────────────────────────────────────────────────────────────────────
// PART 4 — static in named constructors (factory methods)
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Part 4: static in named constructors ─────────────\n\n";

class Model {
    protected array $attributes;
    protected bool  $isNew = true;

    protected function __construct(array $attributes) {
        $this->attributes = $attributes;
    }

    // Named constructor — static ensures subclasses get the right type back
    public static function create(array $attributes): static {
        $instance         = new static($attributes); // new static = runtime class
        $instance->isNew  = true;
        return $instance;
    }

    public static function fromDatabase(array $row): static {
        $instance        = new static($row);
        $instance->isNew = false;
        return $instance;
    }

    public function get(string $key): mixed {
        return $this->attributes[$key] ?? null;
    }

    public function isNew(): bool { return $this->isNew; }
}

class UserModel extends Model {
    public function getEmail(): string {
        return $this->attributes['email'] ?? '';
    }

    public function isAdmin(): bool {
        return ($this->attributes['role'] ?? '') === 'admin';
    }
}

// create() returns static — so we get UserModel back, not just Model
$newUser = UserModel::create(['email' => 'eve@example.com', 'role' => 'admin']);
$dbUser  = UserModel::fromDatabase(['email' => 'frank@example.com', 'role' => 'user', 'id' => 42]);

echo "New user:    " . $newUser->getEmail() . " | isNew=" . ($newUser->isNew() ? 'YES' : 'NO') . "\n";
echo "DB user:     " . $dbUser->getEmail()  . " | isNew=" . ($dbUser->isNew()  ? 'YES' : 'NO') . "\n";
echo "Types: " . get_class($newUser) . ", " . get_class($dbUser) . "\n";


// ─────────────────────────────────────────────────────────────────────────────
// Quick comparison table printed as output
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Summary: self vs static ──────────────────────────\n\n";
echo "  self   — always refers to the class where the method IS WRITTEN\n";
echo "         → use for singletons, internal helpers, non-inheritable factories\n\n";
echo "  static — refers to the class that WAS CALLED at runtime\n";
echo "         → use for fluent builders, named constructors, anything that will be inherited\n\n";
echo "  Rule:  when in doubt, use static — it is almost always what you want\n";
echo "         in inheritable methods. Use self only when you have a deliberate reason.\n";

echo "\n--- Recap ---\n";
echo "self:    resolves to the class where the method is defined — fixed at compile time.\n";
echo "static:  resolves to the runtime class — correct for fluent APIs and factories.\n";
echo "parent:  returns the parent type — very rare, use with care.\n";
echo "Rule:    prefer static in any method designed to be inherited.\n";