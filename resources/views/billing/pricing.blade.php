@auth
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Pricing') }}
            </h2>
        </x-slot>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                @include('billing.partials.pricing-cards')
            </div>
        </div>
    </x-app-layout>
@else
    @extends('pages.layout')

    @section('title', 'Pricing')

    @section('page-content')
        @include('billing.partials.pricing-cards')
    @endsection
@endauth
