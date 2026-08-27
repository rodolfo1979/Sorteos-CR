<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('status', 24)->default('active');
                $table->string('primary_domain')->nullable()->unique();
                $table->string('admin_email')->nullable();
                $table->string('notification_email')->nullable();
                $table->string('timezone', 64)->default('America/Costa_Rica');
                $table->string('currency', 8)->default('CRC');
                $table->string('logo_path')->nullable();
                $table->string('primary_color', 24)->nullable();
                $table->string('accent_color', 24)->nullable();
                $table->timestamps();

                $table->index('status');
            });
        }

        if (! Schema::hasTable('tenant_domains')) {
            Schema::create('tenant_domains', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('domain')->unique();
                $table->string('type', 24)->default('primary');
                $table->boolean('is_verified')->default(false);
                $table->timestamps();

                $table->index(['tenant_id', 'type']);
                $table->index('is_verified');
            });
        }

        if (! Schema::hasTable('tenant_settings')) {
            Schema::create('tenant_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('mail_from_address')->nullable();
                $table->string('mail_from_name')->nullable();
                $table->string('notification_email')->nullable();
                $table->string('whatsapp_number')->nullable();
                $table->text('payment_instructions')->nullable();
                $table->unsignedSmallInteger('reservation_minutes_default')->default(45);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('raffles', 'tenant_id')) {
            Schema::table('raffles', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
                $table->index(['tenant_id', 'sale_enabled', 'is_featured']);
            });
        }

        if (! Schema::hasColumn('raffle_numbers', 'tenant_id')) {
            Schema::table('raffle_numbers', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
                $table->index(['tenant_id', 'raffle_id', 'status']);
                $table->index(['tenant_id', 'reserved_until']);
            });
        }

        if (! Schema::hasColumn('orders', 'tenant_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
                $table->index(['tenant_id', 'raffle_id', 'status']);
                $table->index(['tenant_id', 'created_at']);
            });
        }

        if (! Schema::hasColumn('order_events', 'tenant_id')) {
            Schema::table('order_events', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
                $table->index(['tenant_id', 'order_id', 'created_at']);
                $table->index(['tenant_id', 'action']);
            });
        }

        $now = now();
        $appDomain = parse_url((string) config('app.url'), PHP_URL_HOST) ?: null;
        $tenantId = DB::table('tenants')->where('slug', 'sorteos-cr')->value('id');

        if (! $tenantId) {
            $tenantId = DB::table('tenants')->insertGetId([
                'name' => 'Sorteos CR',
                'slug' => 'sorteos-cr',
                'status' => 'active',
                'primary_domain' => $appDomain,
                'admin_email' => config('admin.notification_email'),
                'notification_email' => config('admin.notification_email'),
                'timezone' => 'America/Costa_Rica',
                'currency' => 'CRC',
                'primary_color' => '#0f172a',
                'accent_color' => '#0891b2',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('tenants')->where('id', $tenantId)->update([
                'primary_domain' => DB::raw('coalesce(primary_domain, '.DB::getPdo()->quote((string) $appDomain).')'),
                'admin_email' => DB::raw('coalesce(admin_email, '.DB::getPdo()->quote((string) config('admin.notification_email')).')'),
                'notification_email' => DB::raw('coalesce(notification_email, '.DB::getPdo()->quote((string) config('admin.notification_email')).')'),
                'updated_at' => $now,
            ]);
        }

        $domain = DB::table('tenants')->where('id', $tenantId)->value('primary_domain');
        if ($domain && ! DB::table('tenant_domains')->where('domain', Str::lower($domain))->exists()) {
            DB::table('tenant_domains')->insert([
                'tenant_id' => $tenantId,
                'domain' => Str::lower($domain),
                'type' => 'primary',
                'is_verified' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! DB::table('tenant_settings')->where('tenant_id', $tenantId)->exists()) {
            DB::table('tenant_settings')->insert([
                'tenant_id' => $tenantId,
                'mail_from_address' => config('mail.from.address'),
                'mail_from_name' => config('mail.from.name'),
                'notification_email' => config('admin.notification_email'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('raffles')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        DB::statement('update raffle_numbers set tenant_id = (select tenant_id from raffles where raffles.id = raffle_numbers.raffle_id) where tenant_id is null');
        DB::statement('update orders set tenant_id = (select tenant_id from raffles where raffles.id = orders.raffle_id) where tenant_id is null');
        DB::statement('update order_events set tenant_id = (select tenant_id from orders where orders.id = order_events.order_id) where tenant_id is null');
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_events', 'tenant_id')) {
            Schema::table('order_events', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'order_id', 'created_at']);
                $table->dropIndex(['tenant_id', 'action']);
                $table->dropConstrainedForeignId('tenant_id');
            });
        }

        if (Schema::hasColumn('orders', 'tenant_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'raffle_id', 'status']);
                $table->dropIndex(['tenant_id', 'created_at']);
                $table->dropConstrainedForeignId('tenant_id');
            });
        }

        if (Schema::hasColumn('raffle_numbers', 'tenant_id')) {
            Schema::table('raffle_numbers', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'raffle_id', 'status']);
                $table->dropIndex(['tenant_id', 'reserved_until']);
                $table->dropConstrainedForeignId('tenant_id');
            });
        }

        if (Schema::hasColumn('raffles', 'tenant_id')) {
            Schema::table('raffles', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'sale_enabled', 'is_featured']);
                $table->dropConstrainedForeignId('tenant_id');
            });
        }

        Schema::dropIfExists('tenant_settings');
        Schema::dropIfExists('tenant_domains');
        Schema::dropIfExists('tenants');
    }
};