<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            if (! Schema::hasColumn('users', 'must_change_password')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->boolean('must_change_password')->default(false)->after('status');
                });
            }

            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin_pmb', 'verifikator', 'keuangan', 'presenter') NOT NULL");
        }

        if (! Schema::hasColumn('presenters', 'user_id')) {
            Schema::table('presenters', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
                $table->timestamp('account_created_at')->nullable()->after('status');
                $table->unique('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('presenters', 'user_id')) {
            Schema::table('presenters', function (Blueprint $table) {
                $table->dropUnique(['user_id']);
                $table->dropConstrainedForeignId('user_id');
                $table->dropColumn('account_created_at');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            if (Schema::hasColumn('users', 'must_change_password')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn('must_change_password');
                });
            }

            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin_pmb', 'verifikator', 'keuangan') NOT NULL");
        }
    }
};
