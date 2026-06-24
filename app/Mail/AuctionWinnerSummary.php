<?php

namespace App\Mail;

use App\Models\Auction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class AuctionWinnerSummary extends Mailable
{
    use Queueable;

    public function __construct(
        public Auction $auction,
        public User $winner,
        public Collection $lots,
        public float $grandTotal,
    ) {}

    public function envelope(): Envelope
    {
        $fromName = config('branding.name');
        $auctioneer = $this->auction->auctioneer;
        if ($auctioneer && $auctioneer->isWhiteLabel()) {
            $fromName = $auctioneer->business_name . ' via ' . config('branding.name');
        }

        return new Envelope(
            from: new Address(config('mail.from.address'), $fromName),
            subject: 'Congratulations! You won lots in: ' . $this->auction->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auction-winner-summary',
        );
    }
}
