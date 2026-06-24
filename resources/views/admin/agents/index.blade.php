<x-app-layout>
    <x-slot name="title">Agents</x-slot>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Community Agents</h1>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif

        {{-- Status filter tabs --}}
        <div class="flex gap-1 mb-5 border-b border-gray-200 dark:border-gray-700">
            @foreach(['pending', 'active', 'suspended', 'terminated'] as $s)
                <a href="{{ route('admin.agents.index', ['status' => $s]) }}"
                   class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $status === $s ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-600 hover:text-primary-600' }}">
                    {{ ucfirst($s) }}
                    <span class="ml-1 text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">{{ $counts[$s] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        @if($agents->count() === 0)
            <div class="card p-8 text-center text-gray-500">
                No {{ $status }} agents.
            </div>
        @else
            <div class="space-y-2">
                @foreach($agents as $agent)
                    <a href="{{ route('admin.agents.show', $agent) }}"
                       class="card p-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        @if($agent->photo)
                            <img src="{{ Storage::url($agent->photo) }}" class="w-12 h-12 rounded-full object-cover">
                        @else
                            <div class="w-12 h-12 bg-teal-100 dark:bg-teal-900 rounded-full flex items-center justify-center font-bold text-teal-700 dark:text-teal-300">
                                {{ substr($agent->user->name, 0, 1) }}
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <div class="font-semibold truncate">{{ $agent->user->name }}</div>
                            <div class="text-xs text-gray-500 truncate">
                                {{ $agent->whatsapp_group_name }} · {{ $agent->whatsapp_group_size_claim }} members
                                · Submitted {{ $agent->created_at->diffForHumans() }}
                            </div>
                        </div>

                        @if($agent->communities->isNotEmpty())
                            <div class="text-xs text-gray-500 hidden md:block">
                                {{ $agent->communities->count() }} {{ Str::plural('community', $agent->communities->count()) }}
                            </div>
                        @endif

                        <span class="text-gray-400">→</span>
                    </a>
                @endforeach
            </div>

            <div class="mt-4">{{ $agents->links() }}</div>
        @endif
    </div>
</x-app-layout>
