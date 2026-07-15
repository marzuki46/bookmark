@extends('layouts.app')
@section('title', 'Edit Invoice')
@section('content')
    <livewire:invoice-form :id="$id" />
@endsection
