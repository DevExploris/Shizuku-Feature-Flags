# Shizuku Feature Flags

A Symfony bundle for managing feature flags backed by Doctrine ORM.

**[Official (EN) documentation →](https://devexploris.com/shizuku?lang=en)**
**[Documentation officielle →](https://devexploris.com/shizuku)**

## Requirements

- PHP 8.2+
- Symfony 7.4+ or 8.0+
- Doctrine ORM 3.x

## Installation

### 1. Require the bundle

```bash
composer require devexploris/shizuku-feature-flags
```

### 2. Register the bundle

```php
// config/bundles.php
return [
    Devexploris\ShizukuFeatureFlags\ShizukuFeatureFlagsBundle::class => ['all' => true],
];
```

### 3. Create the database table

The bundle does not ship its own migrations. Generate and run a migration from your application:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Usage

### Checking a flag in PHP

Inject `FeatureFlagService` and call `isEnabled()`:

```php
use Devexploris\ShizukuFeatureFlags\Service\FeatureFlagService;

class MyService
{
    public function __construct(private FeatureFlagService $flags) {}

    public function doSomething(): void
    {
        if ($this->flags->isEnabled('my_feature')) {
            // new behaviour
        }
    }
}
```

An unknown flag (not found in the database) always returns `false`.

### Checking a flag in Twig

The bundle registers a global `feature()` Twig function:

```twig
{% if feature('my_feature') %}
    {# new behaviour #}
{% endif %}

{# inline use #}
<body class="{{ feature('dark_mode') ? 'dark' : 'light' }}">
```

An unknown flag (not found in the database) always returns `false` — the template renders as if the flag were disabled, with no error thrown.

## Console commands

All flag names must follow the `snake_case` format: lowercase letters, digits and underscores, starting with a letter.

### List flags

```bash
php bin/console shizuku:list [filter]
```

`filter` is optional. Accepted values: `All`, `Enabled`, `Disabled`, `Locked`. When omitted, an interactive prompt is shown.

### Create a flag

```bash
php bin/console shizuku:flag:create
php bin/console shizuku:flag:create --name=my_feature --description="My feature" --enable
```

When called without options, an interactive prompt guides you through name, description and initial state.

### Enable a flag

```bash
php bin/console shizuku:flag:enable
php bin/console shizuku:flag:enable --name=my_feature
```

Locked flags cannot be enabled.

### Disable a flag

```bash
php bin/console shizuku:flag:disable
php bin/console shizuku:flag:disable --name=my_feature
```

Locked flags cannot be disabled.

### Lock a flag

```bash
php bin/console shizuku:flag:lock
php bin/console shizuku:flag:lock --name=my_feature
```

Locking a flag prevents any further enable or disable operations. It signals that the flag's state is final and the code paths it guards should be cleaned up before the flag is deleted.

### Delete a flag

```bash
php bin/console shizuku:flag:delete
php bin/console shizuku:flag:delete --name=my_feature
php bin/console shizuku:flag:delete --name=my_feature --force
```

By default, only locked flags can be deleted. Use `--force` to delete a non-locked flag, with confirmation.

## Flag lifecycle

```
created -> enabled / disabled -> locked -> deleted
```

1. A flag is created in a disabled state by default.
2. It can be toggled freely until it is locked.
3. Once locked, its state is frozen. The Symfony Profiler panel marks it as "Must be cleaned before removal", prompting you to remove the `isEnabled()` calls from the code.
4. Once the code is cleaned up, the flag can be deleted.

## Caching

The bundle integrates with the Symfony Cache component to avoid a database query on every `isEnabled()` call.

When `cache.app` is available in the container (the default Symfony cache pool), the bundle wires it automatically — no configuration needed.

Flag state is cached for **300 seconds**. The cache entry for a flag is invalidated immediately whenever its state changes via a console command (`enable`, `disable`, `lock`, `delete`, `create`). Direct database edits bypass this invalidation and will be visible after the TTL expires.

To use a different cache pool, override the `$cache` argument on `FeatureFlagService` and the mutating commands in your `services.yaml`:

```yaml
Devexploris\ShizukuFeatureFlags\Service\FeatureFlagService:
    arguments:
        $cache: '@cache.my_custom_pool'
```

To disable caching entirely, set the argument to `null`:

```yaml
Devexploris\ShizukuFeatureFlags\Service\FeatureFlagService:
    arguments:
        $cache: ~
```

## Symfony Profiler integration

When the Symfony Profiler is available, a "Feature Flags" panel is added to the toolbar. It shows:

- The number of flags checked during the request and how many are enabled.
- Flags marked as "Must be cleaned before removal" (locked), as a reminder to remove them from the code.
- Unknown flags: flags checked in the code but not found in the database.

The toolbar and menu highlight in warning colour when locked or unknown flags are detected.

## Entity reference

| Property      | Type                    | Description                                              |
|---------------|-------------------------|----------------------------------------------------------|
| `name`        | `string`                | Unique snake_case identifier.                            |
| `description` | `string`                | Human-readable description.                              |
| `isEnabled`   | `bool`                  | Whether the flag is currently active.                    |
| `isLocked`    | `bool`                  | Whether the flag is frozen and pending code cleanup.     |
| `createdAt`   | `DateTimeImmutable`     | Set automatically on creation.                           |
| `enabledAt`   | `DateTimeImmutable\|null` | Set when the flag is first enabled, reset on disable.  |
| `lockedAt`    | `DateTimeImmutable\|null` | Set when the flag is locked.                           |

## License

MIT
