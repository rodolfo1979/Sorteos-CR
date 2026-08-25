<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffle_numbers', function (Blueprint $table) {
            $table->index(['raffle_id', 'status', 'id'], 'raffle_numbers_raffle_status_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('raffle_numbers', function (Blueprint $table) {
            $table->dropIndex('raffle_numbers_raffle_status_id_index');
        });
    }
};
