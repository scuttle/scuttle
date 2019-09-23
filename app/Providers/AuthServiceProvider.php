<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        /**
         * Lay out some scopes that API tokens can have. Simple verb-noun grammar.
         */
        Passport::tokensCan([
            'read-metadata' => 'Read basic metadata about articles and users',
            'read-article' => 'Get the latest revision of an article and associated metadata',
            'read-revision' => 'Get a specific revision and associated metadata',
            'read-votes' => 'Get a list of votes, by user or by article',
            'read-post' => 'Get a specific forum post and all revisions',
            'read-thread' => 'Get an entire forum thread',
            'write-metadata' => 'Update metadata about articles and users',
            'write-revision' => 'Commit a revision to the SCUTTLE DB',
            'write-votes' => 'Update votes by user or article',
            'write-post' => 'Create or update a forum post',
            'write-thread' => 'Create or update thread metadata.'
        ]);

        Passport::setDefaultScope([
           'read-metadata',
           'read-votes'
        ]);

        Passport::routes();
    }
}
