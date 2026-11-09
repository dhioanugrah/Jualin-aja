<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 3: catatan tindak lanjut admin untuk laporan dan sengketa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('handled_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable()->after('handled_by');
            $table->text('admin_note')->nullable()->after('handled_at');
            $table->index(['status', 'created_at']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('dispute_resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispute_resolved_at')->nullable();
            $table->string('dispute_outcome')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dispute_resolved_by');
            $table->dropColumn(['dispute_resolved_at', 'dispute_outcome']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropConstrainedForeignId('handled_by');
            $table->dropColumn(['handled_at', 'admin_note']);
        });
    }
};
