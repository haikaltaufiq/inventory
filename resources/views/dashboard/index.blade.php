@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="px-5">
    <h1 class="text-2xl font-semibold mb-6 tracking-tight">Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm md:col-span-3">
            <h2 class="text-lg tracking-tight font-medium text-gray-600 mb-4">
                Sales Overview
            </h2>

            <x-chart
                id="salesChart1"
                :series="[
                ['name' => 'Sales', 'data' => [10, 20, 35, 50, 49, 60]]
            ]"
                :categories="['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']" />
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm md:col-span-1">
            <h2 class="text-lg tracking-tight font-medium text-gray-600 mb-4">
                Inventory Overview
            </h2>

            <x-pie-chart
                id="stockChart"
                :series="[44, 55, 13, 43]"
                :labels="['Laptop', 'Keyboard', 'Mouse', 'Monitor']" />
        </div>
    </div>

    {{-- SECTION: KPI SUMMARY --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-gray-500">Total Transactions</p>
            <h3 class="text-2xl font-semibold mt-1">128</h3>
            <span class="text-xs text-green-500">+12% this month</span>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-gray-500">Total Revenue</p>
            <h3 class="text-2xl font-semibold mt-1">Rp 82.400.000</h3>
            <span class="text-xs text-green-500">+8% this month</span>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-gray-500">Total Products</p>
            <h3 class="text-2xl font-semibold mt-1">342</h3>
            <span class="text-xs text-gray-400">Active items</span>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-gray-500">Low Stock</p>
            <h3 class="text-2xl font-semibold mt-1 text-red-500">8</h3>
            <span class="text-xs text-red-400">Needs restock</span>
        </div>
    </div>

    {{-- SECTION: RECENT TRANSACTIONS --}}
    <div class="bg-white rounded-2xl p-6 shadow-sm mt-8 mb-8">
        <h2 class="text-lg tracking-tight font-medium text-gray-600 mb-4">
            Recent Transactions
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-gray-400 border-b">
                    <tr>
                        <th class="text-left py-3">Invoice</th>
                        <th class="text-left py-3">Customer</th>
                        <th class="text-left py-3">Date</th>
                        <th class="text-right py-3">Total</th>
                        <th class="text-center py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <tr class="border-b last:border-0">
                        <td class="py-3">TRX-00124</td>
                        <td>Andi Wijaya</td>
                        <td>12 Jan 2026</td>
                        <td class="text-right">Rp 2.400.000</td>
                        <td class="text-center">
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-600">
                                Paid
                            </span>
                        </td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <td class="py-3">TRX-00125</td>
                        <td>Budi Santoso</td>
                        <td>12 Jan 2026</td>
                        <td class="text-right">Rp 1.150.000</td>
                        <td class="text-center">
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-600">
                                Pending
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="py-3">TRX-00126</td>
                        <td>Siti Rahma</td>
                        <td>13 Jan 2026</td>
                        <td class="text-right">Rp 3.800.000</td>
                        <td class="text-center">
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-600">
                                Paid
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection