<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddScopesToActivityLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->integer('user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
}
