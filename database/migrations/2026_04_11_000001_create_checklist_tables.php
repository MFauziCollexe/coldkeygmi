<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('module')->default('gmiic.checklist');
            $table->unsignedInteger('version_no')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('checklist_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('checklist_templates')->cascadeOnDelete();
            $table->string('code');
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['template_id', 'code']);
        });

        Schema::create('checklist_template_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('checklist_templates')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('checklist_template_sections')->nullOnDelete();
            $table->string('question_code');
            $table->text('question_text');
            $table->string('answer_type')->default('boolean');
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'question_code']);
        });

        Schema::create('checklist_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('checklist_templates')->restrictOnDelete();
            $table->string('entry_code')->unique();
            $table->string('title')->nullable();
            $table->string('period_type')->nullable();
            $table->string('period_value')->nullable();
            $table->string('area_code')->nullable();
            $table->string('location_code')->nullable();
            $table->string('status')->default('draft');
            $table->string('current_step')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('payload_summary_json')->nullable();
            $table->timestamps();

            $table->index(['template_id', 'status']);
            $table->index(['period_value', 'area_code']);
        });

        Schema::create('checklist_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_header_id')->constrained('checklist_headers')->cascadeOnDelete();
            $table->unsignedInteger('version_no')->default(1);
            $table->json('state_json');
            $table->foreignId('saved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('saved_at')->nullable();
            $table->timestamps();

            $table->unique(['checklist_header_id', 'version_no']);
        });

        Schema::create('checklist_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_header_id')->constrained('checklist_headers')->cascadeOnDelete();
            $table->foreignId('template_question_id')->constrained('checklist_template_questions')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('checklist_template_sections')->nullOnDelete();
            $table->string('scope_key')->nullable();
            $table->text('answer_value')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['checklist_header_id', 'scope_key']);
            $table->unique(['checklist_header_id', 'template_question_id', 'scope_key'], 'checklist_answers_unique_scope');
        });

        Schema::create('checklist_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_header_id')->constrained('checklist_headers')->cascadeOnDelete();
            $table->string('scan_scope');
            $table->string('scope_key')->nullable();
            $table->string('barcode_value');
            $table->timestamp('scan_date')->nullable();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['checklist_header_id', 'scan_scope', 'scope_key']);
        });

        Schema::create('checklist_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_header_id')->constrained('checklist_headers')->cascadeOnDelete();
            $table->string('approval_type');
            $table->string('scope_key')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['checklist_header_id', 'approval_type', 'scope_key']);
        });

        Schema::create('checklist_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_header_id')->constrained('checklist_headers')->cascadeOnDelete();
            $table->string('action');
            $table->json('old_value_json')->nullable();
            $table->json('new_value_json')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('logged_at')->nullable();
            $table->timestamps();

            $table->index(['checklist_header_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_audit_logs');
        Schema::dropIfExists('checklist_approvals');
        Schema::dropIfExists('checklist_scans');
        Schema::dropIfExists('checklist_answers');
        Schema::dropIfExists('checklist_states');
        Schema::dropIfExists('checklist_headers');
        Schema::dropIfExists('checklist_template_questions');
        Schema::dropIfExists('checklist_template_sections');
        Schema::dropIfExists('checklist_templates');
    }
};
