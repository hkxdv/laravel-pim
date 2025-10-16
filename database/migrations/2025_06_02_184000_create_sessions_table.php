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
        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            /**
             * Columna genérica `user_id` para compatibilidad con el listener de eventos de login en SQLite.
             * El listener `SessionServiceProvider` intenta actualizar esta columna, y fallaba porque no existía.
             * No se usa como clave foránea para evitar conflictos entre los distintos tipos de usuario.
             */
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignId('staff_user_id')->nullable()->constrained('staff_users')->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
