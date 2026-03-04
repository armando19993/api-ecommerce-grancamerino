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
        // Modificar el enum para agregar 'stripe'
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_gateway` ENUM('wompi', 'paymentnow', 'stripe', 'cash') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al enum original
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_gateway` ENUM('wompi', 'paymentnow', 'cash') NULL");
    }
};
