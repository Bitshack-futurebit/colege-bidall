<x-app-layout>
    <x-slot name="title">Edit Terms v{{ $term->version }} - Admin</x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="{{ route('admin.terms.index') }}" class="btn btn-outline">&larr; Back to Terms</a>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Edit Terms v{{ $term->version }}</h1>
            @if($term->isPublished())
                <span class="inline-block px-3 py-1 text-sm bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full">
                    Published {{ $term->published_at->format('d M Y') }} &middot; {{ $term->acceptances()->count() }} acceptances
                </span>
            @else
                <span class="inline-block px-3 py-1 text-sm bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full">Draft</span>
            @endif
        </div>

        <div class="card">
            <div class="p-6">
                <form method="POST" action="{{ route('admin.terms.update', $term) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="version" class="label">Version Number</label>
                        <input id="version" type="text" name="version" value="{{ old('version', $term->version) }}" required class="input @error('version') input-error @enderror">
                        @error('version')<p class="error-message">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="role" class="label">Applies To</label>
                        <select id="role" name="role" class="input @error('role') input-error @enderror">
                            <option value="">All Users</option>
                            <option value="bidder" {{ old('role', $term->role) === 'bidder' ? 'selected' : '' }}>Bidders Only</option>
                            <option value="auctioneer" {{ old('role', $term->role) === 'auctioneer' ? 'selected' : '' }}>Auctioneers Only</option>
                        </select>
                        @error('role')<p class="error-message">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="title" class="label">Title</label>
                        <input id="title" type="text" name="title" value="{{ old('title', $term->title) }}" required class="input @error('title') input-error @enderror">
                        @error('title')<p class="error-message">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-6">
                        <label for="content" class="label">Content (HTML allowed)</label>
                        <textarea id="content" name="content" rows="20" required class="input font-mono text-sm @error('content') input-error @enderror">{{ old('content', $term->content) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">You can use HTML tags for formatting (h2, p, ul, li, strong, etc.)</p>
                        @error('content')<p class="error-message">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        @if($term->isDraft())
                            <button type="submit" name="publish" value="1" class="btn btn-outline">Save & Publish</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
