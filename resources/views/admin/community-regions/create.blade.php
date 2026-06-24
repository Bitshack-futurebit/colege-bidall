<x-app-layout>
    <x-slot name="title">New Community Region</x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="{{ route('admin.community-regions.index') }}" class="btn btn-outline">&larr; Back</a>
        </div>

        <h1 class="text-2xl font-bold mb-6">New Community Region</h1>

        <form method="POST" action="{{ route('admin.community-regions.store') }}" class="card p-6">
            @include('admin.community-regions._form', ['region' => null])

            <div class="mt-6 flex gap-2">
                <button type="submit" class="btn btn-primary">Create Region</button>
                <a href="{{ route('admin.community-regions.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
