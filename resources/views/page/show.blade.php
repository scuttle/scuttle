@extends('layouts.app')

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
                                <div class="p-0">Created by {{$metadata['wikidot_metadata']["created_by"]}}, this revision by {{$metadata["wikidot_metadata"]["updated_by"]}}</div>
                            </div>
                            <br>
                            Revisions: &bull;
                            @for($i = 0; $i < $page->revisions()->count(); $i++)
                                @if($i == ($metadata["wikidot_metadata"]["revisions"] - 1))
                                    <i><b>{{$i}}</b></i> &bull;
                                @else
                                    @if($page->milestone != $milestones)
                                    <a href="{{request()->root()}}/{{$page->slug}}/milestone/{{$page->milestone}}/revision/{{$i}}">{{$i}}</a> &bull;
                                    @else
                                    <a href="{{request()->root()}}/{{$page->slug}}/revision/{{$wd_scraped_revision->metadata->revision_number}}">{{$i}}</a> &bull;
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
                        <div class="card-footer"><strong>Nerd Stuff</strong>
                            <hr>
                            Page ID: <pre>{{$page->wd_page_id}}</pre>
                            Page Revisions: <pre>{{$page->revisions()->pluck('wd_revision_id')->reverse()->values()}}</pre>
                            Metadata: <pre>{{print_r($metadata)}}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
