<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('internet_users_pct', 5, 2);
            $table->decimal('mobile_broadband_per100', 6, 2)->nullable();
            $table->decimal('fixed_broadband_per100', 6, 2)->nullable();
            $table->decimal('avg_mobile_speed_mbps', 7, 2)->nullable();
            $table->decimal('avg_fixed_speed_mbps', 7, 2)->nullable();
            $table->decimal('data_price_usd_gb', 6, 2)->nullable();
            $table->string('data_source', 180);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['country_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
