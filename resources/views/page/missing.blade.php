@extends('layouts.app')

@section('title')
    {{$page->slug}}, Milestone {{$page->milestone}}.
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xs-12">
                <div class="card">
                    <div class="{{$page->slug}}">
                        <div class="card-header">
                            <div class="d-flex justify-content-end">
                                <div class="mr-auto">{{$page->slug}}</div>
                                <div class="p-0">
                                    <i>
                                        Page Missing
                                    </i>
                                </div>
                            </div>
                        </div>
                        <div class="card-body" v-pre>
                            {{$page->slug}}, Milestone {{$page->milestone}} was recorded as existing, but the article was moved or deleted before the content or revisions could be archived. Sorry about that.
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-end">
                                <div class="mr-auto">Milestone {{$page->milestone}}</div>
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
                        </div>
                        <div class="card-footer"><strong>Nerd Stuff</strong>
                            <hr>
                            SCUTTLE ID: <pre>{{$page->id}}</pre>
                            <br>
                            Metadata: <pre>{{print_r($page->metadata)}}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
