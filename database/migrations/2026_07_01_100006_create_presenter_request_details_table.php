<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presenter_request_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presenter_request_id')->constrained('presenter_requests')->cascadeOnDelete();
            $table->string('nim', 30);
            $table->string('student_name');
            $table->date('birth_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_proof_file')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('nim');
            $table->index('presenter_request_id');
            $table->index(['nim', 'presenter_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presenter_request_details');
    }
};
