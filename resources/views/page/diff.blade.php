@extends('layouts.app')

@section('title')
Diff View
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xs-12">
                <div class="card">
                    <div class="scuttle-diff">
                        <div class="card-header">
                            <div class="d-flex justify-content-end">
                                <div class="mr-auto">Diff View</div>
                            </div>
                        </div>
                        <div class="card-body" v-pre>
                            {!! $diff !!}
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-end">
                            </div>
                            <br>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
