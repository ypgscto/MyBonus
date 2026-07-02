<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presenter_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code', 20)->unique();
            $table->foreignId('pmb_period_id')->constrained('pmb_periods')->restrictOnDelete();
            $table->foreignId('presenter_id')->constrained('presenters')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transferred_to_finance_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by_finance_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transferred_to_presenter_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'draft',
                'submitted',
                'rejected_by_verifikator',
                'approved_by_verifikator',
                'transferred_to_finance',
                'received_by_finance',
                'transferred_to_presenter',
                'closed',
                'cancelled',
            ])->default('draft');
            $table->date('request_date');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('transferred_to_finance_at')->nullable();
            $table->timestamp('received_by_finance_at')->nullable();
            $table->timestamp('transferred_to_presenter_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_note')->nullable();
            $table->text('verifikator_note')->nullable();
            $table->text('finance_note')->nullable();
            $table->unsignedInteger('total_students')->default(0);
            $table->decimal('commission_per_student', 15, 2)->default(0);
            $table->decimal('total_commission', 15, 2)->default(0);
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presenter_requests');
    }
};
