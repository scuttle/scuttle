@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xs-12">
                <div class="card">
                    <div class="{{$slug}}">
                        <div class="card-header">
                            <div class="d-flex justify-content-end">
                                <div class="mr-auto">{{$pagemetadata["title"]}}</div>
                                <div class="p-0">
                                    <i>
                                        @if($pagemetadata['rating'] > 0)
                                            +
                                        @endif
                                        {{$pagemetadata['rating']}}
                                    </i>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            {!! nl2br($revision->content) !!}
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-end">
                               <div class="mr-auto">Milestone {{$pagemetadata["milestone"]}}, Revision {{$revisionmetadata['wd_revision_id']}}</div>
                                <div class="p-0">Created by {{$pagemetadata["created_by"]["author"]}}, this revision by {{$revisionmetadata["display_author"]}}</div>
                            </div>
                            <br>
                            Revisions: &bull;
                            @foreach($pagemetadata["wd_scraped_revisions"] as $wd_scraped_revision)
                                @if($wd_scraped_revision == $revisionmetadata["wd_revision_id"])
                                    <i><b>{{$wd_scraped_revision}}</b></i> &bull;
                                @elseif(in_array($wd_scraped_revision,$sourcerevisions) == false)
                                    {{$wd_scraped_revision}} &bull;
                                @else
                                    @if($pagemetadata["milestone"] != $milestonecount-1)
                                    <a href="{{request()->root()}}/{{$slug}}/milestone/{{$pagemetadata["milestone"]}}/revision/{{$wd_scraped_revision}}">{{$wd_scraped_revision}}</a> &bull;
                                    @else
                                    <a href="{{request()->root()}}/{{$slug}}/revision/{{$wd_scraped_revision}}">{{$wd_scraped_revision}}</a> &bull;
                                    @endif
                                @endif
                            @endforeach
                            @if($milestonecount > 1)
                            <br>
                            Milestones: &bull;
                            @for($i = 0; $i < $milestonecount; $i++)
                                @if($i == $pagemetadata["milestone"])
                                    <i><b>{{$i}}</b></i> &bull;
                                @else
                                    <a href="{{request()->root()}}/{{$slug}}/milestone/{{$i}}/">{{$i}}</a> &bull;
                                @endif
                            @endfor
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
