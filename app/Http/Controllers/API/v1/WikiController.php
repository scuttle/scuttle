<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Domain;
use App\Wiki;

class WikiController extends Controller
{
    public function wiki_get_wiki(Domain $domain)
    {
        $wiki = Wiki::find($domain->wiki_id);
        $wiki->metadata = json_decode($wiki->metadata, true);
        $payload = $wiki->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function wiki_get_wikis()
    {
        $wikis = Wiki::with('domains')->get();
        foreach ($wikis as $wiki) {
            $wiki->metadata = json_decode($wiki->metadata, true);
            foreach($wiki->domains as $domain) {
                $domain->metadata = json_decode($domain->metadata, true);
            }
        }
        $payload = $wikis->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }
}
