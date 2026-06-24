<x-app-layout>
    <x-slot name="title">Accept Terms & Conditions</x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card">
                <div class="p-8">
                    <div class="text-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Updated Terms & Conditions</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">
                            Please review and accept the following to continue using {{ config('branding.name') }}.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('terms.accept.store') }}">
                        @csrf

                        @foreach($terms as $index => $version)
                            <div class="mb-8">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                    {{ $version->title }}
                                    <span class="text-sm font-normal text-gray-500">(v{{ $version->version }} &middot; {{ $version->role_label }})</span>
                                </h2>

                                <div class="prose dark:prose-invert max-w-none max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-6 bg-gray-50 dark:bg-gray-900 mb-4">
                                    {!! $version->content !!}
                                </div>

                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" name="accept_terms[{{ $version->id }}]" value="1" class="mt-1 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        I have read and agree to the <strong>{{ $version->title }}</strong> (version {{ $version->version }})
                                    </span>
                                </label>
                                @error("accept_terms.{$version->id}")<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                        @endforeach

                        @error('accept_terms')<p class="text-red-500 text-sm mt-1 mb-4">{{ $message }}</p>@enderror

                        <button type="submit" class="btn btn-primary w-full">
                            Accept & Continue
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
