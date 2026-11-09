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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('condition');
            $table->unsignedInteger('price')->default(0);
            $table->boolean('is_donation')->default(false);
            $table->string('handover_method')->default('cod');
            $table->json('cod_areas');
            $table->string('location')->nullable();
            $table->string('status')->default('pending');
            $table->text('moderation_note')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
