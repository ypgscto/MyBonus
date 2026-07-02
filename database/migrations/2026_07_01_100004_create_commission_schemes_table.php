<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presenter_category_id')->constrained('presenter_categories')->restrictOnDelete();
            $table->foreignId('pmb_period_id')->constrained('pmb_periods')->restrictOnDelete();
            $table->decimal('commission_amount_per_student', 15, 2);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();

            $table->unique(['presenter_category_id', 'pmb_period_id'], 'commission_schemes_category_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_schemes');
    }
};
