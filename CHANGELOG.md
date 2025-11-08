# Changelog

All notable changes to `laravel-dynamic-permissions` will be documented in this file.

## [Unreleased]

### Added
- **Role System Integration**
  - Complete Role model with soft deletes and priority system
  - Role-User pivot table with assignment tracking (who assigned, when)
  - Role activation/deactivation functionality
  - Default role assignment for new users
  - Role priority system for hierarchical access control
  - `hasRole()`, `hasAnyRole()`, and `hasAllRoles()` methods
  - `assignRole()`, `removeRole()`, and `syncRoles()` methods for user role management
  
- **Enhanced Permission System**
  - Role-based feature access through `feature_role` pivot table
  - Automatic cache invalidation when role permissions change
  - Combined role and override permission resolution
  - User-specific overrides now work seamlessly with role-based permissions
  - Permission precedence: User overrides > Role permissions
  
- **Role Management Features**
  - `grantFeature()` and `revokeFeature()` methods for roles
  - `syncFeatures()` for bulk feature assignment to roles
  - `getFeatureSlugs()` to retrieve all feature slugs for a role
  - `activate()` and `deactivate()` methods for role status management
  - `setAsDefault()` method to designate default roles
  - Active, Default, and ByPriority query scopes
  
- **Configuration Enhancements**
  - Added `tables.roles` and `tables.role_user` configuration options
  - Added `default_role` configuration for automatic user role assignment
  - Added `super_admin` configuration for bypass permissions
  - Configurable table names for all relationship tables
  
- **Developer Experience**
  - RoleSeeder class with common role presets (Super Admin, Admin, Manager, User, Guest)
  - Comprehensive usage examples file demonstrating all role features
  - Exception handling for invalid role/feature operations
  - Better error messages with descriptive exceptions

### Changed
- **HasDynamicPermissions Trait**
  - Completely refactored to support both role-based and user-specific permissions
  - `hasFeatureAccess()` now checks user overrides first, then role permissions
  - `getAllFeatures()` now merges role features with user overrides
  - Optimized sidebar generation to include role-based access
  - Improved caching strategy to handle role permission changes
  
- **Permission Resolution Logic**
  - Enhanced algorithm to properly merge role permissions and user overrides
  - User revocations now properly override role grants
  - Expired user overrides are automatically excluded
  - Better handling of hierarchical permission inheritance

### Performance
- Optimized database queries for role-feature relationships
- Added database indexes on commonly queried columns (is_active, priority, assigned_at)
- Automatic cache clearing only affects users with modified roles
- Reduced N+1 queries in sidebar generation with proper eager loading

### Database
- **New Migrations**
  - `create_roles_table.php` - Roles with priority, status, and soft deletes
  - `create_role_user_table.php` - User-role assignments with audit trail
  - Added unique constraints to prevent duplicate assignments
  - Added foreign key constraints with cascade deletes

### Documentation
- Added comprehensive role usage examples
- Updated configuration documentation
- Added role management best practices
- Included migration order recommendations

## [1.0.0] - 2025-01-11

### Added
- Initial release
- Hierarchical permission structure (grandparent → parent → child)
- Role-based permissions
- User-specific permission overrides
- Built-in caching system
- Dynamic sidebar generation
- Auto-sync features from routes
- REST API for managing permissions
- Middleware for route protection
- Artisan commands for management
- Comprehensive documentation

---

## **✅ Updated Package Checklist**

```
✅ composer.json
✅ Service Provider
✅ Models
   ✅ Feature
   ✅ UserFeatureOverride
   ✅ Role (NEW)
✅ Trait (HasDynamicPermissions) - UPDATED with role support
✅ Services
   ✅ FeatureService
   ✅ PermissionCompiler - UPDATED for role integration
   ✅ SidebarBuilder - UPDATED for role-based sidebar
✅ Middleware
   ✅ CheckFeature
   ✅ CheckMultipleFeatures
✅ Controllers
   ✅ FeatureController
   ✅ UserFeatureController
   ✅ RoleController (recommended addition)
✅ Commands
   ✅ Install - UPDATED to include role migrations
   ✅ Sync
   ✅ ClearCache - UPDATED to clear role caches
✅ Migrations
   ✅ create_features_table.php
   ✅ create_feature_role_table.php
   ✅ create_user_features_table.php
   ✅ create_roles_table.php (NEW)
   ✅ create_role_user_table.php (NEW)
✅ Seeders
   ✅ RoleSeeder.php (NEW)
✅ Config file - UPDATED with role configuration
✅ Routes - (recommended: add role management routes)
✅ Facade
✅ Exception
✅ README.md - (needs update with role documentation)
✅ LICENSE
✅ CHANGELOG.md - UPDATED
✅ .gitignore
✅ USAGE_EXAMPLES.php (NEW)
```

---

## Migration Guide (from v1.0.0 to v2.0.0)

### For Existing Users

If you're upgrading from v1.0.0, follow these steps:

1. **Publish new migrations:**
   ```bash
   php artisan vendor:publish --tag=dynamic-permissions-migrations --force
   ```

2. **Run the new migrations:**
   ```bash
   php artisan migrate
   ```

3. **Update your config file:**
   ```bash
   php artisan vendor:publish --tag=dynamic-permissions-config --force
   ```

4. **Update your User model** (if needed - the trait is backward compatible):
   ```php
   use Gamn2090\DynamicPermissions\Traits\HasDynamicPermissions;
   
   class User extends Authenticatable
   {
       use HasDynamicPermissions;
   }
   ```

5. **Clear permission cache:**
   ```bash
   php artisan dynamic-permissions:clear-cache
   ```

### Breaking Changes

None. This release is fully backward compatible with v1.0.0. All existing functionality remains unchanged and will continue to work as expected.

### New Features You Can Start Using

- Assign roles to users: `$user->assignRole('admin')`
- Check role access: `$user->hasRole('admin')`
- Grant features to roles: `$role->grantFeature('users.index')`
- User overrides still work and take precedence over role permissions

---

## Roadmap for v2.1.0

- [ ] Role-based middleware (`role:admin`)
- [ ] Blade directives (`@role('admin')`)
- [ ] Role API endpoints
- [ ] Role management UI components
- [ ] Bulk role assignment commands
- [ ] Role permission inheritance visualization
- [ ] Audit logging for role changes
- [ ] Import/Export roles and permissions