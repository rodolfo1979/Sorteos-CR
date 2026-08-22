<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raffles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('total_numbers');
            $table->unsignedTinyInteger('number_width')->default(5);
            $table->unsignedInteger('price_per_package');
            $table->unsignedSmallInteger('numbers_per_package')->default(1);
            $table->unsignedTinyInteger('max_random_changes')->default(5);
            $table->unsignedSmallInteger('reservation_minutes')->default(45);
            $table->string('assignment_mode', 16)->default('manual');
            $table->boolean('sale_enabled')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->date('draw_date')->nullable();
            $table->string('prize_title')->nullable();
            $table->text('prize_description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('organizer_name')->default('Rifas CR');
            $table->string('organizer_whatsapp')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->text('rules_text')->nullable();
            $table->timestamps();

            $table->index(['sale_enabled', 'is_featured']);
            $table->index('assignment_mode');
        });

        Schema::create('raffle_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->string('number', 24);
            $table->string('status', 16)->default('available');
            $table->timestamp('reserved_until')->nullable();
            $table->timestamps();

            $table->unique(['raffle_id', 'number']);
            $table->index(['raffle_id', 'status']);
            $table->index('reserved_until');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_uuid')->unique();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->string('buyer_name');
            $table->string('buyer_phone', 40);
            $table->string('buyer_email')->nullable();
            $table->unsignedTinyInteger('package_count')->default(1);
            $table->unsignedInteger('amount_total');
            $table->string('assignment_mode', 16);
            $table->unsignedTinyInteger('random_changes_used')->default(0);
            $table->string('status', 16)->default('pending');
            $table->string('receipt_path')->nullable();
            $table->string('receipt_original_name')->nullable();
            $table->string('receipt_mime')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['raffle_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('order_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raffle_number_id')->constrained()->cascadeOnDelete();
            $table->string('number', 24);
            $table->timestamps();

            $table->unique(['order_id', 'raffle_number_id']);
            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_numbers');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('raffle_numbers');
        Schema::dropIfExists('raffles');
    }
};
