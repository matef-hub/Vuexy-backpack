<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();

            // Landlord fields
            $table->string('landlord_name')->index();
            $table->enum('landlord_entity_type', ['individual', 'company', 'sole_proprietorship'])->index();
            $table->string('landlord_national_id', 20)->nullable()->index();
            $table->text('landlord_address')->nullable();

            // Tenant fields
            $table->string('tenant_name')->index();
            $table->enum('tenant_entity_type', ['individual', 'company', 'sole_proprietorship'])->index();
            $table->string('tenant_national_id', 20)->nullable()->index();
            $table->text('tenant_address')->nullable();

            // Unit fields
            $table->string('unit_number', 100)->index();
            $table->text('unit_address');
            $table->decimal('unit_area_sqm', 10, 2)->nullable();

            // Lease terms
            $table->unsignedInteger('lease_duration_months')->nullable();
            $table->date('lease_start_date')->index();
            $table->date('lease_end_date')->index();

            // Payments
            $table->decimal('monthly_rent', 12, 2)->index();
            $table->decimal('security_deposit', 12, 2)->nullable();

            // Status and notes
            $table->enum('status', ['draft', 'active', 'expired', 'terminated'])->default('active')->index();
            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes
            $table->index(['status', 'lease_end_date']);
            $table->index(['landlord_entity_type', 'tenant_entity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_contracts');
    }
};
