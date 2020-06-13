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

                    <div class="card-body" style="text-align: center">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif
                        <h3>{{_('404 | Not Found')}}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
