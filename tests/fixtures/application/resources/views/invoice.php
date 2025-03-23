@extends('app')

@section('title', $title)

@section('content')
    @can('create')
        <h1>You can create it</h1>
    @endcan
@endsection