<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIndexesToLedgersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('accounting.table_prefix').'ledgers', function (Blueprint $table) {
            $table->index(
                ['post_account','entry_type','entity_id','deleted_at','posting_date'],
                "accounting_ledgers_idx_post_entry_entity_delete_postin"
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('accounting.table_prefix').'ledgers', function (Blueprint $table) {
            $table->dropIndex('accounting_ledgers_idx_post_entry_entity_delete_postin');
        });
    }
}