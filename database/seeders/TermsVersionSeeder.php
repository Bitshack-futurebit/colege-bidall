<?php

namespace Database\Seeders;

use App\Models\TermsVersion;
use Illuminate\Database\Seeder;

class TermsVersionSeeder extends Seeder
{
    public function run(): void
    {
        if (TermsVersion::count() > 0) {
            return;
        }

        // Auctioneer Terms
        TermsVersion::create([
            'version' => '1.0',
            'title' => 'Auctioneer Terms and Conditions Agreement',
            'role' => 'auctioneer',
            'content' => $this->getAuctioneerContent(),
            'published_at' => now(),
        ]);

        // Bidder Terms
        TermsVersion::create([
            'version' => '1.0',
            'title' => 'Bidder Terms and Conditions Agreement',
            'role' => 'bidder',
            'content' => $this->getBidderContent(),
            'published_at' => now(),
        ]);

        TermsVersion::clearCache();
    }

    private function getAuctioneerContent(): string
    {
        return <<<'HTML'
<h2>1. INTRODUCTION</h2>

<p>1.1 This Agreement governs the relationship between BidAll.co.za ("BidAll", "the Platform", "we", "us") and any person or entity registering as an auctioneer ("Auctioneer", "you", "your").</p>

<p>1.2 By registering an account, listing any lot, or using the Platform, you agree to be legally bound by this Agreement.</p>

<p>1.3 BidAll is a technology platform facilitating online auctions between independent buyers and auctioneers.
<strong>BidAll is not an auction house, seller, buyer, broker, or agent of any party.</strong></p>

<h2>2. PLATFORM ROLE</h2>

<p>2.1 BidAll provides infrastructure for:</p>
<ul>
    <li>Listing auction lots</li>
    <li>Real-time bidding</li>
    <li>Auction management</li>
    <li>Payment facilitation</li>
</ul>

<p>2.2 BidAll does not:</p>
<ul>
    <li>Take ownership of goods</li>
    <li>Inspect goods</li>
    <li>Guarantee legality, authenticity, or condition</li>
    <li>Act as agent or representative of any user</li>
</ul>

<p>2.3 All transactions occur solely between Auctioneer and Buyer.</p>

<h2>3. AUCTIONEER OBLIGATIONS</h2>

<p>3.1 You warrant that you have full legal right to sell all listed items.</p>

<p>3.2 You warrant that all goods:</p>
<ul>
    <li>Are lawful to sell in South Africa</li>
    <li>Are not stolen, counterfeit, or encumbered</li>
    <li>Are accurately described</li>
</ul>

<p>3.3 You agree to comply with all applicable laws.</p>

<p>3.4 You are solely responsible for:</p>
<ul>
    <li>Delivery or collection</li>
    <li>Buyer communication</li>
    <li>Dispute resolution</li>
</ul>

<h2>4. FEES AND PAYMENT STRUCTURE</h2>

<p>4.1 <strong>Listing Fees</strong> &mdash; Charged per lot and payable upfront when a listing goes live.</p>

<p>4.2 <strong>Success Fee</strong> &mdash; A 1% fee is charged on sold lots upon auction close.</p>

<p>4.3 <strong>Payment Flow</strong> &mdash; Buyer payments are made to BidAll, which facilitates payout to the Auctioneer after conditions are met.</p>

<p>4.4 <strong>No Agency</strong> &mdash; Payment handling does not create any agency or fiduciary relationship.</p>

<h3>4A. NET SETTLEMENT AND FEE PRIORITY</h3>

<p>4A.1 All amounts received by BidAll are subject to automatic fee settlement.</p>

<p>4A.2 BidAll has the first and overriding right to deduct:</p>
<ul>
    <li>Listing fees</li>
    <li>Success fees</li>
    <li>Outstanding balances</li>
    <li>Any other amounts owed</li>
</ul>

<p>4A.3 No entitlement to funds arises until all BidAll fees are settled.</p>

<p>4A.4 Only the net balance is payable to the Auctioneer.</p>

<h3>4B. AUTHORISATION TO DEDUCT AND SET-OFF</h3>

<p>4B.1 You irrevocably authorise BidAll to:</p>
<ul>
    <li>Deduct any amounts owed</li>
    <li>Apply funds across transactions</li>
    <li>Set off debts against payouts</li>
</ul>

<h3>4C. NATURE OF FUNDS AND NO FIDUCIARY DUTY</h3>

<p>4C.1 Funds received are part of a payment facilitation process only.</p>

<p>4C.2 Funds:</p>
<ul>
    <li>Are not trust funds</li>
    <li>Are not held in trust</li>
    <li>Do not create fiduciary obligations</li>
</ul>

<p>4C.3 BidAll is not a bank or financial institution.</p>

<h3>4D. CONDITIONAL PAYOUT</h3>

<p>4D.1 Payouts are conditional upon:</p>
<ul>
    <li>Collection confirmation</li>
    <li>No disputes</li>
    <li>No fraud indicators</li>
    <li>System integrity</li>
</ul>

<p>4D.2 BidAll may delay, withhold, or reverse payouts where necessary.</p>

<h3>4E. TRANSACTION REVERSALS AND ERRORS</h3>

<p>4E.1 Transactions may be affected by:</p>
<ul>
    <li>Chargebacks</li>
    <li>Reversals</li>
    <li>System errors</li>
</ul>

<p>4E.2 BidAll is not liable for such events.</p>

<p>4E.3 BidAll may reverse or recover funds.</p>

<p>4E.4 You must repay any incorrectly paid amounts.</p>

<h3>4F. PLATFORM ERRORS AND AUCTION GLITCHES</h3>

<p>4F.1 The Platform may experience:</p>
<ul>
    <li>Software errors</li>
    <li>Connectivity issues</li>
    <li>Timing discrepancies</li>
</ul>

<p>4F.2 BidAll may:</p>
<ul>
    <li>Cancel or restart auctions</li>
    <li>Adjust results</li>
    <li>Void sales</li>
</ul>

<p>4F.3 You waive claims arising from such issues.</p>

<h3>4G. LIMITATION OF LIABILITY FOR TRANSACTIONS</h3>

<p>BidAll has no responsibility for:</p>
<ul>
    <li>Non-payment</li>
    <li>Non-delivery</li>
    <li>Fraud</li>
    <li>Misrepresentation</li>
    <li>Any disputes</li>
</ul>

<p>All risk lies with the Auctioneer.</p>

<h3>4H. FRAUD AND RISK MANAGEMENT</h3>

<p>4H.1 BidAll may implement fraud controls.</p>

<p>4H.2 BidAll may:</p>
<ul>
    <li>Suspend accounts</li>
    <li>Freeze payouts</li>
    <li>Request verification</li>
</ul>

<p>4H.3 BidAll may report suspicious activity in terms of the Financial Intelligence Centre Act.</p>

<h2>5. LISTINGS</h2>

<p>6.1 Listings cannot be removed once live.</p>

<p>6.2 Listings may only be withdrawn by the Auctioneer.</p>

<p>6.3 Fees are non-refundable.</p>

<h2>7. PROHIBITED ITEMS</h2>

<p>Auctioneers may not list illegal, stolen, counterfeit, or restricted goods.</p>

<h2>8. INDEMNITY</h2>

<p>You indemnify BidAll against:</p>
<ul>
    <li>All claims</li>
    <li>All damages</li>
    <li>All legal costs</li>
</ul>

<p>arising from:</p>
<ul>
    <li>Your listings</li>
    <li>Transactions</li>
    <li>Legal breaches</li>
    <li>Fraud or misconduct</li>
</ul>

<h2>9. LIMITATION OF LIABILITY</h2>

<p>BidAll's total liability shall not exceed the fees paid by the Auctioneer in the preceding 12 months. BidAll shall not be liable for any indirect, incidental, special, consequential, or punitive damages.</p>
HTML;
    }

