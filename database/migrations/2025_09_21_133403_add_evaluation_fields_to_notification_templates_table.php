<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->string('notification_type')->nullable()->after('content')->comment('Type: evaluation_report, course_completion, etc.');
            $table->string('target_manager_level')->nullable()->after('notification_type')->comment('L2, L3, L4, or all');
            $table->integer('employee_count')->default(0)->after('target_manager_level')->comment('Number of employees in this notification');
            $table->string('department_name')->nullable()->after('employee_count')->comment('Target department name');

            // User tracking
            $table->unsignedBigInteger('sent_by')->nullable()->after('department_name')->comment('Admin who sent the notification');
            $table->timestamp('sent_at')->nullable()->after('sent_by')->comment('When notification was sent');

            // Email tracking
            $table->string('email_subject')->nullable()->after('sent_at')->comment('Email subject line');
            $table->integer('managers_notified')->default(0)->after('email_subject')->comment('Number of managers who received this notification');
            $table->text('manager_emails')->nullable()->after('managers_notified')->comment('Comma-separated list of manager emails');

            // Status tracking
            $table->enum('status', ['draft', 'sent', 'failed', 'partial'])->default('draft')->after('manager_emails');
            $table->text('failure_reason')->nullable()->after('status')->comment('Reason if sending failed');

            // Evaluation tracking (separate columns instead of JSON)
            $table->text('evaluation_ids')->nullable()->after('failure_reason')->comment('Comma-separated evaluation IDs');
            $table->text('employee_names')->nullable()->after('evaluation_ids')->comment('Comma-separated employee names for easy reference');

            // Add foreign key constraint
            $table->foreign('sent_by')->references('id')->on('users')->onDelete('set null');

            // Add indexes for better performance
            $table->index('notification_type');
            $table->index('department_name');
            $table->index('sent_by');
            $table->index('sent_at');
            $table->index('status');        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropForeign(['sent_by']);

            // Drop indexes
            $table->dropIndex(['notification_type']);
            $table->dropIndex(['department_name']);
            $table->dropIndex(['sent_by']);
            $table->dropIndex(['sent_at']);
            $table->dropIndex(['status']);

            // Drop columns
            $table->dropColumn([
                'notification_type',
                'target_manager_level',
                'employee_count',
                'department_name',
                'sent_by',
                'sent_at',
                'email_subject',
                'managers_notified',
                'manager_emails',
                'status',
                'failure_reason',
                'evaluation_ids',
                'employee_names'
            ]);
        });
    }
};
