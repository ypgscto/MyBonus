<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikator_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presenter_request_id')->constrained('presenter_requests')->cascadeOnDelete();
            $table->foreignId('transferred_by')->constrained('users')->restrictOnDelete();
            $table->date('transfer_date');
            $table->decimal('transfer_amount', 15, 2);
            $table->foreignId('finance_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('destination_bank');
            $table->string('destination_account_number', 50);
            $table->string('destination_account_name');
            $table->string('transfer_proof_file')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikator_transfers');
    }
};
