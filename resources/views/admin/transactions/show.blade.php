<x-app-layout>
    <x-slot name="title">Transaction Details - Admin</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline">← Back</a>
        </div>

        <div class="card p-6">
            <h1 class="text-2xl font-bold mb-6">Transaction #{{ $transaction->id }}</h1>
            
            <div class="grid grid-cols-2 gap-4">
                <div><strong>Type:</strong> {{ $transaction->type }}</div>
                <div><strong>Amount:</strong> {{ formatCurrency($transaction->amount) }}</div>
                <div><strong>Platform Fee:</strong> {{ formatCurrency($transaction->platform_fee) }}</div>
                <div><strong>Status:</strong> {{ $transaction->status }}</div>
                <div><strong>Date:</strong> {{ $transaction->created_at->format('M d, Y H:i') }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
