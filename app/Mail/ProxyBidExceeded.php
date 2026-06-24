<?php

namespace App\Mail;

use App\Models\Lot;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProxyBidExceeded extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Lot $lot,
        public float $proxyMax,
        public float $currentBid,
    ) {}

    public function envelope(): Envelope
    {
        $fromName = config('branding.name');
        $auctioneer = $this->lot->auction?->auctioneer;
        if ($auctioneer && $auctioneer->isWhiteLabel()) {
            $fromName = $auctioneer->business_name . ' via ' . config('branding.name');
        }

        return new Envelope(
            from: new Address(config('mail.from.address'), $fromName),
            subject: "Your proxy bid on {$this->lot->title} was exceeded",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.proxy-bid-exceeded',
        );
    }
}
