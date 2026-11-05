<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Who is behind a visit: a named bot (Googlebot, GPTBot, AhrefsBot …), a cloud server (Amazon AWS,
 * Google Cloud, DigitalOcean …), a person — and on which network (Grameenphone, Robi …).
 *
 * Three independent signals:
 *  - User-Agent: what the visitor says it is (BOTS below; honest crawlers always say so);
 *  - the IP's network (ip-api.com: ISP / organisation / ASN / "hosting" = data centre). A claimed
 *    Googlebot that doesn't come from Google's network is a fake; a "Chrome" from an AWS address
 *    is a server, not a shopper;
 *  - behaviour, decided by the caller: did a real touch / mouse / key event ever happen.
 *
 * IP details are cached for 30 days. Lookups are never made while a visitor waits: pages read only
 * what's cached and queue the rest (resolveLater / the visitors:resolve-ips schedule).
 */
class VisitorIntel
{
    public const KINDS = [
        'search'  => 'Search engine',
        'ai'      => 'AI crawler',
        'social'  => 'Link preview',
        'seo'     => 'SEO tool',
        'monitor' => 'Uptime / speed test',
        'ads'     => 'Ad review',
        'tool'    => 'Script / scraper',
        'fake'    => 'Fake bot',
        'cloud'   => 'Cloud server',
    ];

