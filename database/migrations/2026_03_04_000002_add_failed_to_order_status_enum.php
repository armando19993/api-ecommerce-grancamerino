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
        // Modificar el enum para agregar 'failed'
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'paid', 'preparing', 'shipped', 'in_transit', 'delivered', 'cancelled', 'failed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al enum original
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'paid', 'preparing', 'shipped', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending'");
    }
};
