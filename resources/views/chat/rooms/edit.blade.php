@extends('layouts.app')

@section('content')
    @include('chat.rooms.form', ['room' => $room])
@endsection