    /**
     * [User-Agent pattern, name, kind, network that must own the IP for the claim to be true (null: not checked)].
     * First match wins, so specific names come before generic ones.
     */
    private const BOTS = [
        // search engines
        ['/AdsBot-Google/i', 'Google Ads bot', 'ads', '/google/i'],
        ['/Mediapartners-Google/i', 'Google AdSense bot', 'ads', '/google/i'],
        ['/Google-InspectionTool/i', 'Google Search Console', 'search', '/google/i'],
        ['/Storebot-Google/i', 'Google Shopping bot', 'search', '/google/i'],
        ['/Googlebot-Image/i', 'Googlebot Images', 'search', '/google/i'],
        ['/Googlebot-Video/i', 'Googlebot Video', 'search', '/google/i'],
        ['/Googlebot/i', 'Googlebot', 'search', '/google/i'],
        ['/GoogleOther/i', 'GoogleOther', 'search', '/google/i'],
        ['/Google-Read-Aloud|Google Favicon|FeedFetcher-Google|Google-Site-Verification/i', 'Google service', 'search', '/google/i'],
        ['/Chrome-Lighthouse|Google Page Speed|PageSpeed/i', 'Google PageSpeed test', 'monitor', null],
        ['/bingbot|BingPreview/i', 'Bingbot', 'search', '/microsoft/i'],
        ['/adidxbot/i', 'Bing Ads bot', 'ads', '/microsoft/i'],
        ['/YandexBot|YandexImages|YandexMobileBot|Yandex(?:Metrika|Accessibility)/i', 'YandexBot', 'search', '/yandex/i'],
        ['/Baiduspider/i', 'Baiduspider', 'search', '/baidu|china/i'],
        ['/DuckDuckBot|DuckAssistBot/i', 'DuckDuckBot', 'search', null],
        ['/Applebot/i', 'Applebot', 'search', '/apple/i'],
        ['/Slurp/i', 'Yahoo Slurp', 'search', '/yahoo|oath/i'],
        ['/PetalBot|AspiegelBot/i', 'PetalBot (Huawei)', 'search', null],
        ['/SeznamBot/i', 'SeznamBot', 'search', null],
        ['/Sogou/i', 'Sogou spider', 'search', null],
        ['/coccocbot/i', 'Cốc Cốc bot', 'search', null],
        ['/Qwantify|Qwantbot/i', 'Qwant bot', 'search', null],
        ['/MojeekBot/i', 'MojeekBot', 'search', null],
        // AI
        ['/GPTBot/i', 'GPTBot (OpenAI)', 'ai', null],
        ['/ChatGPT-User/i', 'ChatGPT (user request)', 'ai', null],
        ['/OAI-SearchBot/i', 'ChatGPT Search (OpenAI)', 'ai', null],
        ['/ClaudeBot|Claude-User|Claude-SearchBot|anthropic-ai/i', 'ClaudeBot (Anthropic)', 'ai', null],
        ['/PerplexityBot|Perplexity-User/i', 'PerplexityBot', 'ai', null],
        ['/Bytespider/i', 'Bytespider (TikTok)', 'ai', null],
        ['/CCBot/i', 'Common Crawl', 'ai', null],
        ['/Amazonbot/i', 'Amazonbot', 'ai', '/amazon/i'],
        ['/meta-externalagent|meta-externalfetcher|FacebookBot/i', 'Meta AI crawler', 'ai', '/facebook|meta/i'],
        ['/Google-Extended/i', 'Google AI (Gemini)', 'ai', '/google/i'],
        ['/cohere-ai|cohere-training/i', 'Cohere AI', 'ai', null],
        ['/MistralAI-User/i', 'Mistral AI', 'ai', null],
        ['/YouBot/i', 'You.com bot', 'ai', null],
        ['/Diffbot/i', 'Diffbot', 'ai', null],
        ['/ImagesiftBot/i', 'ImagesiftBot', 'ai', null],
        ['/Timpibot/i', 'Timpibot', 'ai', null],
        // link previews / social
        ['/facebookexternalhit|facebookcatalog|Facebot|meta-externalhit/i', 'Facebook', 'social', '/facebook|meta/i'],
        ['/WhatsApp/i', 'WhatsApp link preview', 'social', '/facebook|meta/i'],
        ['/Twitterbot/i', 'X (Twitter)', 'social', null],
        ['/LinkedInBot/i', 'LinkedIn', 'social', null],
        ['/TelegramBot/i', 'Telegram link preview', 'social', null],
        ['/Discordbot/i', 'Discord link preview', 'social', null],
        ['/Slackbot|Slack-ImgProxy/i', 'Slack link preview', 'social', null],
        ['/Pinterestbot/i', 'Pinterest', 'social', null],
        ['/redditbot/i', 'Reddit', 'social', null],
        ['/SkypeUriPreview/i', 'Skype link preview', 'social', null],
        ['/Snap URL Preview/i', 'Snapchat link preview', 'social', null],
        ['/Embedly|Iframely|vkShare|Tumblr/i', 'Link preview service', 'social', null],
        // SEO tools
        ['/AhrefsBot|AhrefsSiteAudit/i', 'AhrefsBot', 'seo', null],
        ['/SemrushBot|SiteAuditBot|SplitSignalBot/i', 'SemrushBot', 'seo', null],
        ['/MJ12bot/i', 'Majestic (MJ12bot)', 'seo', null],
        ['/DotBot|rogerbot/i', 'Moz bot', 'seo', null],
        ['/Screaming Frog/i', 'Screaming Frog', 'seo', null],
        ['/BLEXBot/i', 'BLEXBot', 'seo', null],
        ['/DataForSeoBot/i', 'DataForSEO bot', 'seo', null],
        ['/serpstatbot/i', 'Serpstat bot', 'seo', null],
        ['/SeekportBot/i', 'Seekport bot', 'seo', null],
        ['/Barkrowler/i', 'Barkrowler (Babbar)', 'seo', null],
        ['/SEOkicks|seoscanners|SiteCheckerBot|Sitebulb|Lumar|DeepCrawl|Botify|OnCrawl/i', 'SEO crawler', 'seo', null],
        ['/MegaIndex/i', 'MegaIndex', 'seo', null],
        ['/linkdexbot|Linkfluence|BrandVerity/i', 'Brand / link monitor', 'seo', null],
        // monitors & speed tests
        ['/UptimeRobot/i', 'UptimeRobot', 'monitor', null],
        ['/Pingdom/i', 'Pingdom', 'monitor', null],
        ['/StatusCake/i', 'StatusCake', 'monitor', null],
        ['/Site24x7/i', 'Site24x7', 'monitor', null],
        ['/BetterUptime|Better Uptime|Better Stack/i', 'Better Stack', 'monitor', null],
        ['/GTmetrix/i', 'GTmetrix', 'monitor', null],
        ['/HetrixTools|Freshping|Uptime-Kuma|UptimeBot|monitoring/i', 'Uptime monitor', 'monitor', null],
        ['/Cloudflare-(?:Healthchecks|Diagnostics|Traffic-Manager)|CloudFlare-AlwaysOnline/i', 'Cloudflare check', 'monitor', null],
        // scripts & automation
        ['/HeadlessChrome|HeadlessEdg/i', 'Headless Chrome (automation)', 'tool', null],
        ['/PhantomJS/i', 'PhantomJS (automation)', 'tool', null],
        ['/Puppeteer|Playwright|Selenium|WebDriver/i', 'Browser automation', 'tool', null],
        ['/python-requests|python-urllib|aiohttp|httpx|Python\//i', 'Python script', 'tool', null],
        ['/Scrapy/i', 'Scrapy scraper', 'tool', null],
        ['/curl\//i', 'curl', 'tool', null],
        ['/Wget\//i', 'Wget', 'tool', null],
        ['/Go-http-client/i', 'Go script', 'tool', null],
        ['/okhttp/i', 'OkHttp (Android/Java app)', 'tool', null],
        ['/Java\/|Apache-HttpClient/i', 'Java client', 'tool', null],
        ['/axios\/|node-fetch|undici|Node\.js/i', 'Node.js script', 'tool', null],
        ['/PostmanRuntime/i', 'Postman', 'tool', null],
        ['/libwww-perl|LWP::Simple/i', 'Perl script', 'tool', null],
        ['/GuzzleHttp|PHP\//i', 'PHP script', 'tool', null],
        ['/httpie/i', 'HTTPie', 'tool', null],
        ['/archive\.org_bot|ia_archiver/i', 'Internet Archive', 'search', null],
        // anything else that admits it (in-app browsers — Instagram, TikTok, Facebook — are people, not listed;
        // the lookbehind keeps Cubot phones out)
        ['/(?<!cu)bot\b|bot\/|crawl|spider|slurp|scanner|fetcher|preview/i', 'Other bot', 'tool', null],
    ];

