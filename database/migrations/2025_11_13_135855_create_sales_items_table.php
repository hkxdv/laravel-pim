<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('qty');
            $table->decimal('price', 12, 2);
            $table->timestamps();

            $table->foreign('sales_order_id')
                ->references('id')
                ->on('sales_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(['sales_order_id']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_items');
    }
};
