@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xs-12">
                <div class="card">
                    <div class="{{$page->slug}}">
                        <div class="card-header">
                            <div class="d-flex justify-content-end">
                                <div class="mr-auto">{{$pagemetadata["wikidot_metadata"]["title_shown"]}}</div>
                                <div class="p-0">
                                    <i>
                                        @if($pagemetadata['wikidot_metadata']['rating'] > 0)
                                            +
                                        @endif
                                        {{$pagemetadata['wikidot_metadata']['rating']}}
                                    </i>
                                </div>
                            </div>
                        </div>
                        <div class="card-body" v-pre>
                            {!! $revision->content !!}
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-end">
                               <div class="mr-auto">Milestone {{$page->milestone}}</div>
                                <div class="p-0">Created by {{$pagemetadata['wikidot_metadata']["created_by"]}}, this revision by {{$revisionmetadata["wikidot_metadata"]["username"]}}</div>
                                <div class="p-0"><i>{{$revisionmetadata['wikidot_metadata']['comments']}}</i></div>
                            </div>
                            <br>
                            Revisions: &bull;
                            @for($i = 0; $i <= $pagemetadata["wikidot_metadata"]["revisions"]; $i++)
                                @if($i == ($revisionmetadata["wikidot_metadata"]["revision_number"]))
                                    <i><b>{{$i}}</b></i> &bull;
                                @else
                                    @if($page->milestone != $milestones-1)
                                    <a href="{{request()->root()}}/{{$page->slug}}/milestone/{{$page->milestone}}/revision/{{$i}}">{{$i}}</a> &bull;
                                    @else
                                    <a href="{{request()->root()}}/{{$page->slug}}/revision/{{$i}}">{{$i}}</a> &bull;
                                    @endif
                                @endif
                            @endfor
                            @if($milestones > 1)
                            <br>
                            Milestones: &bull;
                            @for($i = 0; $i < $milestones; $i++)
                                @if($i == $page->milestone)
                                    <i><b>{{$i}}</b></i> &bull;
                                @else
                                    <a href="{{request()->root()}}/{{$page->slug}}/milestone/{{$i}}/">{{$i}}</a> &bull;
                                @endif
                            @endfor
                            @endif
                        </div>
                        <div class="card-footer">
                            <strong>Nerd Stuff</strong>
                            <hr>
                            Page ID: <pre>{{$page->wd_page_id}}</pre>
                            Page Revisions: <span style="word-wrap: anywhere">{{$page->revisions()->pluck('wd_revision_id')->reverse()->values()}}</span>
                            <br><br>
                            Page Metadata: <pre>{{print_r($pagemetadata)}}</pre>
                            Revision Metadata: <pre>{{print_r($revisionmetadata)}}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
