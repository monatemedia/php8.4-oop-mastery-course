# PHP 8.4 OOP Mastery Course
### Learn · Code · Quiz — Interactive with XAMPP

> **How to use this README:** Work through each module in order. Tick off `[ ]` checkboxes as you complete each topic. Do **not** move to the next module until all items in the current one are checked.

---

## 📁 Folder Structure

```
htdocs/
├── README.md                  ← You are here
├── module-1-oop-building-blocks/
├── module-2-advanced-types/
├── module-3-dependency-injection/
└── module-4-container-automation/
```

---

## 🗺️ Course Roadmap

```
[Module 1: OOP Building Blocks]
        ↓
[Module 2: Advanced Types & Enums]
        ↓
[Module 3: Dependency Injection & IoC]
        ↓
[Module 4: Container Automation with PHP-DI]
```

> **Rule:** You must complete each module before starting the next. Module 1 lays the foundation that every later module depends on.

---

## Module 1 — OOP Building Blocks
> **Folder:** `module-1-oop-building-blocks/`
> **Goal:** Master the core OOP constructs that enforce clean architecture and enable polymorphism.

### Lesson 1.1 — Interfaces
- [ ] What an interface is and why it exists (contracts, not implementation)
- [ ] Defining and implementing a single interface
- [ ] Implementing multiple interfaces on one class
- [ ] Using interfaces as type hints for polymorphism
- [ ] Interface constants
- [ ] Interface inheritance (`extends` between interfaces)
- [ ] **Code Challenge:** Refactor a tightly coupled class to depend on an interface
- [ ] **Quiz:** Interface design & polymorphism

### Lesson 1.2 — Abstract Classes
- [ ] Abstract classes vs interfaces — when to use which
- [ ] Defining abstract methods (enforcement) and concrete methods (reuse)
- [ ] Constructor logic in abstract classes
- [ ] Extending an abstract class and fulfilling its contract
- [ ] Combining abstract classes with interfaces
- [ ] **Code Challenge:** Extract shared logic from two similar classes into an abstract base
- [ ] **Quiz:** Abstract class rules and trade-offs

### Lesson 1.3 — Traits
- [ ] What traits are and why PHP needs them (horizontal reuse)
- [ ] Defining and using a trait in a class (`use`)
- [ ] Using multiple traits and handling method name conflicts (`insteadof`, `as`)
- [ ] Trait properties and abstract trait methods
- [ ] Traits vs interfaces vs abstract classes — choosing the right tool
- [ ] **Code Challenge:** Extract a cross-cutting concern (e.g. logging, timestamps) into a trait
- [ ] **Quiz:** Trait resolution order and conflict rules

---

## Module 2 — Advanced Types & Enums
> **Folder:** `module-2-advanced-types/`
> **Goal:** Strengthen your type system knowledge and learn the PHP 8.x features that make interfaces far more powerful.

### Lesson 2.1 — Type Hinting & Return Types
- [ ] Scalar types (`int`, `string`, `float`, `bool`) and `strict_types=1`
- [ ] Nullable types (`?string`) and union types (`int|string`)
- [ ] The `void`, `never`, and `mixed` return types
- [ ] `self`, `static`, and `parent` return types
- [ ] Intersection types (PHP 8.1+): `Countable&Traversable`
- [ ] Enforcing strict typing across your module
- [ ] **Code Challenge:** Add strict type declarations to a loosely typed class hierarchy
- [ ] **Quiz:** Type compatibility and strict mode

### Lesson 2.2 — PHP 8.4 Property Hooks
- [ ] What property hooks replace (boilerplate getters/setters)
- [ ] The `get` hook — computed and validated reads
- [ ] The `set` hook — validation and transformation on write
- [ ] Backed vs virtual properties
- [ ] Hooks in interfaces (interface property declarations)
- [ ] Hooks in abstract classes
- [ ] **Code Challenge:** Rewrite a class with six getter/setter pairs using property hooks
- [ ] **Quiz:** Hook behaviour, inheritance, and interface contracts

### Lesson 2.3 — Enums (PHP 8.1+)
- [ ] Pure (unit) enums — named cases with no value
- [ ] Backed enums — string and integer backing
- [ ] Enum methods and constants
- [ ] Implementing interfaces on enums
- [ ] Using enums as type hints and in match expressions
- [ ] `from()` vs `tryFrom()` — safe parsing of external data
- [ ] Enums in switch/match — exhaustiveness checking
- [ ] **Code Challenge:** Replace magic string constants across a module with a backed enum
- [ ] **Quiz:** Enum rules, interface implementation, and safe value parsing

### Lesson 2.4 — Anonymous Classes
- [ ] Syntax and instantiation of anonymous classes
- [ ] Implementing interfaces inline (ideal for tests and stubs)
- [ ] Extending concrete and abstract classes anonymously
- [ ] When to use anonymous classes vs named classes vs closures
- [ ] **Code Challenge:** Replace a test double class file with an anonymous class stub
- [ ] **Quiz:** Anonymous class scoping and use cases

---

## Module 3 — Dependency Injection & IoC
> **Folder:** `module-3-dependency-injection/`
> **Goal:** Understand why coupling is the enemy of testable code and how to invert control using the patterns DI containers are built on.