    /** ASN → friendly network name, for the big clouds and well-known networks. */
    private const NETWORKS = [
        16509 => 'Amazon AWS', 14618 => 'Amazon AWS', 8987 => 'Amazon AWS', 7224 => 'Amazon AWS',
        15169 => 'Google', 396982 => 'Google Cloud', 19527 => 'Google Cloud', 36492 => 'Google',
        8075 => 'Microsoft Azure', 8068 => 'Microsoft', 8069 => 'Microsoft',
        14061 => 'DigitalOcean', 63949 => 'Akamai Linode', 16276 => 'OVHcloud', 24940 => 'Hetzner',
        213230 => 'Hetzner', 20473 => 'Vultr', 31898 => 'Oracle Cloud', 45102 => 'Alibaba Cloud',
        37963 => 'Alibaba Cloud', 132203 => 'Tencent Cloud', 45090 => 'Tencent Cloud', 51167 => 'Contabo',
        13335 => 'Cloudflare', 32934 => 'Meta (Facebook)', 714 => 'Apple', 6185 => 'Apple',
        13238 => 'Yandex', 55967 => 'Baidu', 38365 => 'Baidu', 136907 => 'Huawei Cloud',
        60781 => 'Leaseweb', 28753 => 'Leaseweb', 16265 => 'Leaseweb', 9009 => 'M247 (VPN / hosting)',
        212238 => 'Datacamp (VPN / hosting)', 47583 => 'Hostinger', 12876 => 'Scaleway', 20940 => 'Akamai',
        54113 => 'Fastly', 62240 => 'Clouvider', 174 => 'Cogent', 3223 => 'Voxility', 35916 => 'MultaCom',
        46606 => 'Unified Layer', 26496 => 'GoDaddy', 398324 => 'Censys', 211298 => 'Driftnet (scanner)',
        6939 => 'Hurricane Electric', 397423 => 'Tier.Net', 40021 => 'Contabo US', 202425 => 'IP Volume (scanner)',
        24389 => 'Grameenphone', 24432 => 'Robi Axiata', 45245 => 'Banglalink', 17494 => 'BTCL',
        23688 => 'Link3', 58717 => 'Summit Communications', 38203 => 'Teletalk',
    ];

