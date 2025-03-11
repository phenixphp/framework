@extends('app', ['key' => 'value'])

@section('title')
    {{ $title }}
@endsection

@section('content')
    <h1>Home</h1>
    <p>Welcome to the home page, list of colors:</p>
    <ul>
        @foreach($colors as $color)
            <li>{{ $color }}</li>
        @endforeach
    </ul>
@endsection
