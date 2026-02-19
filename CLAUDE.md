# CLAUDE.md - Module User

This file provides guidance to Claude Code when working with this module.

## Overview

`hanafalah/module-user` is a Laravel package that provides user management functionality for the Wellmed healthcare system. It handles user authentication, user references (linking users to various entities like patients, doctors, workspaces), and password management.

**Namespace:** `Hanafalah\ModuleUser`

**Dependencies:**
- `hanafalah/laravel-support` - Base package providing service provider and model foundations

## CRITICAL WARNING: ServiceProvider Configuration

**The current `ModuleUserServiceProvider` uses `registers(['*'])` which is DANGEROUS and can cause memory exhaustion issues.**

```php
// Current implementation (PROBLEMATIC):
public function register()
{
    $this->registerMainClass(ModuleUser::class)
        ->registerCommandService(Providers\CommandServiceProvider::class)
        ->registers(['*']);  // <-- This can cause memory issues
}
```

### Why This Is Problematic

The `registers(['*'])` method in `BaseServiceProvider` auto-loads multiple services including schemas, which trigger class loading chains through `HasModelConfiguration` trait. This can cause:
- Memory exhaustion (536MB limit exceeded)
- Slow boot times
- Circular dependency issues

### Recommended Pattern

When extending `BaseServiceProvider`, use explicit registration instead:

```php
// RECOMMENDED pattern:
public function register()
{
    $this->registerMainClass(ModuleUser::class)
        ->registerCommandService(Providers\CommandServiceProvider::class);

    // Explicitly register what you need with safe methods
    $this->registerConfig()
         ->registerModel();

    // Register services manually with deferred loading
    $this->app->singleton(Contracts\ModuleUser::class, function () {
        return new ModuleUser();
    });
}
```

**Safe register methods that CAN be used with `registers()`:**
- `Config`, `Model`, `Database`, `Migration`, `Route`, `Namespace`, `Provider`

**Dangerous methods (avoid auto-loading):**
- `Schema`, `Services`

## Directory Structure

```
src/
├── ModuleUserServiceProvider.php    # Main service provider
├── ModuleUser.php                   # Main module class
├── Facades/
│   └── ModuleUser.php               # Facade for accessing module
├── Models/
│   └── User/
│       ├── User.php                 # Main User model (Authenticatable)
│       ├── Authenticatable.php      # Base authenticatable class
│       └── UserReference.php        # User-entity reference pivot model
├── Schemas/
│   ├── User.php                     # User business logic schema
│   └── UserReference.php            # UserReference business logic schema
├── Contracts/
│   ├── ModuleUser.php               # Main module contract
│   ├── Schemas/
│   │   ├── User.php
│   │   └── UserReference.php
│   └── Data/
│       ├── UserData.php
│       ├── UserReferenceData.php
│       └── ChangePasswordData.php
├── Data/
│   ├── UserData.php                 # User DTO with validation
│   ├── UserReferenceData.php        # UserReference DTO
│   └── ChangePasswordData.php       # Password change DTO
├── Resources/
│   ├── ViewUser.php                 # API resource for list view
│   ├── ShowUser.php                 # API resource for detail view
│   └── UserReference/
│       ├── ViewUserReference.php
│       └── ShowUserReference.php
├── Concerns/
│   ├── User/
│   │   ├── UserValidation.php       # Username/email validation trait
│   │   └── UpdatePassword.php       # Password update trait
│   ├── UserReference/
│   │   └── HasUserReference.php     # Trait for models that have user references
│   └── Maps/
│       └── HasCoordinate.php        # Coordinate handling trait
├── Supports/
│   └── BaseModuleUser.php           # Base class extending PackageManagement
├── Factories/
│   └── UserFactory.php              # Model factory for testing
├── Commands/
│   ├── InstallMakeCommand.php       # Installation command
│   └── EnvironmentCommand.php       # Environment setup command
└── Providers/
    └── CommandServiceProvider.php   # Command registration provider
```

## Key Models

### User (`Models/User/User.php`)

The main user model for authentication.