    private const CACHE_DAYS = 30;
    private const QUEUE_KEY = 'ip_intel:queue';

    /** The bot a User-Agent claims to be: ['name', 'kind', 'verify'] or null. */
    public static function botFromUserAgent(?string $ua): ?array
    {
        $ua = trim((string) $ua);
        if ($ua === '') {
            return ['name' => 'No browser (empty User-Agent)', 'kind' => 'tool', 'verify' => null];
        }
        foreach (self::BOTS as [$pattern, $name, $kind, $verify]) {
            if (preg_match($pattern, $ua)) {
                return ['name' => $name, 'kind' => $kind, 'verify' => $verify];
            }
        }

        return null;
    }

    /** Cached details of one IP (null: not looked up yet, or private). */
    public static function ip(?string $ip): ?array
    {
        if (!$ip || !self::isPublic($ip)) {
            return null;
        }
        $hit = Cache::get(self::key($ip), '__miss__');

        return is_array($hit) ? $hit : null;
    }

    /** Cached details for many IPs (ip => details); the missing ones are queued for lookup. */
    public static function ips(iterable $ips, bool $queueMissing = true): array
    {
        $ips = collect($ips)->filter(fn ($ip) => $ip && self::isPublic($ip))->unique()->values();
        if ($ips->isEmpty()) {
            return [];
        }
        $found = Cache::many($ips->map(fn ($ip) => self::key($ip))->all());
        $out = [];
        $missing = [];
        foreach ($ips as $ip) {
            $v = $found[self::key($ip)] ?? null;
            if (is_array($v)) {
                $out[$ip] = $v;
            } elseif ($v === null) {
                $missing[] = $ip;
            }
        }
        if ($queueMissing && $missing) {
            self::queue($missing);
        }

        return $out;
    }

    /** Remember IPs to look up — resolved after this response (admin pages) or by the schedule. */
    public static function queue(array $ips): void
    {
        $ips = array_values(array_filter($ips, fn ($ip) => self::isPublic($ip)));
        if (!$ips) {
            return;
        }
        $queued = Cache::get(self::QUEUE_KEY, []);
        $queued = array_slice(array_values(array_unique(array_merge($queued, $ips))), -2000);
        Cache::put(self::QUEUE_KEY, $queued, now()->addDay());
    }

    /** Look up to $batches×100 queued IPs (ip-api.com free batch API: 15 batches a minute). */
    public static function resolveQueued(int $batches = 3): int
    {
        $queued = Cache::get(self::QUEUE_KEY, []);
        if (!$queued) {
            return 0;
        }
        $todo = array_slice($queued, 0, $batches * 100);
        Cache::put(self::QUEUE_KEY, array_values(array_slice($queued, count($todo))), now()->addDay());

        return self::lookup($todo);
    }

