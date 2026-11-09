<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom tambahan Sprint 2 untuk alur booking aman (Proposal 3.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('cod_area')->default('inside')->after('cod_location_id');
            $table->timestamp('accepted_at')->nullable()->after('respond_by');
            $table->timestamp('reminded_at')->nullable()->after('seller_confirmed_at');
            $table->timestamp('completed_at')->nullable()->after('reminded_at');
            $table->foreignId('cancelled_by')->nullable()->after('completed_by')->constrained('users')->nullOnDelete();
            $table->foreignId('no_show_reported_by')->nullable()->after('cancelled_by')->constrained('users')->nullOnDelete();
            $table->timestamp('no_show_reported_at')->nullable()->after('no_show_reported_by');
            $table->text('dispute_note')->nullable()->after('no_show_reported_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropConstrainedForeignId('no_show_reported_by');
            $table->dropColumn(['cod_area', 'accepted_at', 'reminded_at', 'completed_at', 'no_show_reported_at', 'dispute_note']);
        });
    }
};
