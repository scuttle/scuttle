@extends('layouts.app')

@section('title')
    Wikidot User {{$user->username}} ({{$user->wd_user_id}}) - Pages
@endsection

@section('content')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.3/js/jquery.tablesorter.combined.min.js"></script>
    <script type="text/javascript">
        $(function() {
            $("#pagesTable").tablesorter();
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
                                    <h2>All Pages</h2>
                                    <table id="pagesTable" class="tablesorter">
                                        <thead>
                                        <tr>
                                            <th>Page</th>
                                            <th>Rating</th>
                                            <th>Recorded At</th>
                                        </tr>
                                        </thead>
                                        @foreach($pages as $page)
                                            <tr>
                                                <td><a href="{{request()->root()}}/{{$page->slug}}/milestone/{{$page->milestone}}">{{$page->metadata["wikidot_metadata"]["title_shown"] ?? $page->slug}}, Milestone {{$page->milestone}}</a></td>
                                                <td>{{$page->votes->sum('vote')}}</td>
                                                <td>{{$page->created_at}}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                    <hr>
                                    <div class="d-flex">
                                        <div class="mx-auto">
                                            {{ $pages->links() }}
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