    /** Fetch and cache details for these IPs now. Returns how many were resolved. */
    public static function lookup(array $ips): int
    {
        $ips = array_values(array_filter(array_unique($ips), fn ($ip) => self::isPublic($ip) && !is_array(Cache::get(self::key($ip)))));
        $n = 0;
        foreach (array_chunk($ips, 100) as $chunk) {
            try {
                $res = Http::timeout(8)->post('http://ip-api.com/batch?fields=status,message,country,countryCode,city,isp,org,as,asname,mobile,proxy,hosting,query', $chunk);
            } catch (\Throwable) {
                self::queue($chunk);
                break;
            }
            if (!$res->ok()) {
                self::queue($chunk); // rate limited / down: try again on the next run
                break;
            }
            foreach ($res->json() ?: [] as $e) {
                if (!($ip = $e['query'] ?? null)) {
                    continue;
                }
                if (($e['status'] ?? null) !== 'success') {
                    Cache::put(self::key($ip), ['ok' => false], now()->addDays(self::CACHE_DAYS));
                    continue;
                }
                $asn = preg_match('/^AS(\d+)/', (string) ($e['as'] ?? ''), $m) ? (int) $m[1] : null;
                Cache::put(self::key($ip), [
                    'ok'      => true,
                    'city'    => $e['city'] ?? null,
                    'country' => $e['country'] ?? null,
                    'cc'      => $e['countryCode'] ?? null,
                    'isp'     => $e['isp'] ?? null,
                    'org'     => $e['org'] ?? null,
                    'asn'     => $asn,
                    'asname'  => $e['asname'] ?? null,
                    'hosting' => (bool) ($e['hosting'] ?? false),
                    'proxy'   => (bool) ($e['proxy'] ?? false),
                    'mobile'  => (bool) ($e['mobile'] ?? false),
                ], now()->addDays(self::CACHE_DAYS));
                $n++;
            }
        }

        return $n;
    }

    /** "Amazon AWS", "Grameenphone", "Google Cloud" … for an IP's details. */
    public static function network(?array $info): ?string
    {
        if (!$info || empty($info['ok'])) {
            return null;
        }
        if ($info['asn'] && isset(self::NETWORKS[$info['asn']])) {
            return self::NETWORKS[$info['asn']];
        }
        $name = $info['isp'] ?: ($info['org'] ?: $info['asname']);

        if (!$name) {
            return null;
        }
        $name = trim(preg_replace('/\b(Ltd|Limited|LLC|Inc|Corp|Corporation|Co|GmbH|B\.V|S\.A|Pvt|Private)\b\.?,?/i', '', $name), " ,.-");

        return $name === strtoupper($name) && strlen($name) > 4 ? ucwords(strtolower($name)) : $name; // "TELETALK" → "Teletalk"
    }

    public static function location(?array $info): ?string
    {
        return $info && !empty($info['ok']) ? (collect([$info['city'] ?? null, $info['country'] ?? null])->filter()->implode(', ') ?: null) : null;
    }

