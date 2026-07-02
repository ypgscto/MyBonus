<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verifikator_transfers', function (Blueprint $table) {
            $table->unique('presenter_request_id');
        });

        Schema::table('presenter_transfers', function (Blueprint $table) {
            $table->unique('presenter_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('presenter_transfers', function (Blueprint $table) {
            $table->dropUnique(['presenter_request_id']);
        });

        Schema::table('verifikator_transfers', function (Blueprint $table) {
            $table->dropUnique(['presenter_request_id']);
        });
    }
};
