@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xs-12">
                <div class="card">
                    <div class="{{$page->slug}}">
                        <div class="card-header">
                            {{$metadata["title"]}}
                        </div>
                        <div class="card-body">
                            {!! nl2br($content) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
