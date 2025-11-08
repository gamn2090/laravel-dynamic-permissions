# Laravel Dynamic Permissions

🚀 A powerful and flexible hierarchical permissions system for Laravel with role-based and user-specific overrides.

## ✨ Features

- 🌳 **Hierarchical Structure**: Organize permissions in grandparent → parent → child structure
- 👥 **Role-Based**: Assign features to roles
- 🎯 **User Overrides**: Grant or revoke specific features for individual users
- ⚡ **Performance**: Built-in caching system for optimal performance
- 🎨 **Dynamic Sidebar**: Auto-generate navigation menus based on user permissions
- 🔄 **Auto-Sync**: Automatically sync features from your routes
- 🛠️ **Flexible**: Works with or without existing role systems
- 📱 **API Ready**: Full REST API for managing permissions

## 📦 Installation

### Requirements

- PHP >= 8.2
- Laravel >= 11.0

### Install via Composer
```bash
composer require gamn2090/laravel-dynamic-permissions
```

### Publish and Run Migrations
```bash
php artisan dynamic-permissions:install
```

This will:
- Publish configuration file
- Publish migrations
- Optionally run migrations

### Add Trait to User Model

Add the `HasDynamicPermissions` trait to your `User` model:
```php
use gamn2090\DynamicPermissions\Traits\HasDynamicPermissions;

class User extends Authenticatable
{
    use HasDynamicPermissions;
    
    // ... rest of your model
}
```

## 🚀 Quick Start

### Create Features Manually
```php
use gamn2090\DynamicPermissions\Models\Feature;

// Create a grandparent (top-level group)
$admin = Feature::create([
    'name' => 'Administration',
    'slug' => 'admin',
    'type' => 'grandparent',
    'icon' => 'settings',
]);

// Create a parent (sub-group)
$users = Feature::create([
    'name' => 'User Management',
    'slug' => 'users',
    'type' => 'parent',
    'parent_id' => $admin->id,
    'icon' => 'users',
]);

// Create children (actual features)
Feature::create([
    'name' => 'View Users',
    'slug' => 'users.index',
    'type' => 'child',
    'parent_id' => $users->id,
    'url' => '/users',
]);

Feature::create([
    'name' => 'Create User',
    'slug' => 'users.store',
    'type' => 'child',
    'parent_id' => $users->id,
]);
```

### Auto-Sync Features from Routes
```bash
php artisan dynamic-permissions:sync
```

### Check Permissions
```php
// In your controllers or views
if (auth()->user()->hasFeatureAccess('users.index')) {
    // User has access
}

// Check multiple features
if (auth()->user()->hasAnyFeatureAccess(['users.index', 'users.store'])) {
    // User has at least one
}

if (auth()->user()->hasAllFeatureAccess(['users.index', 'users.store'])) {
    // User has all of them
}
```

### Protect Routes with Middleware
```php
// Single feature
Route::middleware(['auth:sanctum', 'feature:users.index'])
    ->get('/users', [UserController::class, 'index']);

// Multiple features (any)
Route::middleware(['auth:sanctum', 'features:users.index,users.store'])
    ->get('/users/manage', [UserController::class, 'manage']);

// Multiple features (all required)
Route::middleware(['auth:sanctum', 'features:users.index,users.store,all'])
    ->post('/users/bulk', [UserController::class, 'bulk']);
```

### Get User's Sidebar
```php
$sidebar = auth()->user()->getSidebarFeatures();

// Returns hierarchical structure:
[
    [
        'id' => 1,
        'name' => 'Administration',
        'icon' => 'settings',
        'children' => [
            [
                'id' => 2,
                'name' => 'User Management',
                'icon' => 'users',
                'children' => [
                    ['id' => 3, 'name' => 'View Users', 'url' => '/users'],
                    ['id' => 4, 'name' => 'Create User', 'url' => '/users/create']
                ]
            ]
        ]
    ]
]
```

### Grant/Revoke User-Specific Access
```php
$user = User::find(1);

// Grant access
$user->grantFeatureAccess(
    'users.delete', 
    grantedBy: auth()->id(),
    reason: 'Temporary admin access',
    expiresAt: now()->addDays(7)
);

// Revoke access
$user->revokeFeatureAccess(
    'users.delete',
    grantedBy: auth()->id(),
    reason: 'Access no longer needed'
);

// Remove override (back to role default)
$user->removeFeatureOverride('users.delete');
```

## 📚 API Endpoints

All endpoints are prefixed with `/api/permissions` (configurable):

### Features
```
GET    /features              # List all features
POST   /features              # Create feature
GET    /features/{id}         # Get specific feature
PUT    /features/{id}         # Update feature
DELETE /features/{id}         # Delete feature
GET    /features/sidebar      # Get sidebar structure
POST   /features/reorder      # Reorder features
POST   /features/{id}/move    # Move feature to new parent
```

### User Features
```
GET    /user/features         # Get current user's features
GET    /user/sidebar          # Get current user's sidebar
POST   /user/check-access     # Check specific feature access
```

### User Management
```
GET    /users/{id}/overrides  # Get user's overrides
POST   /users/{id}/grant      # Grant feature to user
POST   /users/{id}/revoke     # Revoke feature from user
DELETE /users/{id}/override   # Remove override
```

## 🔧 Configuration

Publish the config file:
```bash
php artisan vendor:publish --tag=dynamic-permissions-config
```

Key configuration options in `config/dynamic-permissions.php`:
```php
return [
    // Customize models
    'models' => [
        'user' => \App\Models\User::class,
        'role' => \App\Models\Role::class,
    ],

    // Cache settings
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
    ],

    // Auto-sync from routes
    'auto_sync' => [
        'enabled' => false,
    ],

    // API routes
    'routes' => [
        'enabled' => true,
        'prefix' => 'api/permissions',
        'middleware' => ['api', 'auth:sanctum'],
    ],
];
```

## 🧪 Testing
```bash
composer test
```

## 📖 Documentation

Full documentation available at: [https://github.com/gamn2090/laravel-dynamic-permissions](https://github.com/gamn2090/laravel-dynamic-permissions)

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## 👨‍💻 Author

**Gustavo**
- Email: gamn2090@gmail.com
- GitHub: [@gamn2090](https://github.com/gamn2090)

## 🙏 Acknowledgments

Built with ❤️ for the Laravel community.
```

---

## **2️⃣0️⃣ Crear LICENSE**

Archivo: `LICENSE`
```
MIT License

Copyright (c) 2025 Gustavo

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.