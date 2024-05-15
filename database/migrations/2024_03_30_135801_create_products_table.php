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
            $table->string('name');
            $table->string('slug');
            // $table->string('sku')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('qty')->default(0);
            $table->unsignedBigInteger('security_stock')->default(0);
            $table->decimal('price', 10, 2)->nullable();
            $table->text('photo_url')->nullable();
            $table->timestamps();
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
