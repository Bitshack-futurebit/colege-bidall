<x-app-layout>
    <x-slot name="title">Terms & Conditions - Admin</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">&larr; Back to Dashboard</a>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Terms & Conditions</h1>
            <a href="{{ route('admin.terms.create') }}" class="btn btn-primary">Create New Version</a>
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Version</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Title</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Applies To</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acceptances</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($terms as $version)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="font-mono font-bold text-primary-600 dark:text-primary-400">v{{ $version->version }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $version->title }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-block px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded">{{ $version->role_label }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($version->isPublished())
                                        <span class="inline-block px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">Published</span>
                                        <span class="text-xs text-gray-500 block">{{ $version->published_at->format('d M Y H:i') }}</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded">Draft</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $version->acceptances_count ?? $version->acceptances()->count() }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $version->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.terms.edit', $version) }}" class="text-sm text-primary-600 hover:text-primary-800">Edit</a>
                                        @if($version->isDraft())
                                            <form method="POST" action="{{ route('admin.terms.destroy', $version) }}" onsubmit="return confirm('Delete this draft?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    No terms versions yet. Create your first version to require acceptance during registration.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
