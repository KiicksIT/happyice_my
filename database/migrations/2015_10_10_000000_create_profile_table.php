<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProfileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('roc_no')->nullable();
            $table->text('address')->nullable();
            $table->string('contact');
            $table->string('alt_contact')->nullable();
            $table->string('email')->nullable();
            $table->integer('gst');
            $table->string('header')->nullable();
            $table->string('logo')->nullable();
            $table->string('footer')->nullable();
            $table->timestamps();

            $table->string('acronym')->nullable();
            $table->integer('payterm_id')->nullable();
            $table->string('attn')->nullable();
            $table->boolean('is_gst_inclusive')->default(0);
            $table->decimal('gst_rate', 5, 2)->nullable();
            $table->integer('currency_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Schema::drop('profiles');
        // DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
