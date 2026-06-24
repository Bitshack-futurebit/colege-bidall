<x-app-layout>
    <x-slot name="title">Terms of Service</x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(count($termsVersions) > 0)
                @foreach($termsVersions as $terms)
                <div class="card mb-8">
                    <div class="p-8">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ $terms->title }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">
                            Version {{ $terms->version }}
                            &middot; {{ $terms->role_label }}
                            &middot; Last updated: {{ $terms->published_at->format('F d, Y') }}
                        </p>

                        <div class="prose dark:prose-invert max-w-none">
                            {!! $terms->content !!}
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                <div class="card">
                    <div class="p-8">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Terms of Service</h1>

                        <div class="prose dark:prose-invert max-w-none">
                            <h2>1. Acceptance of Terms</h2>
                            <p>By accessing and using {{ config('branding.name') }}, you accept and agree to be bound by the terms and provision of this agreement.</p>

                            <h2>2. Use License</h2>
                            <p>Permission is granted to temporarily access the services on {{ config('branding.name') }} for personal, non-commercial transitory viewing only.</p>

                            <h2>3. Contact Us</h2>
                            <p>If you have any questions about these Terms, please join our support group:</p>
                            @if(config('regional.whatsapp.support_group_url'))
                            <p class="mt-3">
                                <a href="{{ config('regional.whatsapp.support_group_url') }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#128C7E] text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                    WhatsApp Support Group
                                </a>
                            </p>
                            @endif

                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-8">
                                Last Updated: {{ date('F d, Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
