<?php

use Illuminate\Database\Seeder;

class DomainsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('domains')->insert([
            'domain' => 'www.scuttle.laravel',
            'wiki_id' => 2
        ]);
        DB::table('domains')->insert([
            'domain' => 'scuttle.laravel',
            'wiki_id' => 2
        ]);
        DB::table('domains')->insert([
            'domain' => 'scuttle.bluesoul.net',
            'wiki_id' => 2
        ]);
    }
}
