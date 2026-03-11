<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $indexName = 'draw_requests_session_request_player_idx';
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "CREATE INDEX {$indexName} ON draw_requests (session_id, request_id(64), player_id)"
            );
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement(
                "CREATE INDEX {$indexName} ON draw_requests (session_id, request_id, player_id)"
            );
            return;
        }

        Schema::table('draw_requests', function (Blueprint $table) use ($indexName) {
            $table->index(['session_id', 'request_id', 'player_id'], $indexName);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexName = 'draw_requests_session_request_player_idx';
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("DROP INDEX {$indexName} ON draw_requests");
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("DROP INDEX {$indexName}");
            return;
        }

        Schema::table('draw_requests', function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }
};
