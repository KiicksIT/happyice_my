<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('username')->unique();
            $table->string('password', 60);
            $table->string('contact');
            $table->rememberToken();

            $table->string('type');
            $table->timestamps();
            $table->softDeletes();

            $table->boolean('can_access_inv')->default(1);
            $table->string('user_code')->nullable();
        });

        $statement = "ALTER TABLE users AUTO_INCREMENT = 100001;";
        DB::unprepared($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
