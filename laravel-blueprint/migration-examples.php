<?php

// Ejemplos de migraciones Laravel. Separar en archivos individuales al implementar.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('raffles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('total_numbers');
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('numbers_per_order')->default(1);
            $table->unsignedSmallInteger('max_random_changes')->default(5);
            $table->enum('assignment_mode', ['manual', 'random'])->default('manual');
            $table->boolean('sale_enabled')->default(true);
            $table->date('draw_date')->nullable();
            $table->text('prize')->nullable();
            $table->string('image_path')->nullable();
            $table->string('organizer_name')->default('Rifas CR');
            $table->string('organizer_whatsapp')->nullable();
            $table->text('payment_info')->nullable();
            $table->text('rules_text')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->string('buyer_name');
            $table->string('buyer_phone');
            $table->string('buyer_email')->nullable();
            $table->unsignedInteger('amount');
            $table->unsignedSmallInteger('package_count')->default(1);
            $table->enum('assignment_mode', ['manual', 'random']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->string('receipt_path')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['raffle_id', 'status']);
        });

        Schema::create('raffle_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->string('number', 12);
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reserved_until')->nullable();
            $table->timestamps();

            $table->unique(['raffle_id', 'number']);
            $table->index(['raffle_id', 'status']);
            $table->index('reserved_until');
        });

        Schema::create('order_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raffle_number_id')->constrained()->cascadeOnDelete();
            $table->string('number', 12);
            $table->timestamps();

            $table->unique(['order_id', 'raffle_number_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('order_numbers');
        Schema::dropIfExists('raffle_numbers');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('raffles');
    }
};
