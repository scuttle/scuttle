@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">API</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <passport-clients></passport-clients><br>
                    <passport-authorized-clients></passport-authorized-clients><br>
                    <passport-personal-access-tokens></passport-personal-access-tokens><br>

                    Personal Access Token Usage (Python):<br>

<pre><code>
import requests

scuttle_endpoint = "http://scpfoundation.wiki/api"
scuttle_token = """eyJ0eXAiOiJKV1QiLC(...)"""  # Personal Access Token with read-revision scope.

headers = {"Authorization": "Bearer " + scuttle_token}
r = requests.get(scuttle_endpoint + '/revisions', headers=headers).json()  # Returns dict.
</code></pre><br>
                    Standard user accounts have access to <code>read-metadata</code>, <code>read-article</code>, and
                        <code>read-votes</code> only and are rate-limited to 480 requests/min.
                        Contact bluesoul to request more permission.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
