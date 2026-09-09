<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\BaseUrlResolver;
use App\Integrations\ESB\EsbEnvironment;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbConfigurationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ESB base URLs are not uniform: the vendor declares a different host family
 * per endpoint group, and the same logical environment maps to different hosts
 * per family (ADR-010, docs/05-api-integration.md).
 */
class BaseUrlResolverTest extends TestCase
{
    private function resolver(): BaseUrlResolver
    {
        return app(BaseUrlResolver::class);
    }

    #[Test]
    public function it_resolves_the_core_family_per_environment(): void
    {
        $resolver = $this->resolver();

        $this->assertSame(
            'https://services.esb.co.id/core',
            $resolver->resolve(EsbFamily::Core, EsbEnvironment::Production),
        );

        $this->assertSame(
            'https://stg7.esb.co.id/core-stg',
            $resolver->resolve(EsbFamily::Core, EsbEnvironment::Staging),
        );

        $this->assertSame(
            'https://stg7.esb.co.id/core-int',
            $resolver->resolve(EsbFamily::Core, EsbEnvironment::StagingInt),
        );
    }

    #[Test]
    public function the_same_environment_maps_to_different_hosts_per_family(): void
    {
        $resolver = $this->resolver();

        // This is the whole reason for ADR-010: a single ESB_BASE_URL cannot
        // represent the contract.
        $this->assertNotSame(
            $resolver->resolve(EsbFamily::Core, EsbEnvironment::Production),
            $resolver->resolve(EsbFamily::FnbBackend, EsbEnvironment::Production),
        );

        $this->assertSame(
            'https://core-api.esb.co.id',
            $resolver->resolve(EsbFamily::FnbBackend, EsbEnvironment::Production),
        );

        $this->assertSame(
            'https://services.esb.co.id/voucher',
            $resolver->resolve(EsbFamily::Voucher, EsbEnvironment::Production),
        );
    }

    #[Test]
    public function it_falls_back_to_the_configured_environment(): void
    {
        config()->set('esb.environment', 'production');

        $this->assertSame(
            'https://services.esb.co.id/core',
            $this->resolver()->resolve(EsbFamily::Core),
        );
    }

    #[Test]
    public function an_override_takes_precedence(): void
    {
        config()->set('esb.overrides.core', 'https://esb.internal.test/core');

        $this->assertSame(
            'https://esb.internal.test/core',
            $this->resolver()->resolve(EsbFamily::Core, EsbEnvironment::Production),
        );
    }

    #[Test]
    public function a_trailing_slash_is_stripped_so_paths_join_predictably(): void
    {
        config()->set('esb.overrides.core', 'https://esb.internal.test/core/');

        $this->assertSame(
            'https://esb.internal.test/core',
            $this->resolver()->resolve(EsbFamily::Core, EsbEnvironment::Production),
        );
    }

    #[Test]
    public function an_undocumented_base_url_fails_loudly(): void
    {
        // The vendor documents no staging-int host for the POS family. Failing
        // here is the point: silently falling back to another environment would
        // send staging traffic at production ERP data.
        $this->expectException(EsbConfigurationException::class);

        $this->resolver()->resolve(EsbFamily::Pos, EsbEnvironment::StagingInt);
    }

    #[Test]
    public function an_unknown_configured_environment_fails_loudly(): void
    {
        config()->set('esb.environment', 'wherever');

        $this->expectException(EsbConfigurationException::class);

        $this->resolver()->resolve(EsbFamily::Core);
    }
}
