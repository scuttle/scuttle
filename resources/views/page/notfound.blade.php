@extends('layouts.app')

@section('title')
    {{__('404')}}
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Page Not Found</div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif
                        A page with the slug {{$slug}} is not currently active for this SCUTTLE wiki.

                        @if($milestone != null)
                            <br><br>
                            However, one previously existed and you may see the most recently deleted version <a href="/{{$slug}}/milestone/{{$milestone}}">here</a>.
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
