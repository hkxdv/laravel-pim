<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /** @var string|null $connection */
        $connection = config('activitylog.database_connection');
        /** @var string|null $tableName */
        $tableName = config('activitylog.table_name');

        throw_unless(is_string($tableName), RuntimeException::class, 'Invalid activitylog.table_name configuration');

        Schema::connection(is_string($connection) ? $connection : null)
            ->create($tableName, function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('log_name')->nullable();
                $table->text('description');
                $table->nullableMorphs('subject', 'subject');
                $table->string('event')->nullable();
                $table->nullableMorphs('causer', 'causer');
                $table->json('properties')->nullable();
                $table->uuid('batch_uuid')->nullable();
                $table->timestamps();
                $table->index('log_name');
            });
    }

    public function down(): void
    {
        /** @var string|null $connection */
        $connection = config('activitylog.database_connection');
        /** @var string|null $tableName */
        $tableName = config('activitylog.table_name');

        throw_unless(is_string($tableName), RuntimeException::class, 'Invalid activitylog.table_name configuration');

        Schema::connection(is_string($connection) ? $connection : null)->dropIfExists($tableName);
    }
};
