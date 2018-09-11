<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCtnPcsDeals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vendings', function ($table) {
            // $table->string('vend_id')->nullable();
            // $table->string('name')->nullable();
            // $table->string('serial_no');
            // $table->string('router')->nullable();
            // $table->text('desc')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vendings', function ($table) {
            // $table->dropColumn('vend_id');
            // $table->dropColumn('name');
            // $table->dropColumn('serial_no');
            // $table->dropColumn('router');
            // $table->dropColumn('desc');
        });
    }
}
