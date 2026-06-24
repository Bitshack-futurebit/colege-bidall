<x-app-layout>
    <x-slot name="title">Create Terms Version - Admin</x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="{{ route('admin.terms.index') }}" class="btn btn-outline">&larr; Back to Terms</a>
        </div>

        <h1 class="text-2xl font-bold mb-6">Create New Terms Version</h1>

        <div class="card">
            <div class="p-6">
                <form method="POST" action="{{ route('admin.terms.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="version" class="label">Version Number</label>
                        <input id="version" type="text" name="version" value="{{ old('version') }}" required class="input @error('version') input-error @enderror" placeholder="e.g. 1.0, 2.0">
                        @error('version')<p class="error-message">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="role" class="label">Applies To</label>
                        <select id="role" name="role" class="input @error('role') input-error @enderror">
                            <option value="">All Users</option>
                            <option value="bidder" {{ old('role') === 'bidder' ? 'selected' : '' }}>Bidders Only</option>
                            <option value="auctioneer" {{ old('role') === 'auctioneer' ? 'selected' : '' }}>Auctioneers Only</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Role-specific terms are shown in addition to general terms</p>
                        @error('role')<p class="error-message">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="title" class="label">Title</label>
                        <input id="title" type="text" name="title" value="{{ old('title', 'Terms & Conditions') }}" required class="input @error('title') input-error @enderror">
                        @error('title')<p class="error-message">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-6">
                        <label for="content" class="label">Content (HTML allowed)</label>
                        <textarea id="content" name="content" rows="20" required class="input font-mono text-sm @error('content') input-error @enderror" placeholder="<h2>1. Acceptance of Terms</h2>
<p>By accessing and using...</p>">{{ old('content') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">You can use HTML tags for formatting (h2, p, ul, li, strong, etc.)</p>
                        @error('content')<p class="error-message">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" name="publish" value="1" class="btn btn-primary">Save & Publish</button>
                        <button type="submit" class="btn btn-outline">Save as Draft</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
