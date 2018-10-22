<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $array = array(
            'roles' => array(
                'admin' => array(
                    'global_administrator' => true,
                ),
                'www' => array(
                    'member' => true,
                ),
            )
        );
        $metadata = json_encode($array);

        DB::table('users')->insert([
           'username' => 'bluesoul',
           'email' => 'bluesoul@o5command.com',
           // if you want to set your own password, you need to bcrypt("password") via php artisan tinker
           'password' => "$2y$10$8TYadxDwpCaj1t4RZz6b5.V0ckTNTnarKi76F0ibFJinJ1.gha8zq",
           'metadata' => $metadata,
        ]);
    }
}