**Key features:**
- Uses ULIDs for primary key (`HasUlids`)
- Supports API tokens (`HasApiTokens` from `hanafalah/api-helper`)
- Has props/metadata support (`HasProps`)
- Non-incrementing string primary key

**Fillable fields:**
- `id`, `username`, `email`, `email_verified_at`, `password`, `props`

**Hidden fields:**
- `password`, `remember_token`

**Relationships:**
- `userReference()` - HasOne to current UserReference
- `userReferences()` - HasMany to all UserReferences

### Authenticatable (`Models/User/Authenticatable.php`)

Base class that User extends. Implements Laravel authentication contracts:
- `AuthenticatableContract`
- `AuthorizableContract`
- `CanResetPasswordContract`

Extends `Hanafalah\LaravelSupport\Models\BaseModel`.

### UserReference (`Models/User/UserReference.php`)

Pivot model linking users to various entities (patients, doctors, workspaces, etc.).

**Key features:**
- Uses ULIDs for primary key
- Soft deletes enabled
- Has roles support (`HasRole` from `hanafalah/laravel-permission`)
- Tracks "current" reference (`HasCurrent`)

**Fillable fields:**
- `id`, `uuid`, `reference_type`, `reference_id`, `user_id`, `workspace_type`, `workspace_id`, `current`

**Relationships:**
- `reference()` - MorphTo (links to Patient, Doctor, Employee, etc.)
- `user()` - BelongsTo User
- `workspace()` - MorphTo (links to Tenant, Workspace, etc.)
- `tenant()` - Alias for workspace relationship
- `role()` - Through HasRole trait

**Condition columns (for updateOrCreate):**
- `reference_type`, `reference_id`, `user_id`

## Schemas (Business Logic)

### User Schema (`Schemas/User.php`)

Handles user CRUD operations and password management.

**Key methods:**
- `prepareStoreUser(UserData $dto)` - Create/update user with optional user reference
- `prepareChangePassword(ChangePasswordData $dto)` - Change user password
- `changePassword(?ChangePasswordData $dto)` - Transaction-wrapped password change
- `getUserByUsernameId(string $username, mixed $user_id)` - Find user by username and ID
- `getUserByEmailId(string $email, mixed $user_id)` - Find user by email and ID

### UserReference Schema (`Schemas/UserReference.php`)

Handles user reference CRUD and role assignment.

**Key methods:**
- `prepareShowUserReference(?Model $model, ?array $attributes)` - Get user reference with relations
- `prepareStoreUserReference(UserReferenceData $dto)` - Create/update user reference with roles

## Data Transfer Objects (DTOs)

### UserData

```php
class UserData extends Data {
    public mixed $id = null;
    public ?string $username = null;
    public ?string $password = null;           // @Password validation
    public ?string $password_confirmation = null;
    public ?string $email = null;              // @Email, @Unique validation
    public ?Carbon $email_verified_at = null;
    public ?UserReferenceData $user_reference = null;
    public ?array $props = null;
}
```

### UserReferenceData

Contains fields for linking users to reference entities and workspaces.

### ChangePasswordData

Contains `id`, `password`, `old_password`, `password_confirmation`.

## Traits for Other Models

### HasUserReference (`Concerns/UserReference/HasUserReference.php`)

Add to models that can be linked to users (e.g., Patient, Doctor, Employee).

**What it provides:**
- Auto-creates UserReference on model creation
- Syncs UUID from UserReference to parent model
- `userReference()` - MorphOne relationship
- `userReferences()` - MorphMany relationship
- `user()` - HasOneThrough to User via UserReference
- `scopeHasRefUuid($uuid)` - Query scope to filter by UserReference UUID

**Usage:**
```php
use Hanafalah\ModuleUser\Concerns\UserReference\HasUserReference;

class Patient extends BaseModel
{
    use HasUserReference;

    // Now this model can be linked to users
}
```

### UserValidation (`Concerns/User/UserValidation.php`)

Validation methods for checking username/email uniqueness.

**Methods:**
- `isTakenByUsernameId(string $username, $user_id, bool $throw)` - Check if username is taken by another user
- `isTakenByEmailId(string $email, $user_id, bool $throw)` - Check if email is taken by another user