    private function getBidderContent(): string
    {
        return <<<'HTML'
<h2>1. INTRODUCTION</h2>

<p>1.1 This Agreement governs the relationship between BidAll.co.za ("BidAll", "the Platform", "we", "us") and any person registering as a bidder ("Bidder", "you", "your").</p>

<p>1.2 By registering an account, placing any bid, or using the Platform, you agree to be legally bound by this Agreement.</p>

<p>1.3 BidAll is a technology platform facilitating online auctions between independent buyers and auctioneers. <strong>BidAll is not an auction house, seller, buyer, broker, or agent of any party.</strong></p>

<h2>2. PLATFORM ROLE</h2>

<p>2.1 BidAll provides infrastructure for online auctions, real-time bidding, and payment facilitation.</p>

<p>2.2 BidAll does not take ownership of goods, inspect goods, guarantee legality, authenticity, or condition, or act as agent or representative of any user.</p>

<p>2.3 All transactions occur solely between Auctioneer and Bidder.</p>

<h2>3. BIDDER OBLIGATIONS</h2>

<p>3.1 <strong>Binding Bids</strong> &mdash; All bids placed are legally binding. By placing a bid, you commit to purchasing the item if you are the winning bidder.</p>

<p>3.2 <strong>No Bid Retraction</strong> &mdash; Bids cannot be retracted, cancelled, or withdrawn once placed.</p>

<p>3.3 <strong>Payment Obligation</strong> &mdash; Winning bidders must complete payment within the timeframe specified by the Auctioneer or the Platform.</p>

<p>3.4 <strong>Collection</strong> &mdash; You are responsible for collecting or arranging delivery of purchased items within the timeframe specified.</p>

<p>3.5 <strong>Accurate Information</strong> &mdash; You must provide accurate, complete, and current personal and payment information.</p>

<p>3.6 <strong>Account Security</strong> &mdash; You are responsible for maintaining the confidentiality of your account credentials. All activity under your account is your responsibility.</p>

<h2>4. PROHIBITED CONDUCT</h2>

<p>You may not:</p>
<ul>
    <li>Engage in shill bidding or bid manipulation</li>
    <li>Place bids without intention to pay</li>
    <li>Collude with other bidders to manipulate prices</li>
    <li>Use automated tools or bots to place bids</li>
    <li>Create multiple accounts to circumvent restrictions</li>
    <li>Interfere with other users' accounts or platform operations</li>
    <li>Use the platform for fraudulent purposes</li>
</ul>

<h2>5. NON-PAYMENT CONSEQUENCES</h2>

<p>5.1 Failure to pay for won items may result in:</p>
<ul>
    <li>Account suspension or permanent ban</li>
    <li>Negative feedback on your profile</li>
    <li>Loss of any deposits paid</li>
    <li>Legal action by the Auctioneer</li>
</ul>

<p>5.2 BidAll reserves the right to suspend accounts of bidders with a pattern of non-payment.</p>

<h2>6. AUCTION DEPOSITS</h2>

<p>6.1 Some auctions may require a refundable deposit to participate.</p>

<p>6.2 Deposits are refunded after the auction closes, provided no items were won or full payment has been made.</p>

<p>6.3 Deposits may be forfeited if you win items and fail to pay.</p>

<h2>7. PAYMENT</h2>

<p>7.1 Payments are facilitated through the Platform's payment gateway.</p>

<p>7.2 Payment is made directly through the platform to the Auctioneer. BidAll bears no responsibility for non-delivery.</p>

<h2>8. DISCLAIMER AND LIMITATION OF LIABILITY</h2>

<p>8.1 BidAll does not guarantee the accuracy of item descriptions, photographs, or condition reports provided by Auctioneers.</p>

<p>8.2 It is your responsibility to inspect items (where possible) and satisfy yourself before bidding.</p>

<p>8.3 BidAll shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of the service.</p>

<p>8.4 BidAll's total liability shall not exceed the total amount you have paid through the Platform in the preceding 12 months.</p>

<h2>9. DISPUTE RESOLUTION</h2>

<p>9.1 In the event of disputes between you and an Auctioneer, disputes must be resolved directly with the Auctioneer.</p>

<p>9.2 BidAll may assist in facilitating communication but is not obligated to resolve disputes.</p>

<h2>10. ACCOUNT SUSPENSION AND TERMINATION</h2>

<p>10.1 BidAll may suspend or terminate your account at any time for breach of these terms.</p>

<p>10.2 Auctioneers may also suspend specific bidders from their auctions.</p>

<p>10.3 You may close your account at any time, provided all outstanding obligations are met.</p>

<h2>11. INDEMNITY</h2>

<p>You indemnify BidAll against all claims, damages, and legal costs arising from your use of the Platform, breach of these terms, or disputes with Auctioneers.</p>

<h2>12. CHANGES TO TERMS</h2>

<p>12.1 BidAll reserves the right to modify these terms at any time.</p>

<p>12.2 You will be notified of material changes and required to accept updated terms to continue using the Platform.</p>
HTML;
    }
}
