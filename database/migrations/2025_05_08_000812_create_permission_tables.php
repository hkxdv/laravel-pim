<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams = (bool) config('permission.teams');
        /** @var array<string, mixed>|null $tableNames */
        $tableNames = config('permission.table_names');
        /** @var array<string, mixed>|null $columnNames */
        $columnNames = config('permission.column_names');

        throw_unless(is_array($tableNames),
        Exception::class,
        'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        throw_unless(is_array($columnNames),
        Exception::class,
        'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');

        $pivotRole = is_string(
            $columnNames['role_pivot_key'] ?? null
        ) ? $columnNames['role_pivot_key'] : 'role_id';

        $pivotPermission = is_string(
            $columnNames['permission_pivot_key'] ?? null
        ) ? $columnNames['permission_pivot_key'] : 'permission_id';

        throw_if($teams && ! is_string(
            $columnNames['team_foreign_key'] ?? null
        ),
        Exception::class,
        'Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.');

        $permissionsTable = $tableNames['permissions'] ?? null;
        throw_unless(
            is_string($permissionsTable),
            RuntimeException::class,
            'Invalid permissions table name'
        );

        Schema::create($permissionsTable, static function (
            Blueprint $table
        ): void {
            // $table->engine('InnoDB');
            // permission id
            $table->bigIncrements('id');
            $table->string('name');
            // For MyISAM use string('name', 225);
            // (or 166 for InnoDB with Redundant/Compact row format)
            $table->string('guard_name');
            // For MyISAM use string('guard_name', 25);
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        $rolesTable = $tableNames['roles'] ?? null;
        throw_unless(
            is_string($rolesTable),
            RuntimeException::class,
            'Invalid roles table name'
        );

        Schema::create($rolesTable, static function (
            Blueprint $table
        ) use ($teams, $columnNames): void {
            // $table->engine('InnoDB');
            // role id
            $table->bigIncrements('id');
            // permission.testing is a fix for sqlite testing
            if ($teams || config('permission.testing')) {
                $teamKey = $columnNames['team_foreign_key'];
                throw_unless(
                    is_string($teamKey),
                    RuntimeException::class,
                    'Invalid team_foreign_key'
                );
                $table->unsignedBigInteger($teamKey)->nullable();
                $table->index($teamKey, 'roles_team_foreign_key_index');
            }

            $table->string('name');
            // For MyISAM use string('name', 225);
            // (or 166 for InnoDB with Redundant/Compact row format)
            $table->string('guard_name');
            // For MyISAM use string('guard_name', 25);
            $table->timestamps();
            if ($teams || config('permission.testing')) {
                $teamKey = $columnNames['team_foreign_key'];
                throw_unless(
                    is_string($teamKey),
                    RuntimeException::class,
                    'Invalid team_foreign_key'
                );
                $table->unique([$teamKey, 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        $modelHasPermissionsTable = $tableNames['model_has_permissions']
            ?? null;
        throw_unless(
            is_string($modelHasPermissionsTable),
            RuntimeException::class,
            'Invalid model_has_permissions table name'
        );

        Schema::create($modelHasPermissionsTable, static function (
            Blueprint $table
        ) use ($permissionsTable, $columnNames, $pivotPermission, $teams): void {
            $table->unsignedBigInteger($pivotPermission);
            $table->string('model_type');

            $modelMorphKey = $columnNames['model_morph_key'];
            throw_unless(
                is_string($modelMorphKey),
                RuntimeException::class,
                'Invalid model_morph_key'
            );
            $table->unsignedBigInteger($modelMorphKey);
            $table->index(
                [$modelMorphKey, 'model_type'],
                'model_has_permissions_model_id_model_type_index'
            );
            $table->foreign($pivotPermission)
                // permission id
                ->references('id')
                ->on($permissionsTable)
                ->onDelete('cascade');
            if ($teams) {
                $teamKey = $columnNames['team_foreign_key'];
                throw_unless(
                    is_string($teamKey),
                    RuntimeException::class,
                    'Invalid team_foreign_key'
                );
                $table->unsignedBigInteger($teamKey);
                $table->index(
                    $teamKey,
                    'model_has_permissions_team_foreign_key_index'
                );

                $table->primary(
                    [$teamKey, $pivotPermission, $modelMorphKey, 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );
            } else {
                $table->primary(
                    [$pivotPermission, $modelMorphKey, 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );
            }
        });

        $modelHasRolesTable = $tableNames['model_has_roles']
            ?? null;
        throw_unless(
            is_string($modelHasRolesTable),
            RuntimeException::class,
            'Invalid model_has_roles table name'
        );

        Schema::create($modelHasRolesTable, static function (
            Blueprint $table
        ) use ($rolesTable, $columnNames, $pivotRole, $teams): void {
            $table->unsignedBigInteger($pivotRole);
            $table->string('model_type');

            $modelMorphKey = $columnNames['model_morph_key'];
            throw_unless(
                is_string($modelMorphKey),
                RuntimeException::class,
                'Invalid model_morph_key'
            );
            $table->unsignedBigInteger($modelMorphKey);
            $table->index(
                [$modelMorphKey, 'model_type'],
                'model_has_roles_model_id_model_type_index'
            );
            $table->foreign($pivotRole)
                ->references('id') // role id
                ->on($rolesTable)
                ->onDelete('cascade');
            if ($teams) {
                $teamKey = $columnNames['team_foreign_key'];
                throw_unless(
                    is_string($teamKey),
                    RuntimeException::class,
                    'Invalid team_foreign_key'
                );
                $table->unsignedBigInteger($teamKey);
                $table->index(
                    $teamKey,
                    'model_has_roles_team_foreign_key_index'
                );
                $table->primary(
                    [$teamKey, $pivotRole, $modelMorphKey, 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );
            } else {
                $table->primary(
                    [$pivotRole, $modelMorphKey, 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );
            }
        });

        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? null;
        throw_unless(
            is_string($roleHasPermissionsTable),
            RuntimeException::class,
            'Invalid role_has_permissions table name'
        );

        Schema::create($roleHasPermissionsTable, static function (
            Blueprint $table
        ) use ($rolesTable, $permissionsTable, $pivotRole, $pivotPermission): void {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);
            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on($permissionsTable)
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id') // role id
                ->on($rolesTable)
                ->onDelete('cascade');

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        /** @var string|null $cacheStore */
        $cacheStore = config('permission.cache.store');

        /** @var string|null $cacheKey */
        $cacheKey = config('permission.cache.key');
        app(Illuminate\Contracts\Cache\Factory::class)
            ->store(
                $cacheStore !== 'default'
                    && is_string($cacheStore) ? $cacheStore : null
            )
            ->forget(
                is_string($cacheKey) ? $cacheKey : 'spatie.permission.cache'
            );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** @var array<string, mixed>|null $tableNames */
        $tableNames = config('permission.table_names');

        throw_unless(
            is_array($tableNames),
            Exception::class,
            'Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.'
        );

        $required = [
            'role_has_permissions',
            'model_has_roles',
            'model_has_permissions',
            'roles',
            'permissions',
        ];
        foreach ($required as $key) {
            throw_if(
                ! isset($tableNames[$key]) || ! is_string($tableNames[$key]),
                RuntimeException::class,
                sprintf("Invalid table name for '%s'", $key)
            );
        }

        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? null;
        throw_unless(
            is_string($roleHasPermissionsTable),
            RuntimeException::class,
            'Invalid table name for role_has_permissions'
        );
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? null;
        throw_unless(
            is_string($modelHasRolesTable),
            RuntimeException::class,
            'Invalid table name for model_has_roles'
        );
        $modelHasPermissionsTable = $tableNames['model_has_permissions'] ?? null;
        throw_unless(
            is_string($modelHasPermissionsTable),
            RuntimeException::class,
            'Invalid table name for model_has_permissions'
        );
        $rolesTable = $tableNames['roles'] ?? null;
        throw_unless(
            is_string($rolesTable),
            RuntimeException::class,
            'Invalid table name for roles'
        );
        $permissionsTable = $tableNames['permissions'] ?? null;
        throw_unless(
            is_string($permissionsTable),
            RuntimeException::class,
            'Invalid table name for permissions'
        );

        Schema::drop($roleHasPermissionsTable);
        Schema::drop($modelHasRolesTable);
        Schema::drop($modelHasPermissionsTable);
        Schema::drop($rolesTable);
        Schema::drop($permissionsTable);
    }
};
