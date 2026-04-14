@extends('layouts.app')

@section('title', 'Enterprise POS - PC Builder Edition')

@section('content')
<div class="px-5 pb-10" x-data="posSystem()">

    @include('transactions.partials._modals')

    {{-- MAIN INTERFACE --}}
    <div class="flex gap-8 items-start">
        <div class="flex-1 min-w-0">
            @include('transactions.partials._topbar')
            @include('transactions.partials._product_grid')
        </div>

        @include('transactions.partials._cart')
    </div>
</div>

@include('transactions.partials._script')

@endsection