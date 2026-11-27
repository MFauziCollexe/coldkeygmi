<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'checklist_audit_logs',
        'checklist_approvals',
        'checklist_scans',
        'checklist_answers',
        'checklist_template_questions',
        'checklist_template_sections',
    ];

    public function up(): void
    {
        $tablesWithRows = array_values(array_filter(
            $this->tables,
            fn (string $table) => Schema::hasTable($table) && DB::table($table)->exists()
        ));

        if ($tablesWithRows !== []) {
            throw new RuntimeException(
                'Refusing to drop unused checklist tables containing rows: '.implode(', ', $tablesWithRows)
            );
        }

        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
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

            $table->index(
                ['checklist_header_id', 'scan_scope', 'scope_key'],
                'chk_scans_hdr_scope_key_idx'
            );
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

            $table->index(
                ['checklist_header_id', 'approval_type', 'scope_key'],
                'chk_appr_hdr_type_scope_idx'
            );
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
};
