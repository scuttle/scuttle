@extends('layouts.app')

@section('title')
Provisional Entry: {{$slug}} ({{$pagemetadata["wikidot_metadata"]["title"]}}), by {{$metadata['wikidot_metadata']["created_by"]}}
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xs-12">
                <div class="card">
                    <div class="{{$slug}}">
                        <div class="card-header">
                            <div class="d-flex justify-content-end">
                                <div class="mr-auto">{{$pagemetadata["wikidot_metadata"]["title"]}}</div>
                                <div class="p-0">
                                    <i>
                                        @if($pagemetadata["wikidot_metadata"]['rating'] > 0)
                                            +
                                        @endif
                                        {{$pagemetadata["wikidot_metadata"]['rating']}}
                                    </i>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            {!! nl2br($revision->content) !!}
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-end">
                                <div class="mr-auto">Milestone {{$page->milestone}}, Latest Revision</div>
                                <div class="p-0">Created by {{$pagemetadata["wikidot_metadata"]["created_by"]}}, this revision by {{$pagemetadata["wikidot_metadata"]["updated_by"]}}</div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <div class="mr-auto">This is a stubbed entry. SCUTTLE is awaiting additional metadata.</div>
                            </div>
                            <br>
                            @if($milestonecount > 1)
                                <br>
                                Milestones: &bull;
                                @for($i = 0; $i < $milestonecount; $i++)
                                    @if($i == $page->milestone)
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
