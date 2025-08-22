@extends('app')

@section('title', $title)

@section('content')
    @include('partials.form', ['token' => $token])
@endsection
