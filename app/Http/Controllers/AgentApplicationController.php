<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AgentApplicationController extends Controller
{
    /**
     * Show the application form, or the current status if the user has
     * already applied.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        abort_if($user->isAdmin() || $user->isAuctioneer() || $user->isStaff(), 403,
            'Auctioneers, staff and admins cannot also be community agents.');

        $agent = $user->agent;

        return view('agents.apply', [
            'agent' => $agent,
        ]);
    }

    /**
     * Submit a new application. Idempotent — if one already exists, edits the
     * pending record (you can re-upload the proof if rejected? no — keep it
     * simple, only allow create if there's no existing record).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        abort_if($user->isAdmin() || $user->isAuctioneer() || $user->isStaff(), 403);
        abort_if($user->agent !== null, 409, 'You have already applied.');

        $validated = $request->validate([
            'whatsapp_group_name' => ['required', 'string', 'max:120'],
            'whatsapp_group_size_claim' => ['required', 'integer', 'min:50', 'max:5000'],
            'whatsapp_group_proof' => ['required', 'image', 'mimes:jpeg,png,webp,heic', 'max:8192'],
            'public_whatsapp_number' => ['required', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
        ], [
            'whatsapp_group_size_claim.min' => 'Your WhatsApp group must have at least 50 members.',
        ]);

        $proofPath = $request->file('whatsapp_group_proof')
            ->store('agents/proof', 'public');

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('agents/photos', 'public');
        }

        Agent::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'whatsapp_group_name' => $validated['whatsapp_group_name'],
            'whatsapp_group_size_claim' => $validated['whatsapp_group_size_claim'],
            'whatsapp_group_proof_path' => $proofPath,
            'public_whatsapp_number' => $validated['public_whatsapp_number'],
            'bio' => $validated['bio'] ?? null,
            'photo' => $photoPath,
            'referral_code' => Agent::generateReferralCode(),
        ]);

        return redirect()->route('agent.apply')
            ->with('success', 'Application received. We\'ll review and get back to you soon.');
    }
}
