@extends('layouts.app')

@section('title')
    Wikidot User {{$user->username}} ({{$user->wd_user_id}}) - Votes
@endsection

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.3/js/jquery.tablesorter.combined.min.js"></script>
<script type="text/javascript">
    $(function() {
        $("#votesTable").tablesorter();
    });
</script>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xs-12">
                <div class="card">
                    <div class="wduser-{{$user->wd_user_id}}">
                        <div class="card-header">
                            <div class="d-flex justify-content-end">
                                <div class="mr-auto">{{$user->username}}</div>
                                <div class="p-0">
                                    <!-- Upper Right -->
                                </div>
                            </div>
                        </div>
                        <div class="card-body" v-pre>
                            <div class="row">
                                <div class="col-sm-12">
                                    <h2>All Votes</h2>
                                    <table id="votesTable" class="tablesorter">
                                        <thead>
                                            <tr>
                                                <th>Page</th>
                                                <th>Author</th>
                                                <th>Vote</th>
                                                <th>Recorded At</th>
                                            </tr>
                                        </thead>
                                        @foreach($votes as $vote)
                                            <tr>
                                                <td><a href="{{request()->root()}}/{{$vote->page->slug}}/milestone/{{$vote->page->milestone}}">{{$vote->page->metadata["wikidot_metadata"]["title_shown"] ?? $vote->page->slug}}, Milestone {{$vote->page->milestone}}</a></td>
                                                <td>{{$vote->page->metadata["wikidot_metadata"]["created_by"]}}</td>
                                                <td>
                                                    @if($vote->vote > 0)
                                                        +{{$vote->vote}}
                                                    @else
                                                        {{$vote->vote}}
                                                    @endif
                                                </td>
                                                <td>{{$vote->created_at}}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                    <hr>
                                    <div class="d-flex">
                                        <div class="mx-auto">
                                            {{ $votes->links() }}
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
