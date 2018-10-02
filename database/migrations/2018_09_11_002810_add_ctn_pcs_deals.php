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
        Schema::table('deals', function ($table) {
            $table->integer('ctn');
            $table->integer('pcs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals', function ($table) {
            $table->dropColumn('ctn');
            $table->dropColumn('pcs');
        });
    }
}
