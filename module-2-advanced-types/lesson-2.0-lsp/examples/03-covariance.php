<?php
declare(strict_types=1);

/**
 * LSP Example 03 — Covariance (Return Types)
 * --------------------------------------------
 * Covariance: an overriding method's return type can be a SUBTYPE (more specific).
 * This is safe because the caller asked for T and received S — and S IS a T.
 *
 * PHP enforces covariance on return types since PHP 7.4.
 * Trying to return a WIDER (parent) type than the interface promises is a fatal error.
 *
 * Scenario: Factories and repositories that return progressively more specific types.
 */

echo "╔══════════════════════════════════════════════════╗\n";
echo "║  LSP — Covariance: Return Types                 ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";


// ─────────────────────────────────────────────────────────────────────────────
// The type hierarchy we will work with
// ─────────────────────────────────────────────────────────────────────────────

abstract class Vehicle {
    abstract public function describe(): string;
    public function type(): string { return 'Vehicle'; }
}

class Car extends Vehicle {
    public function describe(): string { return "A car."; }
    public function type(): string { return 'Car'; }
    public function honk(): string { return "Beep!"; }
}

class ElectricCar extends Car {
    public function describe(): string { return "An electric car."; }
    public function type(): string { return 'ElectricCar'; }
    public function chargeBattery(): string { return "Charging..."; }
}


// ─────────────────────────────────────────────────────────────────────────────
// Factory interfaces with covariant return types
// ─────────────────────────────────────────────────────────────────────────────

interface VehicleFactory {
    public function create(): Vehicle; // Returns Vehicle (the base contract)
}

// ✅ COVARIANT: CarFactory::create() returns Car — a subtype of Vehicle.
// Safe because the caller got a Vehicle (or better). No expectations broken.
interface CarFactory extends VehicleFactory {
    public function create(): Car; // Narrower return type — allowed
}

// ✅ COVARIANT: ElectricCarFactory::create() returns ElectricCar — subtype of Car.
interface ElectricCarFactory extends CarFactory {
    public function create(): ElectricCar; // Even narrower — still allowed
}


// ─────────────────────────────────────────────────────────────────────────────
// Concrete factory implementations
// ─────────────────────────────────────────────────────────────────────────────

class StandardVehicleFactory implements VehicleFactory {
    public function create(): Vehicle {
        return new Car(); // Could return any Vehicle subtype here
    }
}

class SportCarFactory implements CarFactory {
    public function create(): Car {
        echo "[FACTORY] Building a Car...\n";
        return new Car();
    }
}

class TeslaFactory implements ElectricCarFactory {
    public function create(): ElectricCar {
        echo "[FACTORY] Building an ElectricCar...\n";
        return new ElectricCar();
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Using the factories — covariance in action
// ─────────────────────────────────────────────────────────────────────────────

echo "── Factory covariance ──────────────────────────────\n\n";

// This function accepts any VehicleFactory — including CarFactory and ElectricCarFactory.
function buildAndDescribeVehicle(VehicleFactory $factory): void {
    $vehicle = $factory->create();
    // We only know we have a Vehicle here — and that is fine.
    echo "  Got: " . get_class($vehicle) . " — " . $vehicle->describe() . "\n";
}

buildAndDescribeVehicle(new StandardVehicleFactory()); // Returns Car (a Vehicle)
buildAndDescribeVehicle(new SportCarFactory());         // Returns Car (a Vehicle) ✓
buildAndDescribeVehicle(new TeslaFactory());            // Returns ElectricCar (a Vehicle) ✓


// ─────────────────────────────────────────────────────────────────────────────
// The benefit: when you have a CarFactory specifically, you get Car methods
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Richer access when using the specific type ──────\n\n";

function buildCarWithHorn(CarFactory $factory): void {
    $car = $factory->create(); // Return type is Car (not just Vehicle)
    // Because the return type is Car, we can call Car-specific methods directly:
    echo "  " . $car->honk() . " (from " . get_class($car) . ")\n";
}

buildCarWithHorn(new SportCarFactory());
buildCarWithHorn(new TeslaFactory()); // ElectricCar IS a Car — covariance is safe

function buildElectricAndCharge(ElectricCarFactory $factory): void {
    $ev = $factory->create(); // Return type is ElectricCar
    echo "  " . $ev->chargeBattery() . " (from " . get_class($ev) . ")\n";
}

buildElectricAndCharge(new TeslaFactory());


// ─────────────────────────────────────────────────────────────────────────────
// Repository pattern — another classic covariance use case
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── Repository covariance ───────────────────────────\n\n";

abstract class Model {
    abstract public function getId(): int;
}

class UserModel extends Model {
    public function __construct(
        private int    $id,
        private string $email
    ) {}

    public function getId(): int      { return $this->id; }
    public function getEmail(): string { return $this->email; }
}

interface Repository {
    public function findById(int $id): Model;
}

// ✅ Covariant: returns UserModel (subtype of Model)
class UserRepository implements Repository {
    private array $users = [
        1 => ['email' => 'alice@example.com'],
        2 => ['email' => 'bob@example.com'],
    ];

    public function findById(int $id): UserModel { // Covariant return ✓
        if (!isset($this->users[$id])) {
            throw new \RuntimeException("User {$id} not found");
        }
        return new UserModel($id, $this->users[$id]['email']);
    }
}

// Works with any Repository — gets back a Model
function printModelId(Repository $repo, int $id): void {
    $model = $repo->findById($id);
    echo "  Model ID: " . $model->getId() . " (type: " . get_class($model) . ")\n";
}

// Works with UserRepository specifically — gets back a UserModel with email access
function printUserEmail(UserRepository $repo, int $id): void {
    $user = $repo->findById($id);
    echo "  User email: " . $user->getEmail() . "\n";
}

$userRepo = new UserRepository();
printModelId($userRepo, 1);
printUserEmail($userRepo, 2);


// ─────────────────────────────────────────────────────────────────────────────
// What PHP rejects — widening return type (anti-covariance = error)
// ─────────────────────────────────────────────────────────────────────────────

echo "\n── What PHP prevents: widening return type ─────────\n\n";
echo "The following would be a PHP fatal error (commented out — uncomment to test):\n\n";

echo <<<'CODE'
interface AnimalFactory {
    public function create(): Dog;  // Promises a Dog
}

class BadFactory implements AnimalFactory {
    public function create(): Animal { // ❌ Animal is WIDER than Dog
        return new Cat();             // Caller expected Dog, got Cat.
    }
    // Fatal error: Declaration of BadFactory::create(): Animal must be
    // compatible with AnimalFactory::create(): Dog
}
CODE;
echo "\n";

echo "\n--- Recap ---\n";
echo "Covariance: overriding return types can be NARROWER (more specific). Always safe.\n";
echo "PHP enforces this: you cannot widen (broaden) a return type in an override.\n";
echo "Benefit: factory/repository patterns let callers get richer types without losing safety.\n";