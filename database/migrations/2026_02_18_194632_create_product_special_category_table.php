<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_special_category', function (Blueprint $table) {
            $table->uuid('id', 36)->primary()->default(DB::raw('(UUID())'));
            $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('special_category_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_special_category');
    }
};
