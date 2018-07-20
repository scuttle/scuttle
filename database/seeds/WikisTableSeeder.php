<?php

use Illuminate\Database\Seeder;

class WikisTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('wikis')->insert([
            'subdomain' => 'admin',
            'WikiJson' => json_encode(array(
                'title' => 'Admin Control Panel',
                'locale' => 'en',
                'visible' => false,
                'membership' => 'invite'
            ))
        ]);
        DB::table('wikis')->insert([
            'subdomain' => 'www',
            'WikiJson' => json_encode(array(
                'title' => 'The SCP Foundation',
                'locale' => 'en',
                'membership' => 'apply'
            ))
        ]);
    }
}