    /**
     * The verdict for one visit:
     *   type  human | bot | cloud | unknown
     *   name  "Googlebot", "Fake Googlebot", "Amazon AWS server", "Person" …
     *   kind  a KINDS key for bots / cloud
     *   verified  true: the claim matches the IP's network; false: it doesn't; null: not checked
     *   network, location, note
     *
     * $engaged: a real touch / mouse / key event was seen (blog tracker); null when unknown
     * (activity log). $automation: ActivityLog::automationSignal() reason stored with the visit.
     */
    public static function verdict(?string $ua, ?string $ip, ?bool $engaged = null, ?string $automation = null, ?array $info = null): array
    {
        $info ??= self::ip($ip);
        $network = self::network($info);
        $base = ['network' => $network, 'location' => self::location($info), 'verified' => null, 'note' => null];
        $hosting = $info && !empty($info['hosting']);

        if ($bot = self::botFromUserAgent($ua)) {
            if ($bot['verify'] && $info && !empty($info['ok'])) {
                if (!self::ownedBy($bot['verify'], $info)) {
                    return ['type' => 'bot', 'kind' => 'fake', 'name' => 'Fake ' . $bot['name'], 'verified' => false,
                        'note' => 'Claims to be ' . $bot['name'] . ' but comes from ' . ($network ?: 'another network')] + $base;
                }

                return ['type' => 'bot', 'kind' => $bot['kind'], 'name' => $bot['name'], 'verified' => true] + $base;
            }

            return ['type' => 'bot', 'kind' => $bot['kind'], 'name' => $bot['name']] + $base;
        }
        if ($automation === 'no-browser-headers') {
            return ['type' => 'bot', 'kind' => 'tool', 'name' => 'Scraper posing as a browser',
                'note' => 'Says it is a browser but sends none of the headers a real one does'] + $base;
        }
        if ($automation === 'too-fast') {
            return ['type' => 'bot', 'kind' => 'tool', 'name' => 'Too fast for a person', 'note' => '30+ product views in 10 minutes'] + $base;
        }
        if ($automation === 'no-js') {
            return ['type' => 'bot', 'kind' => 'tool', 'name' => 'Scraper (never ran JavaScript)'] + $base;
        }
        if ($automation === 'cloud') {
            return ['type' => 'cloud', 'kind' => 'cloud', 'name' => ($network ?: 'Cloud') . ' server',
                'note' => 'Comes from a data centre and never shopped'] + $base;
        }
        if ($engaged) {
            return ['type' => 'human', 'kind' => null, 'name' => 'Person'] + array_replace($base, [
                'note' => $hosting ? 'Real touch/mouse activity, but through ' . ($network ?: 'a data centre') . ' — VPN, proxy or a well-built bot' : null,
            ]);
        }
        if ($hosting) {
            return ['type' => 'cloud', 'kind' => 'cloud', 'name' => ($network ?: 'Cloud') . ' server',
                'note' => 'Comes from a data centre, not a home or mobile connection'] + $base;
        }
        if ($engaged === false) {
            return ['type' => 'unknown', 'kind' => null, 'name' => 'No interaction',
                'note' => 'Opened the page but never touched, scrolled or moved the mouse — left at once, or a bot'] + $base;
        }

        return ['type' => 'human', 'kind' => null, 'name' => 'Person'] + $base;
    }

    /**
     * The same verdict, from what a blog_reads row already has stamped (net, net_hosting,
     * bot_verified) — lets the dashboard count every view with one GROUP BY instead of per IP.
     */
    public static function storedVerdict(?string $botName, ?string $botKind, ?bool $botVerified, ?string $net, ?bool $hosting, bool $engaged): array
    {
        $base = ['network' => $net, 'location' => null, 'verified' => null, 'note' => null];

        if ($botName) {
            if ($botVerified === false) {
                return ['type' => 'bot', 'kind' => 'fake', 'name' => 'Fake ' . $botName, 'verified' => false,
                    'note' => 'Claims to be ' . $botName . ' but comes from ' . ($net ?: 'another network')] + $base;
            }
            if ($botName === 'Scraper posing as a browser') {
                return ['type' => 'bot', 'kind' => 'tool', 'name' => $botName,
                    'note' => 'Says it is a browser but sends none of the headers a real one does'] + $base;
            }

            return ['type' => 'bot', 'kind' => $botKind ?: 'tool', 'name' => $botName, 'verified' => $botVerified] + $base;
        }
        if ($engaged) {
            return ['type' => 'human', 'kind' => null, 'name' => 'Person'] + array_replace($base, [
                'note' => $hosting ? 'Real touch/mouse activity, but through ' . ($net ?: 'a data centre') . ' — VPN, proxy or a well-built bot' : null,
            ]);
        }
        if ($hosting) {
            return ['type' => 'cloud', 'kind' => 'cloud', 'name' => ($net ?: 'Cloud') . ' server',
                'note' => 'Comes from a data centre, not a home or mobile connection'] + $base;
        }

        return ['type' => 'unknown', 'kind' => null, 'name' => 'No interaction',
            'note' => 'Opened the page but never touched, scrolled or moved the mouse — left at once, or a bot'] + $base;
    }

