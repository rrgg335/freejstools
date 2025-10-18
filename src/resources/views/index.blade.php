@extends('layout')
@section('post-title','- Tools for development, design')
@section('content')
    <div class="action-cards">
        @foreach($tools as $tool)
            <a href="{{ $tool['url'] }}" class="action-card">
                <h4 class="text-main">{{ $tool['title'] }}</h4>
            </a>
        @endforeach
    </div>
@endsection