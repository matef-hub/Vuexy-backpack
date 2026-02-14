<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('company_documents', function (Blueprint $table) {
      $table->id();

      $table->string('docname')->index();
      $table->string('doc_number', 100)->nullable()->index();
      $table->string('doc_type', 100)->nullable()->index();

      $table->date('doc_issue_date')->index();
      $table->date('doc_end_date')->nullable()->index();

      // PDF / Image
      $table->string('doc_file')->nullable();
      $table->string('doc_original_name')->nullable();
      $table->string('doc_mime', 50)->nullable()->index();
      $table->unsignedBigInteger('doc_size')->nullable();

      $table->enum('status', ['active', 'expired', 'archived'])
        ->default('active')
        ->index();

      $table->text('notes')->nullable();

      $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
      $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

      $table->timestamps();
      $table->softDeletes();

      $table->index(['status', 'doc_end_date']);
      $table->index(['doc_type', 'status']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('company_documents');
  }
};
