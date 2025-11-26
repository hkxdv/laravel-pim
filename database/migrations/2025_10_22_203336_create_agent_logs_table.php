<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_logs', function (Blueprint $table): void {
            $table->id();
            // Identificación del agente y el usuario que disparó la acción
            $table->string('agent_name', 128);
            $table->unsignedBigInteger('user_id')->nullable();

            // Contexto y acción
            $table->string('module', 64)->nullable();
            $table->string('action', 128)->nullable();
            $table->string('status', 32)->default('ok');

            // Tiempos
            $table->unsignedInteger('duration_ms')->default(0);

            // Payloads (se usan JSON para compatibilidad; considerar JSONB en PostgreSQL)
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('meta')->nullable();

            // Info de cliente
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('staff_users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index(['agent_name']);
            $table->index(['user_id', 'created_at']);
            $table->index(['module', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_logs');
    }
};
