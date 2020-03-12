<?php

use Illuminate\Database\Seeder;
use App\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds for user
     *
     * @return void
     */
    public function run()
    {
/*
        User::create([
            'name' => 'brian',
            'email' => 'leehongjie91@gmail.com',
            'username' => 'brian',
            'password' => 'brian',
            'contact' => '83089699',
        ]);

        User::create([
            'name' => 'user',
            'email' => 'user@gmail.com',
            'username' => 'user',
            'password' => 'user',
            'contact' => '83089699',
        ]);  */

        User::create([
            'name' => 'sc1',
            'email' => 'superiorcreamery@gmail.com',
            'username' => 'sc1',
            'password' => '12345678',
            'contact' => '',
        ]);
    }
}
