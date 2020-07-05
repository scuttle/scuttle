@extends('layouts.app')

@section('title')
    Wikidot User {{$user->username}} ({{$user->wd_user_id}}) - Revisions
@endsection

@section('content')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.3/js/jquery.tablesorter.combined.min.js"></script>
    <script type="text/javascript">
        $(function() {
            $("#revsTable").tablesorter();
        });
    </script>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xs-12">
                <div class="card">
                    <div class="wduser-{{$user->wd_user_id}}">
                        <div class="card-header">
                            <div class="d-flex justify-content-end">
                                <div class="mr-auto"><a href="/user/{{$user->wd_user_id}}/{{$user->username}}">{{$user->username}}</a></div>
                                <div class="p-0">
                                    <!-- Upper Right -->
                                </div>
                            </div>
                        </div>
                        <div class="card-body" v-pre>
                            <div class="row">
                                <div class="col-sm-12">
                                    <h2>All Revisions</h2>
                                    <table id="revsTable" class="tablesorter">
                                        <thead>
                                        <tr>
                                            <th>Revision</th>
                                            <th>Comment</th>
                                            <th>Recorded At</th>
                                        </tr>
                                        </thead>
                                        @foreach($revisions as $revision)
                                            <tr>
                                                <td><a href="{{request()->root()}}/{{$revision->page->slug}}/milestone/{{$revision->page->milestone}}/revision/{{$revision->metadata["wikidot_metadata"]["revision_number"]}}">{{$revision->page->metadata["wikidot_metadata"]["title_shown"] ?? $revision->page->slug}}, Milestone {{$revision->page->milestone}}, Revision {{$revision->metadata["wikidot_metadata"]["revision_number"]}}</a></td>
                                                <td>{{$revision->metadata["wikidot_metadata"]["comments"]}}</td>
                                                <td>{{$revision->created_at}}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                    <hr>
                                    <div class="d-flex">
                                        <div class="mx-auto">
                                            {{ $revisions->links() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
