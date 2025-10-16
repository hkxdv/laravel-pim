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
        Schema::create('staff_login_infos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('staff_user_id')->constrained('staff_users')->onDelete('cascade');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->boolean('is_mobile')->default(false);
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('last_login_at');
            $table->integer('login_count')->default(1);
            $table->timestamps();

            $table->index(['staff_user_id', 'ip_address']);
            $table->index(['staff_user_id', 'is_trusted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_login_infos');
    }
};
