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
                                            <a href="{{request()->root()}}/{{$page->milestones[0]->slug}}/milestone/{{$page->milestones[0]->milestone}}">{{$page->metadata["wikidot_metadata"]["title_shown"] ?? $page->milestones[0]->slug}}, Milestone {{$page->milestones[0]->milestone}}</a>
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
                                        <li><a href="{{request()->root()}}/{{$revision->page->milestones[0]->slug}}/milestone/{{$revision->page->milestones[0]->milestone}}/revision/{{$revision->metadata["wikidot_metadata"]["revision_number"]}}">{{$revision->page_metadata["wikidot_metadata"]["title_shown"] ?? $revision->page->milestones[0]->slug}}, Milestone {{$revision->page->milestones[0]->milestone}}, Revision {{$revision->metadata["wikidot_metadata"]["revision_number"]}}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <h2>Recent Votes (<a href="{{request()->fullUrl()}}/votes">all</a>)</h2>
                                <ul>
                                    @foreach($votes as $vote)
                                        <li><a href="{{request()->root()}}/{{$vote->page->milestones[0]->slug}}/milestone/{{$vote->page->milestones[0]->milestone}}">{{$vote->page->metadata["wikidot_metadata"]["title_shown"] ?? $vote->page->milestones[0]->slug}}, Milestone {{$vote->page->milestones[0]->milestone}}</a>:
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
