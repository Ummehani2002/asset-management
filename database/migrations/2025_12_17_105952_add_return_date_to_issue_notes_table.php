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
        Schema::table('issue_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('issue_notes', 'return_date')) {
                $table->date('return_date')->nullable()->after('issued_date');
            }
            if (!Schema::hasColumn('issue_notes', 'note_type')) {
                $table->string('note_type')->default('issue')->after('return_date'); // 'issue' or 'return'
            }
            if (!Schema::hasColumn('issue_notes', 'issue_note_id')) {
                $table->unsignedBigInteger('issue_note_id')->nullable()->after('note_type'); // Reference to original issue note
            }
        });
        
        // Add foreign key separately - try catch to avoid error if already exists
        try {
            Schema::table('issue_notes', function (Blueprint $table) {
                $table->foreign('issue_note_id')->references('id')->on('issue_notes')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Foreign key might already exist, ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issue_notes', function (Blueprint $table) {
            // Check if foreign key exists before trying to drop it
            if (Schema::hasColumn('issue_notes', 'issue_note_id')) {
                try {
                    $table->dropForeign(['issue_note_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
            }
            if (Schema::hasColumn('issue_notes', 'return_date')) {
                $table->dropColumn('return_date');
            }
            if (Schema::hasColumn('issue_notes', 'note_type')) {
                $table->dropColumn('note_type');
            }
            if (Schema::hasColumn('issue_notes', 'issue_note_id')) {
                $table->dropColumn('issue_note_id');
            }
        });
    }
};
