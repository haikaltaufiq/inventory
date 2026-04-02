@extends('layouts.app')

@section('title', 'Manajemen Inventory')

@section('content')
    @include('products._grid')
    @include('products._grid_script')
@endsection
