@extends('layouts.app')
@section('title', 'Cetak Invoice')
@section('content')
    <livewire:invoice-print :id="$id" />
@endsection
