<?php

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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->integer('amount'); // + для прихода, - для расхода
            $table->string('operation_type'); // purchase, sale, adjustment, return etc.
            $table->morphs('source'); // Полиморфная связь (order, inventory etc.)
            $table->text('reason')->nullable(); // Комментарий
            $table->foreignId('user_id')->nullable()->constrained(); // Кто совершил
            $table->timestamps();

            $table->index(['stock_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
