<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status', 32)->default('draft');
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedBigInteger('delivered_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('staff_users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('delivered_by')
                ->references('id')
                ->on('staff_users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index(['status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
