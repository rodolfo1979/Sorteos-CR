<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'admin_username')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('admin_username')->nullable()->after('admin_email');
                $table->string('admin_password_hash')->nullable()->after('admin_username');
                $table->index('admin_username');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'admin_username')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropIndex(['admin_username']);
                $table->dropColumn(['admin_username', 'admin_password_hash']);
            });
        }
    }
};
