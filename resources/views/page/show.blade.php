@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xs-12">
                <div class="card">
                    <div class="card-header">{{$metadata["title"]}}</div>

                    <div class="card-body">
                        {!! nl2br(e($latestrevision->content)) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
