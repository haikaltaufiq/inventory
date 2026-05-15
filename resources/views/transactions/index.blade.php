@extends('layouts.app')

@section('title', 'Enterprise POS - PC Builder Edition')

@section('content')
<div class="px-1 pb-6 sm:px-2 lg:px-3" x-data="posSystem()">

    @include('transactions.partials._modals')

    {{-- MAIN INTERFACE --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-start lg:gap-5 xl:gap-6">
        <div class="flex-1 min-w-0">
            @include('transactions.partials._topbar')
            @include('transactions.partials._product_grid')
        </div>

        @include('transactions.partials._cart')
    </div>
</div>

@include('transactions.partials._script')

@include('transactions.partials._modal_payment')

@endsection
