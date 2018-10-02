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
                'subtitle' => 'Secure, Contain, Protect',
                'locale' => 'en',
                'membership' => 'apply',
                'start_page' => 'main',
                'alternate_subdomains' => array('scp-wiki'),
                'alternate_domains' => array('www.scp-wiki.com', 'scp-wiki.com', 'www.scp-wiki.net', 'scp-wiki.net'),
                'namespaces' => array(
                    '_default',
                    'system',
                    'component',
                    'protected'
                )
            ))
        ]);
    }
}
