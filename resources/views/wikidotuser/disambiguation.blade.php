@extends('layouts.app')

@section('title')
    Wikidot User {{$users[0]->username}} (Disambiguation)
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xs-12">
                <div class="card">
                    <div class="wdusername-disambiguation">
                        <div class="card-header">
                            <div class="d-flex justify-content-end">
                                <div class="mr-auto">{{$users[0]->username}}</div>
                                <div class="p-0">
                                    <!-- Upper Right -->
                                </div>
                            </div>
                        </div>
                        <div class="card-body" v-pre>
                            <div class="row">
                                <div class="col-sm-12">
                                    <h2>Found <b>{{$users->count()}}</b> users named {{$users[0]->username}}</h2>
                                    <ul>
                                        @foreach($users as $user)
                                            <li>
                                                @if($user->avatar_path)
                                                    <img src="{{$user->avatar_path}}">
                                                @else
                                                    <img src="https://cdn.scpfoundation.wiki/avatars/wikidot/default">
                                                @endif
                                                <a href="/user/{{$user->wd_user_id}}/{{$user->username}}">User ID {{$user->wd_user_id}}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