### Lesson 3.1 — Tight vs Loose Coupling
- [ ] What coupling means and how to measure it
- [ ] Why `new ClassName()` inside a constructor is a design smell
- [ ] The cost of tight coupling: untestable, inflexible, hard to swap
- [ ] Identifying coupling in real code examples
- [ ] **Code Challenge:** Identify and list every coupling violation in a given class
- [ ] **Quiz:** Coupling recognition and consequences

### Lesson 3.2 — Constructor Injection
- [ ] The Dependency Injection principle (passing, not creating)
- [ ] Constructor injection — the preferred pattern
- [ ] Type-hinting injected dependencies against interfaces
- [ ] Injecting multiple dependencies cleanly
- [ ] **Code Challenge:** Refactor a class that calls `new` internally to use constructor injection
- [ ] **Quiz:** DI rules and constructor design

### Lesson 3.3 — Setter & Interface Injection
- [ ] Setter injection — optional dependencies
- [ ] Interface injection — the dependency provides the setter contract
- [ ] When setter injection is appropriate vs constructor injection
- [ ] **Code Challenge:** Add an optional logger dependency via setter injection
- [ ] **Quiz:** Choosing between injection patterns

### Lesson 3.4 — Inversion of Control (IoC)
- [ ] The Hollywood Principle: "Don't call us, we'll call you"
- [ ] High-level modules depending on abstractions, not details
- [ ] The Dependency Inversion Principle (DIP — the D in SOLID)
- [ ] Building a manual IoC example from scratch (no library)
- [ ] How IoC leads naturally to service containers
- [ ] **Code Challenge:** Refactor a multi-class application to fully invert its dependencies
- [ ] **Quiz:** IoC vs DI — conceptual differences and real-world application

---

## Module 4 — Container Automation with PHP-DI
> **Folder:** `module-4-container-automation/`
> **Goal:** Automate the wiring of your dependency graph using a real DI container and PHP's Reflection API.

### Lesson 4.1 — Service Containers
- [ ] What a service container is (the central object registry)
- [ ] Manual container implementation: binding and resolving
- [ ] Container as a registry vs container as a factory
- [ ] Service identifiers: class names vs interface names
- [ ] **Code Challenge:** Build a minimal service container from scratch using an associative array
- [ ] **Quiz:** Container responsibilities and resolution flow

### Lesson 4.2 — PHP Reflection API
- [ ] `ReflectionClass` — inspecting class metadata at runtime
- [ ] `ReflectionMethod` and `ReflectionParameter` — reading constructor signatures
- [ ] Reading type hints from parameters programmatically
- [ ] How containers use Reflection to auto-wire dependencies
- [ ] Performance implications and when to cache reflection data
- [ ] **Code Challenge:** Write a function that reads a class constructor and lists its type-hinted parameters
- [ ] **Quiz:** Reflection API concepts and auto-wiring mechanics

### Lesson 4.3 — Auto-wiring
- [ ] What auto-wiring is and how it works end-to-end
- [ ] Resolving deep dependency graphs automatically
- [ ] Circular dependency detection
- [ ] Limitations of auto-wiring (primitives, ambiguous bindings)
- [ ] **Code Challenge:** Extend your manual container to support auto-wiring via Reflection
- [ ] **Quiz:** Auto-wiring rules and failure scenarios

### Lesson 4.4 — PHP-DI Library
- [ ] Installing PHP-DI via Composer inside XAMPP
- [ ] `ContainerBuilder` — building the container
- [ ] Auto-wiring with PHP-DI (zero-config mode)
- [ ] Explicit bindings: binding an interface to a concrete class
- [ ] Factory definitions — when you need control over instantiation
- [ ] Environment-based configuration (dev vs prod)
- [ ] Scopes and shared instances (singletons vs new instances)
- [ ] **Code Challenge:** Wire the full Module 3 application using PHP-DI with no `new` calls in business logic
- [ ] **Quiz:** PHP-DI configuration, bindings, and container lifecycle

---

## ✅ Completion Checklist

| Module | Lessons | Code Challenges | Quizzes | Status |
|--------|---------|-----------------|---------|--------|
| 1 — OOP Building Blocks | 3 | 3 | 3 | `[ ] Not started` |
| 2 — Advanced Types & Enums | 4 | 4 | 4 | `[ ] Not started` |
| 3 — Dependency Injection & IoC | 4 | 4 | 4 | `[ ] Not started` |
| 4 — Container Automation | 4 | 4 | 4 | `[ ] Not started` |

---

## 🛠️ Environment

| Tool | Version |
|------|---------|
| PHP | 8.4 |
| Runtime | XAMPP (local) |
| Package manager | Composer (for Module 4) |
| Editor | Your choice |

**Enabling strict types** — add this to the top of every PHP file you write:
```php
<?php
declare(strict_types=1);
```

---

## 📖 Reference

- [PHP 8.4 Manual](https://www.php.net/manual/en/)
- [PHP-DI Documentation](https://php-di.org/)
- [PHP Reflection API](https://www.php.net/manual/en/book.reflection.php)
- [SOLID Principles (Wikipedia)](https://en.wikipedia.org/wiki/SOLID)
