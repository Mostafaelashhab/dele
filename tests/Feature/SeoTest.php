<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What a crawler and a share preview see.
 *
 * None of this is visible while using the site, so it breaks silently: a
 * missing canonical splits a page across several addresses, a broken share
 * card makes every WhatsApp link look like spam, and an indexed tracking URL
 * publishes a customer's address. These are the checks that would otherwise
 * only fail in public.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Zone::factory()->count(3)->create();
    }

    #[Test]
    public function the_landing_page_is_indexable_and_describes_itself(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertSee('name="description"', escape: false);
        $response->assertSee('rel="canonical"', escape: false);
        $response->assertSee('index, follow', escape: false);
    }

    /**
     * A tracking link carries a customer's address and phone. It must never
     * be indexed, and the token must not leak to a map tile host as a referrer.
     */
    #[Test]
    public function a_tracking_page_is_never_indexable(): void
    {
        $delivery = Delivery::factory()->create();

        $response = $this->get(route('tracking.show', $delivery->tracking_token))->assertOk();

        $response->assertSee('noindex', escape: false);
        $response->assertSee('name="referrer" content="no-referrer"', escape: false);
        $response->assertDontSee('rel="canonical"', escape: false);
        $response->assertDontSee('application/ld+json', escape: false);
    }

    #[Test]
    public function the_share_card_is_declared_with_a_real_image(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('og:image', escape: false)
            ->assertSee('twitter:card', escape: false)
            ->assertSee('summary_large_image', escape: false);

        $this->assertFileExists(public_path('og-image.png'));

        [$width, $height] = getimagesize(public_path('og-image.png'));

        // Below 1200x630 the major platforms crop or downgrade the card.
        $this->assertSame(1200, $width);
        $this->assertSame(630, $height);
    }

    #[Test]
    public function the_structured_data_is_valid_json(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

        $this->assertNotEmpty($matches, 'The page declared no structured data.');

        $data = json_decode(trim($matches[1]), true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Structured data must be valid JSON.');
        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertContains('Organization', array_column($data['@graph'], '@type'));
        $this->assertContains('WebSite', array_column($data['@graph'], '@type'));
    }

    #[Test]
    public function the_sitemap_lists_only_public_pages(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml, 'The sitemap must be valid XML.');
        $this->assertNotEmpty($xml->url);

        $locations = array_map('strval', iterator_to_array($xml->xpath('//*[local-name()="loc"]')));

        $this->assertContains(route('home'), $locations);

        // Compared as path prefixes, not substrings: /register/company is a
        // public page that happens to contain the string "/company".
        $paths = array_map(fn (string $url) => parse_url($url, PHP_URL_PATH) ?: '/', $locations);

        foreach (['/track', '/admin', '/app', '/company', '/rider', '/api'] as $private) {
            foreach ($paths as $path) {
                $this->assertFalse(
                    $path === $private || str_starts_with($path, $private.'/'),
                    "The sitemap must not publish {$private} (found {$path})."
                );
            }
        }
    }

    /**
     * @param  string  $path  a route a crawler must be told to stay out of
     */
    #[Test]
    #[DataProvider('privateAreas')]
    public function robots_keeps_crawlers_out_of_private_areas(string $path): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString("Disallow: {$path}", $robots);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function privateAreas(): array
    {
        return [
            'tracking links' => ['/track/'],
            'admin portal' => ['/admin'],
            'business portal' => ['/app'],
            'company portal' => ['/company'],
            'rider app' => ['/rider'],
            'the api' => ['/api/'],
        ];
    }

    #[Test]
    public function the_brand_assets_a_browser_asks_for_all_exist(): void
    {
        foreach ([
            'favicon.svg',
            'favicon.ico',
            'apple-touch-icon.png',
            'icons/icon-192.png',
            'icons/icon-512.png',
            'site.webmanifest',
            'og-image.png',
        ] as $asset) {
            $path = public_path($asset);

            $this->assertFileExists($path);

            // A zero-byte favicon.ico shipped in this repo once and nothing
            // noticed, because a browser asks for it quietly and gives up.
            $this->assertGreaterThan(0, filesize($path), "{$asset} is empty.");
        }

        $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertNotEmpty($manifest['icons']);
        $this->assertContains('maskable', array_column($manifest['icons'], 'purpose'));
    }
}