    /**
     * Writes each IP's network onto its blog_reads rows (and whether a named bot really is that
     * bot). Only IPs already looked up are stamped; private / unresolvable ones are marked "not a
     * data centre" so they stop showing as pending. Returns the rows updated.
     */
    public static function stampBlogReads(iterable $ips): int
    {
        $ips = collect($ips)->filter()->unique()->values();
        if ($ips->isEmpty()) {
            return 0;
        }
        $intel = self::ips($ips, false);
        $rows = 0;

        foreach ($ips->chunk(200) as $chunk) {
            $net = $host = [];
            foreach ($chunk as $ip) {
                $info = $intel[$ip] ?? null;
                if ($info === null && self::isPublic($ip)) {
                    continue; // not looked up yet: stays pending
                }
                $net[$ip] = self::network($info);
                $host[$ip] = $info && !empty($info['hosting']);
            }
            if (!$net) {
                continue;
            }
            $caseNet = $caseHost = '';
            $bind = [];
            foreach ($net as $ip => $name) {
                $caseNet .= ' WHEN ? THEN ?';
                array_push($bind, $ip, $name);
            }
            foreach ($host as $ip => $h) {
                $caseHost .= ' WHEN ? THEN ?';
                array_push($bind, $ip, $h ? 1 : 0);
            }
            $keys = array_keys($net);
            $rows += DB::update(
                'UPDATE blog_reads SET net = CASE ip' . $caseNet . ' END, net_hosting = CASE ip' . $caseHost . ' END'
                . ' WHERE net_hosting IS NULL AND ip IN (' . implode(',', array_fill(0, count($keys), '?')) . ')',
                array_merge($bind, $keys)
            );

            // named bots: does the network match the name? (fake Googlebot from Amazon AWS)
            $bots = DB::table('blog_reads')->whereIn('ip', $keys)->whereNotNull('bot_name')->whereNull('bot_verified')
                ->distinct()->get(['ip', 'bot_name']);
            foreach ($bots as $b) {
                $info = $intel[$b->ip] ?? null;
                $pattern = self::verifyPatternFor($b->bot_name);
                if ($pattern && $info && !empty($info['ok'])) {
                    DB::table('blog_reads')->where('ip', $b->ip)->where('bot_name', $b->bot_name)->whereNull('bot_verified')
                        ->update(['bot_verified' => self::ownedBy($pattern, $info)]);
                }
            }
        }

        return $rows;
    }

    /** Recent blog views whose network isn't stamped yet. */
    public static function pendingBlogIps(int $limit = 3000): \Illuminate\Support\Collection
    {
        return DB::table('blog_reads')->where('created_at', '>=', now()->subDays(3))->whereNull('net_hosting')
            ->whereNotNull('ip')->distinct()->limit($limit)->pluck('ip');
    }

    private static function verifyPatternFor(string $name): ?string
    {
        foreach (self::BOTS as [, $botName, , $verify]) {
            if ($botName === $name) {
                return $verify;
            }
        }

        return null;
    }

    private static function ownedBy(string $pattern, array $info): bool
    {
        $owner = implode(' ', array_filter([$info['isp'] ?? '', $info['org'] ?? '', $info['asname'] ?? '', self::network($info)]));

        return (bool) preg_match($pattern, $owner);
    }

    public static function icon(array $v): string
    {
        return match (true) {
            $v['type'] === 'human'    => '👤',
            $v['type'] === 'cloud'    => '☁️',
            $v['type'] === 'unknown'  => '❔',
            $v['kind'] === 'fake'     => '🎭',
            $v['kind'] === 'search'   => '🔍',
            $v['kind'] === 'ai'       => '🧠',
            $v['kind'] === 'social'   => '💬',
            $v['kind'] === 'seo'      => '📈',
            $v['kind'] === 'monitor'  => '⏱️',
            $v['kind'] === 'ads'      => '📢',
            default                   => '🤖',
        };
    }

    private static function key(string $ip): string
    {
        return 'ip_intel:' . $ip;
    }

    private static function isPublic(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
