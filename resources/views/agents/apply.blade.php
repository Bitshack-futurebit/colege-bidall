<x-app-layout>
    <x-slot name="title">Become a Community Agent</x-slot>

    <div class="max-w-2xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-2">Become a Community Agent</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            Help grow a local community auction. Earn a share of platform commission on every sale.
        </p>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 rounded">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 rounded">
                {{ session('error') }}
            </div>
        @endif

        @if($agent)
            {{-- Status display --}}
            @php
                $statusStyles = [
                    'pending'    => ['amber', 'Application under review', 'We\'ll be in touch shortly. You\'ll get an in-app notification when a decision is made.'],
                    'active'     => ['green', 'Active agent', 'You\'re all set. Visit your agent dashboard to track earnings and your referral link.'],
                    'suspended'  => ['red', 'Suspended', $agent->suspension_reason ?: 'Contact platform support.'],
                    'terminated' => ['gray', 'Application not approved', $agent->suspension_reason ?: 'No further details.'],
                ];
                [$color, $label, $detail] = $statusStyles[$agent->status] ?? ['gray', 'Unknown', ''];
            @endphp
            <div class="card p-6">
                <span class="inline-block px-2 py-0.5 text-xs bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-300 rounded uppercase tracking-wide">
                    {{ strtoupper($agent->status) }}
                </span>
                <h2 class="text-xl font-bold mt-2 mb-1">{{ $label }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $detail }}</p>

                <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Submitted</dt>
                        <dd class="font-semibold">{{ $agent->created_at->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Group claimed</dt>
                        <dd class="font-semibold">{{ $agent->whatsapp_group_name }} ({{ $agent->whatsapp_group_size_claim }} members)</dd>
                    </div>
                    @if($agent->approved_at)
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Approved</dt>
                        <dd class="font-semibold">{{ $agent->approved_at->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Referral code</dt>
                        <dd class="font-mono font-bold tracking-wider">{{ $agent->referral_code }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        @else
            {{-- Application form --}}
            <div class="card p-4 mb-5 bg-teal-50 dark:bg-teal-900/20 border-l-4 border-teal-500">
                <h3 class="font-bold mb-2">What you need to qualify</h3>
                <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-disc ml-5">
                    <li>An active local buy/sell WhatsApp group with <strong>50+ members</strong></li>
                    <li>You're the admin or a trusted member of that group</li>
                    <li>You're willing to promote a community auction in your area</li>
                </ul>
            </div>

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 rounded text-sm">
                    <ul class="list-disc ml-5">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('agent.apply.store') }}" enctype="multipart/form-data" class="card p-6 space-y-4">
                @csrf

                <div>
                    <label class="label">WhatsApp group name <span class="text-red-500">*</span></label>
                    <input type="text" name="whatsapp_group_name" value="{{ old('whatsapp_group_name') }}" required maxlength="120" class="input">
                </div>

                <div>
                    <label class="label">Number of members in group <span class="text-red-500">*</span></label>
                    <input type="number" name="whatsapp_group_size_claim" value="{{ old('whatsapp_group_size_claim') }}" required min="50" max="5000" class="input">
                    <p class="text-xs text-gray-500 mt-1">Minimum 50.</p>
                </div>

                <div>
                    <label class="label">Proof screenshot <span class="text-red-500">*</span></label>
                    <input type="file" name="whatsapp_group_proof" accept="image/*" required class="input">
                    <p class="text-xs text-gray-500 mt-1">Screenshot of your group info screen showing the member count. Max 8MB.</p>
                </div>

                <div>
                    <label class="label">Your WhatsApp number <span class="text-red-500">*</span></label>
                    <input type="tel" name="public_whatsapp_number" value="{{ old('public_whatsapp_number') }}" required maxlength="20" class="input" placeholder="+27 82 123 4567">
                    <p class="text-xs text-gray-500 mt-1">Shown publicly on the community page so members can reach you.</p>
                </div>

                <div>
                    <label class="label">Short bio (optional)</label>
                    <textarea name="bio" rows="3" maxlength="1000" class="input" placeholder="Tell potential members why they should trust you with their local community auction.">{{ old('bio') }}</textarea>
                </div>

                <div>
                    <label class="label">Profile photo (optional)</label>
                    <input type="file" name="photo" accept="image/*" class="input">
                    <p class="text-xs text-gray-500 mt-1">Square works best. Max 4MB.</p>
                </div>

                <button type="submit" class="btn btn-primary w-full">Submit application</button>
            </form>
        @endif
    </div>
</x-app-layout>
