@extends('layouts.app')

@section('title', 'Detail Keluarga')

@section('content')
    <livewire:admin.user-finances :family-id="$family->id" />
@endsection
