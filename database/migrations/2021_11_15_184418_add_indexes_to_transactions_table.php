<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIndexesToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('accounting.table_prefix').'transactions', function (Blueprint $table) {
            $table->index(['transaction_type','entity_id'],"type_entity_index");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('accounting.table_prefix').'transactions', function (Blueprint $table) {
            $table->dropIndex('type_entity_index');
        });
    }
}