## API Resources

### ViewUser / ShowUser

Transform User models for API responses:
- `ViewUser` - Basic info: id, username, email, email_verified_at, user_reference
- `ShowUser` - Extends ViewUser, adds: user_references (all references)

### ViewUserReference / ShowUserReference

Transform UserReference models for API responses.

## Usage Patterns

### Creating a User with Reference

```php
use Hanafalah\ModuleUser\Facades\ModuleUser;
use Hanafalah\ModuleUser\Data\UserData;
use Hanafalah\ModuleUser\Data\UserReferenceData;

$userDto = UserData::from([
    'username' => 'john.doe',
    'email' => 'john@example.com',
    'password' => 'SecurePassword123',
    'password_confirmation' => 'SecurePassword123',
    'user_reference' => [
        'reference_type' => Patient::class,
        'reference_id' => $patient->id,
        'workspace_type' => Tenant::class,
        'workspace_id' => $tenant->id,
        'roles' => [['id' => $roleId, 'name' => 'patient']]
    ]
]);

$user = app(Contracts\Schemas\User::class)->prepareStoreUser($userDto);
```

### Linking Existing Entity to User

If you have a model using `HasUserReference` trait:

```php
// Patient model uses HasUserReference trait
$patient = Patient::create([...]);
// UserReference is automatically created on patient creation

// Access the user through the patient
$user = $patient->user;
$userRef = $patient->userReference;
```

### Changing Password

```php
use Hanafalah\ModuleUser\Data\ChangePasswordData;

$dto = ChangePasswordData::from([
    'id' => $userId,
    'old_password' => 'OldPassword123',
    'password' => 'NewPassword123',
    'password_confirmation' => 'NewPassword123'
]);

app(Contracts\Schemas\User::class)->changePassword($dto);
```

### Validating Username/Email Uniqueness

```php
$userSchema = app(Contracts\Schemas\User::class);

// Check and throw exception if taken
$userSchema->isTakenByEmailId('john@example.com', $currentUserId, throw: true);

// Just check (returns bool)
$isTaken = $userSchema->isTakenByUsernameId('john.doe', $currentUserId);
```

## Configuration

The module expects configuration at `config/module-user.php`:

```php
return [
    'namespace' => 'Hanafalah\\ModuleUser',
    'app' => [
        'contracts' => [
            // Contract => Implementation mappings
        ]
    ],
    'libs' => [
        'model' => 'Models',
        'contract' => 'Contracts',
        'schema' => 'Schemas',
    ],
    'database' => [
        'models' => [
            'User' => \Hanafalah\ModuleUser\Models\User\User::class,
            'UserReference' => \Hanafalah\ModuleUser\Models\User\UserReference::class,
        ]
    ],
    'user_reference_types' => [
        // Define mappings for reference types to their schemas
        // 'patient' => ['schema' => 'Patient'],
    ]
];
```

## Testing

When testing this module:

```bash
# Run tests
vendor/bin/pest

# Use the factory
$user = \Hanafalah\ModuleUser\Factories\UserFactory::new()->create();
```

## Octane Considerations

Since Wellmed uses Laravel Octane:
- User state is NOT persisted between requests
- UserReference `current` flag is request-scoped
- Always use request-scoped resolution for user context
- The `HasCurrent` trait on UserReference helps manage which reference is "active"

## Common Patterns

### Get Current User's Active Reference

```php
$user = auth()->user();
$activeRef = $user->userReference; // Gets the "current" one
$allRefs = $user->userReferences; // Gets all references
```

### Switch User's Active Workspace/Reference

```php
// Mark a specific reference as current
$userReference->current = true;
$userReference->save();

// Other references should have current = false
```

## Modification Checklist

Before modifying this module:

- [ ] Check if change affects User authentication flow
- [ ] Check if change affects UserReference relationships
- [ ] Test with multiple user references per user
- [ ] Verify no state leakage in Octane environment
- [ ] Test role synchronization with laravel-permission
- [ ] Reload Octane after changes: `php artisan octane:reload`
