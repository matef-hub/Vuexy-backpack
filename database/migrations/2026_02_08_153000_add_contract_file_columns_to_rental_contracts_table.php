<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->string('contract_file')->nullable()->after('security_deposit');
            $table->string('contract_original_name')->nullable()->after('contract_file');
            $table->string('contract_mime', 50)->nullable()->after('contract_original_name');
            $table->unsignedBigInteger('contract_size')->nullable()->after('contract_mime');

            $table->index('contract_mime');
        });
    }

    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->dropIndex(['contract_mime']);
            $table->dropColumn([
                'contract_file',
                'contract_original_name',
                'contract_mime',
                'contract_size',
            ]);
        });
    }
};
