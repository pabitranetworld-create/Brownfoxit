@extends('layouts.app')

@php(seo()->subject('home'))
@section('body_class', 'page-home')

@section('content')
    {{-- Each section lives in its own file in resources/views/home/sections --}}
    @include('home.sections.hero')
    @include('home.sections.service-boxes')
    @include('home.sections.about')
    @include('home.sections.clients')
    @include('home.sections.service-cards')
    @include('home.sections.marketplace')
    @include('home.sections.counters')
    @include('home.sections.projects')
    @include('home.sections.testimonials')
    @include('home.sections.team')
    @include('home.sections.steps')
    @include('home.sections.cta')
    @include('home.sections.blog')
@endsection
