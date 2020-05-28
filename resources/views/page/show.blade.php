@extends('layouts.app')

@section('title')
{{$page->slug}} ({{$metadata["wikidot_metadata"]["title_shown"]}}), by <a href="/user/{{$metadata['wikidot_metadata']["created_by"]}}" target="_top">{{$metadata['wikidot_metadata']["created_by"]}}</a>.
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xs-12">
                <div class="card">
                    <div class="{{$page->slug}}">
                        <div class="card-header">
                            <div class="d-flex justify-content-end">
                                <div class="mr-auto">{{$metadata["wikidot_metadata"]["title_shown"]}}</div>
                                <div class="p-0">
                                    <i>
                                        @if($metadata['wikidot_metadata']['rating'] > 0)
                                            +
                                        @endif
                                        {{$metadata['wikidot_metadata']['rating']}}
                                    </i>
                                </div>
                            </div>
                        </div>
                        <div class="card-body" v-pre>
                            {!! nl2br($page->latest_revision) !!}
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-end">
                               <div class="mr-auto">Milestone {{$page->milestone}}</div>
                                <div class="p-0">Created by <a href="/user/{{$metadata['wikidot_metadata']["created_by"]}}" target="_top">{{$metadata['wikidot_metadata']["created_by"]}}</a>, this revision by <a href="/user/{{$metadata["wikidot_metadata"]["updated_by"]}}" target="_top">{{$metadata["wikidot_metadata"]["updated_by"]}}</a>.</div>
                            </div>
                            <br>
                            @if($slug_milestones->count() > 1)
                                Milestones: &bull;
                                @foreach($slug_milestones as $milestone)
                                    @if($milestone->milestone == $page->milestone)
                                        <i><b>{{$milestone->milestone}}</b></i> &bull;
                                    @else
                                        <a href="{{request()->root()}}/{{$page->slug}}/milestone/{{$milestone->milestone}}/">{{$milestone->milestone}}</a> &bull;
                                    @endif
                                @endforeach
                                <br>
                            @endif
                            Revisions: &bull;
                            @for($i = 0; $i < $page->revisions()->count(); $i++)
                                @if($i == ($metadata["wikidot_metadata"]["revisions"] - 1))
                                    <i><b>{{$i}}</b></i> &bull;
                                @else
                                    <a href="{{request()->root()}}/{{$page->slug}}/milestone/{{$page->milestone}}/revision/{{$i}}">{{$i}}</a> &bull;
                                @endif
                            @endfor
                        </div>
                        <div class="card-footer"><strong>Nerd Stuff</strong>
                            <hr>
                            SCUTTLE ID: <pre>{{$page->id}}</pre>
                            Page ID: <pre>{{$page->wd_page_id}}</pre>
                            Page Revisions: <span style="word-wrap: anywhere">{{$page->revisions()->pluck('wd_revision_id')->reverse()->values()}}</span>
                            <br><br>
                            Metadata: <pre>{{print_r($metadata)}}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
