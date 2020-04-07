@extends('layouts.app')

@section('title')
{{__('Welcome')}}
@endsection

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
                    The v1 API is <b>not</b> currently finalized. You can follow the
                    <a href="https://scuttle.atlassian.net/browse/SCUTTLE-3">JIRA Epic</a> or the
                    <a href="https://app.swaggerhub.com/apis/scuttle/scuttle/1.0.0#/">Swaggerhub</a> to see progress.<br>
                    You can also submit a <a href="https://scuttle.atlassian.net/browse/HELP">Help Ticket</a> for
                    feature requests. The APIs are designed to be immutable, so if you have questions or concerns about
                    a method or endpoint, speak up before the version is finalized.<br><br>
                    <passport-clients></passport-clients><br>
                    <passport-authorized-clients></passport-authorized-clients><br>
                    <passport-personal-access-tokens></passport-personal-access-tokens><br>

                    Personal Access Token Usage (Python):<br>

<pre><code>
import requests

scuttle_endpoint = "https://scuttle.bluesoul.net/api/v1"
scuttle_token = """eyJ0eXAiOiJKV1QiLC(...)"""  # Personal Access Token with read-revision scope.

headers = {"Authorization": "Bearer " + scuttle_token}
r = requests.get(scuttle_endpoint + '/page', headers=headers).json()  # Returns dict.
</code></pre>
                    Currently supported routes:<br>
                        <table border="1" width="100%">
                            <thead>
                                <tr>
                                    <th style="padding: 2px;">Method</th>
                                    <th width="33%">Location</th>
                                    <th>Returns</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>GET</td>
                                    <td><code>/page</code></td>
                                    <td>A manifest of page slugs, wikidot IDs, and SCUTTLE IDs.</td>
                                </tr>
                                <tr>
                                    <td>GET</td>
                                    <td><code>/page/{SCUTTLE ID}</code></td>
                                    <td>Full metadata for a page.</td>
                                </tr>
                                <tr>
                                    <td>GET</td>
                                    <td><code>/page/slug/{slug}</code></td>
                                    <td>Full metadata for a page, by page slug.</td>
                                </tr>
                                <tr>
                                    <td>GET</td>
                                    <td><code>/page/{SCUTTLE ID}/revisions</code></td>
                                    <td>All revisions for a page.</td>
                                </tr>
                                <tr>
                                    <td>POST</td>
                                    <td><code>/page/revisions</code></td>
                                    <td>Send 'id' (SCUTTLE ID), 'limit' (max 100), and 'offset', to receive paginated revisions for a page.</td>
                                </tr>
                                <tr>
                                    <td>GET</td>
                                    <td><code>/page/{SCUTTLE ID}/votes</code></td>
                                    <td>All votes for a page. Non-null <code>deleted_at</code> indicates the vote was
                                        removed. Status 3 is a deleted account, status 4 is a user that's no longer a
                                        member. (Status 3 and 4 not yet implemented.)</td>
                                </tr>
                            </tbody>
                        </table><br>
                    Standard user accounts have access to <code>read-metadata</code>, <code>read-article</code>, and
                        <code>read-votes</code> only and are rate-limited to 480 requests/min.
                        Contact bluesoul to request more permission.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
