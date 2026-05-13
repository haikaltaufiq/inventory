@extends('layouts.app')

@section('title', 'Manajemen Inventory')

@section('content')
    @include('products._inventory')
    @include('products._inventory_script')
@endsection
