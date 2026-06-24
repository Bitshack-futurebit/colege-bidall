<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Foreign-exchange rates via openexchangerates.org.
 *
 * The feed is USD-based. Rates are CACHED (default 1h, matching OXR's hourly
 * updates) to stay within the free-plan monthly request cap. Any pair is derived
 * as a cross-rate from the USD-based feed.
 *
 * Used by BlinkGateway to convert ZAR (platform currency) → USD for Stablesats
 * Lightning invoices.
 */
class FxRateService
{
    /**
     * USD per 1 ZAR (e.g. ~0.06) — what BlinkGateway multiplies the rand amount by.
     */
    public function usdPerZar(): float
    {
        return $this->rate('USD', 'ZAR');
    }

    /**
     * How many units of $to currency equal 1 unit of $from, via the USD-based feed.
     *
     *   1 FROM = (1 / rates[FROM]) USD  and  1 EUR-style: 1 USD = rates[TO] TO
     *   => 1 FROM = rates[TO] / rates[FROM] TO   (rates[USD] === 1)
     */
    public function rate(string $to, string $from): float
    {
        $rates = $this->usdRates([$to, $from]);

        $toRate = $this->valueOf($rates, $to);
        $fromRate = $this->valueOf($rates, $from);

        if ($toRate <= 0 || $fromRate <= 0) {
            throw new \RuntimeException("FX rate unavailable for {$from}->{$to}");
        }

        return $toRate / $fromRate;
    }

    /**
     * Resolve a symbol's USD-based rate, treating USD (the base) as 1.
     */
    protected function valueOf(array $rates, string $symbol): float
    {
        if (!empty($rates[$symbol])) {
            return (float) $rates[$symbol];
        }

        return $symbol === 'USD' ? 1.0 : 0.0;
    }

    /**
     * USD-based rates for the given symbols, cached to respect the free-tier quota.
     *
     * @param string[] $symbols
     * @return array<string,float>
     */
    protected function usdRates(array $symbols): array
    {
        sort($symbols);
        $cacheKey = 'fx:usd:' . implode(',', $symbols);
        $ttl = (int) config('services.openexchangerates.ttl', 3600); // 1h default

        return Cache::remember($cacheKey, $ttl, function () use ($symbols) {
            $appId = config('services.openexchangerates.app_id');
            $base = rtrim(config('services.openexchangerates.base_url', 'https://openexchangerates.org/api'), '/');

            if (empty($appId)) {
                throw new \RuntimeException('Open Exchange Rates app_id not configured (OPENEXCHANGERATES_APP_ID)');
            }

            $response = Http::timeout(15)->get("{$base}/latest.json", [
                'app_id' => $appId,
                'symbols' => implode(',', $symbols),
            ]);

            $json = $response->json() ?? [];

            // OXR has no "success" flag: it returns base+rates on success, or an
            // {error:true,...} body (non-200) on failure.
            if (!$response->ok() || !empty($json['error']) || empty($json['rates'])) {
                Log::error('Open Exchange Rates request failed', [
                    'status' => $response->status(),
                    'body' => $json,
                ]);
                throw new \RuntimeException('FX rate fetch failed');
            }

            return $json['rates'];
        });
    }
}
