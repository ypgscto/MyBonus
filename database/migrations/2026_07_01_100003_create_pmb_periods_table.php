<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pmb_periods', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20);
            $table->string('wave');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();

            $table->unique(['academic_year', 'wave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmb_periods');
    }
};
