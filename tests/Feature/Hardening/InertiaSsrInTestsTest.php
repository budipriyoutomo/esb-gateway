<?php

namespace Tests\Feature\Hardening;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A local `npm run build:ssr` leaves a bundle in bootstrap/ssr/ (gitignored).
 * With SSR on, every Inertia render would then POST to the SSR server through
 * the HTTP client, and every `Http::fake()` count in the suite would be off by
 * one. phpunit.xml turns SSR off (INERTIA_SSR_ENABLED=false).
 */
class InertiaSsrInTestsTest extends TestCase
{
    private const BUNDLE = 'bootstrap/ssr/ssr.mjs';

    private bool $createdBundle = false;

    private bool $createdDirectory = false;

    protected function tearDown(): void
    {
        if ($this->createdBundle) {
            File::delete(base_path(self::BUNDLE));
        }

        if ($this->createdDirectory) {
            File::deleteDirectory(dirname(base_path(self::BUNDLE)));
        }

        parent::tearDown();
    }

    #[Test]
    public function ssr_is_off_under_phpunit(): void
    {
        $this->assertFalse(config('inertia.ssr.enabled'));
    }

    #[Test]
    public function an_ssr_bundle_on_disk_does_not_reach_the_http_client(): void
    {
        $bundle = base_path(self::BUNDLE);

        if (! File::exists($bundle)) {
            $this->createdDirectory = ! File::isDirectory(dirname($bundle));
            File::ensureDirectoryExists(dirname($bundle));
            File::put($bundle, '// test bundle');
            $this->createdBundle = true;
        }

        Http::fake();

        $this->get(route('login'))->assertOk();

        Http::assertNothingSent();
    }
}
