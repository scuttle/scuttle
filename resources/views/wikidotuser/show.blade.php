@extends('layouts.app')

@section('title')
    Wikidot User {{$user->username}} ({{$user->wd_user_id}})
@endsection

@section('content')
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
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <h2>Recent Pages (<a href="{{request()->fullUrl()}}/pages">all</a>)</h2>
                                <ul>
                                    @foreach($pages as $page)
                                        <li>
                                            @if($page->trashed())
                                                <del>
                                            @endif
                                            <a href="{{request()->root()}}/{{$page->slug}}/milestone/{{$page->milestone}}">{{$page->metadata["wikidot_metadata"]["title_shown"] ?? $page->slug}}, Milestone {{$page->milestone}}</a>
                                            @if($page->trashed())
                                                </del>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <h2>Recent Revisions (<a href="{{request()->fullUrl()}}/revisions">all</a>)</h2>
                                <ul>
                                    @foreach($revisions as $revision)
                                        <li><a href="{{request()->root()}}/{{$revision->page->slug}}/milestone/{{$revision->page->milestone}}/revision/{{$revision->metadata["wikidot_metadata"]["revision_number"]}}">{{$revision->page_metadata["wikidot_metadata"]["title_shown"] ?? $revision->page->slug}}, Milestone {{$revision->page->milestone}}, Revision {{$revision->metadata["wikidot_metadata"]["revision_number"]}}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <h2>Recent Votes (<a href="{{request()->fullUrl()}}/votes">all</a>)</h2>
                                <ul>
                                    @foreach($votes as $vote)
                                        <li><a href="{{request()->root()}}/{{$vote->page->slug}}/milestone/{{$vote->page->milestone}}">{{$vote->page->metadata["wikidot_metadata"]["title_shown"] ?? $vote->page->slug}}, Milestone {{$vote->page->milestone}}</a>:
                                            @if($vote->vote > 0)
                                                +{{$vote->vote}}
                                            @else
                                                {{$vote->vote}}
                                            @endif
                                            , recorded {{$vote->created_at}}.
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
@endsection
