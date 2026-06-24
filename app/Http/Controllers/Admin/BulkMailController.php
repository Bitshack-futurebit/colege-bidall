<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BroadcastEmail;
use App\Models\User;
use App\Models\Auctioneer;
use App\Models\AuctioneerFollower;
use App\Models\CommunityRegion;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class BulkMailController extends Controller
{
    public function send(Request $request)
    {
        // Single-user quick mail (from user list page)
        if ($request->filled('user_id')) {
            $request->validate([
                'subject' => ['required', 'string', 'max:255'],
                'message' => ['required', 'string'],
                'user_id' => ['required', 'integer', 'exists:users,id'],
            ]);

            $user = User::findOrFail($request->input('user_id'));

            try {
                $personalised = str_replace(
                    ['{name}', '{email}', '{platform_name}'],
                    [$user->name, $user->email, config('app.name')],
                    $request->input('message')
                );
                Mail::to($user->email)->send(new BroadcastEmail($user, $request->input('subject'), $personalised));
                return redirect()->back()->with('success', "Email sent to {$user->name} ({$user->email}).");
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', "Send failed: " . $e->getMessage());
            }
        }

        // Bulk broadcast — queue each email, return immediately
        $request->validate([
            'subject'        => ['required', 'string', 'max:255'],
            'message'        => ['required', 'string'],
            'recipients'     => ['required', 'array'],
            'recipients.*'   => ['in:all_users,bidders,auctioneers,active_bidders,activated_auctioneers,auctioneer_followers,community_members,geographic_region'],
            'auctioneer_id'  => ['required_if:recipients.*,auctioneer_followers', 'nullable', 'exists:auctioneers,id'],
            'community_id'   => ['required_if:recipients.*,community_members', 'nullable', 'exists:community_regions,id'],
            'geo_province'   => ['required_if:recipients.*,geographic_region', 'nullable', 'string', 'max:100'],
            'geo_city'       => ['nullable', 'string', 'max:100'],
        ]);

        $subject = $request->input('subject');
        $message = $request->input('message');
        $groups  = $request->input('recipients');

        $userIds = collect();

        foreach ($groups as $group) {
            switch ($group) {
                case 'all_users':
                    $userIds = $userIds->merge(User::where('is_active', true)->pluck('id'));
                    break;
                case 'bidders':
                    $userIds = $userIds->merge(User::where('role', 'bidder')->where('is_active', true)->pluck('id'));
                    break;
                case 'auctioneers':
                    $userIds = $userIds->merge(User::where('role', 'auctioneer')->where('is_active', true)->pluck('id'));
                    break;
                case 'active_bidders':
                    $userIds = $userIds->merge(
                        User::where('role', 'bidder')->where('is_active', true)->whereHas('bids')->pluck('id')
                    );
                    break;
                case 'activated_auctioneers':
                    $userIds = $userIds->merge(
                        User::where('role', 'auctioneer')->where('is_active', true)
                            ->whereHas('auctioneer', fn ($q) => $q->where('is_activated', true))
                            ->pluck('id')
                    );
                    break;
                case 'auctioneer_followers':
                    if ($request->filled('auctioneer_id')) {
                        $userIds = $userIds->merge(
                            AuctioneerFollower::where('auctioneer_id', $request->input('auctioneer_id'))
                                ->pluck('user_id')
                        );
                    }
                    break;
                case 'community_members':
                    if ($request->filled('community_id')) {
                        $userIds = $userIds->merge(
                            User::where('community_region_id', $request->input('community_id'))
                                ->where('is_active', true)
                                ->pluck('id')
                        );
                    }
                    break;
                case 'geographic_region':
                    if ($request->filled('geo_province')) {
                        $userIds = $userIds->merge(
                            User::where('province', $request->input('geo_province'))
                                ->where('is_active', true)
                                ->when(
                                    $request->filled('geo_city'),
                                    fn ($q) => $q->where('city', 'like', '%' . $request->input('geo_city') . '%')
                                )
                                ->pluck('id')
                        );
                    }
                    break;
            }
        }

        $users = User::whereIn('id', $userIds->unique()->values())->get();
        $count = $users->count();

        // Send admin copy if requested
        if ($request->boolean('admin_copy')) {
            $adminUser = auth()->user();
            $adminMessage = str_replace(
                ['{name}', '{email}', '{platform_name}'],
                [$adminUser->name, $adminUser->email, config('app.name')],
                $message
            );
            Mail::to($adminUser->email)->queue(
                (new BroadcastEmail($adminUser, $subject, $adminMessage))->onConnection('database')
            );
        }

        // Queue emails using database connection explicitly (avoid sync driver hanging)
        foreach ($users as $user) {
            $personalised = str_replace(
                ['{name}', '{email}', '{platform_name}'],
                [$user->name, $user->email, config('app.name')],
                $message
            );
            Mail::to($user->email)->queue(
                (new BroadcastEmail($user, $subject, $personalised))->onConnection('database')
            );
        }

        ActivityLog::log(
            'broadcast_sent',
            'Broadcast queued to: ' . implode(', ', $groups),
            null,
            ['subject' => $subject, 'queued' => $count]
        );

        return back()->with('success', "Broadcast queued for {$count} user(s). Emails will be sent within 1 minute.");
    }
}
