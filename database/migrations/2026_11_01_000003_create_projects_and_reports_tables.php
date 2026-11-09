<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infrastructure_projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            // bts | fiber | satellite | public_wifi | microwave
            $table->string('type', 30);
            $table->text('description')->nullable();
            $table->decimal('budget_usd', 14, 2)->default(0);
            // proposed | approved | rejected | in_progress | on_hold | completed | cancelled
            $table->string('status', 20)->default('proposed')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->decimal('priority_score', 5, 2)->default(0);
            $table->unsignedInteger('beneficiaries')->default(0);
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infrastructure_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->unsignedTinyInteger('progress')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 60);
            // none | 2g | 3g | 4g | 5g | fiber
            $table->string('technology', 10);
            // blank_spot | slow | unstable | outage | expensive
            $table->string('issue_type', 20)->index();
            $table->decimal('download_mbps', 8, 2)->nullable();
            $table->decimal('upload_mbps', 8, 2)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('description');
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedTinyInteger('severity_score')->default(0);
            // low | medium | high | critical
            $table->string('severity_level', 10)->default('low')->index();
            // pending | verified | rejected | resolved
            $table->string('status', 15)->default('pending')->index();
            // manual | speedtest | import
            $table->string('source', 15)->default('manual');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('infrastructure_project_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('project_updates');
        Schema::dropIfExists('infrastructure_projects');
    }
};
