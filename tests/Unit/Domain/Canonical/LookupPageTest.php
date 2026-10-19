<?php

namespace Tests\Unit\Domain\Canonical;

use App\Domain\Canonical\LookupOption;
use App\Domain\Canonical\LookupPage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Searching and paging a list the ESB returns whole ([ESB-019]).
 */
class LookupPageTest extends TestCase
{
    /**
     * @return list<LookupOption>
     */
    private function options(int $count): array
    {
        return array_map(
            fn (int $i) => new LookupOption(value: (string) $i, label: "Branch {$i}", code: sprintf('B%03d', $i)),
            range(1, $count),
        );
    }

    #[Test]
    public function a_blank_search_keeps_every_option(): void
    {
        $page = LookupPage::fromAll($this->options(3), search: '  ', page: 1, limit: 20);

        $this->assertCount(3, $page->options);
        $this->assertFalse($page->hasMore);
    }

    #[Test]
    public function search_matches_label_or_code_ignoring_case(): void
    {
        $options = [
            new LookupOption(value: '1', label: 'Jakarta Pusat', code: 'JKT'),
            new LookupOption(value: '2', label: 'Bandung', code: 'BDG'),
            new LookupOption(value: '3', label: 'Surabaya', code: null),
        ];

        $byLabel = LookupPage::fromAll($options, search: 'pusat', page: 1, limit: 20);
        $byCode = LookupPage::fromAll($options, search: 'bdg', page: 1, limit: 20);

        $this->assertSame(['1'], array_map(fn ($o) => $o->value, $byLabel->options));
        $this->assertSame(['2'], array_map(fn ($o) => $o->value, $byCode->options));
    }

    #[Test]
    public function it_pages_the_filtered_options(): void
    {
        $first = LookupPage::fromAll($this->options(25), search: null, page: 1, limit: 20);
        $second = LookupPage::fromAll($this->options(25), search: null, page: 2, limit: 20);
        $beyond = LookupPage::fromAll($this->options(25), search: null, page: 3, limit: 20);

        $this->assertCount(20, $first->options);
        $this->assertTrue($first->hasMore);
        $this->assertSame('21', $second->options[0]->value);
        $this->assertCount(5, $second->options);
        $this->assertFalse($second->hasMore);
        $this->assertSame([], $beyond->options);
    }

    #[Test]
    public function it_serializes_for_the_picker(): void
    {
        $page = new LookupPage(
            options: [new LookupOption(value: '1', label: 'Jakarta', code: 'JKT', meta: ['branchId' => 1])],
            hasMore: true,
        );

        $this->assertSame([
            'options' => [['value' => '1', 'label' => 'Jakarta', 'code' => 'JKT', 'meta' => ['branchId' => 1]]],
            'hasMore' => true,
        ], json_decode(json_encode($page), true));
    }
}
