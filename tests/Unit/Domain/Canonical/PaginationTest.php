<?php

namespace Tests\Unit\Domain\Canonical;

use App\Domain\Canonical\Pagination;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pagination arithmetic that belongs to no particular provider.
 *
 * Promoted out of EsbPagination, which keeps parsing the ESB's own list
 * response but no longer owns the page maths every provider needs.
 */
class PaginationTest extends TestCase
{
    #[Test]
    public function it_derives_the_last_page_from_count_and_limit(): void
    {
        $this->assertSame(5, (new Pagination(page: 1, limit: 20, count: 92))->lastPage());
        $this->assertSame(4, (new Pagination(page: 1, limit: 20, count: 80))->lastPage());
    }

    #[Test]
    public function an_empty_result_still_has_one_page(): void
    {
        $this->assertSame(1, (new Pagination(page: 1, limit: 20, count: 0))->lastPage());
    }

    #[Test]
    public function a_nonsensical_limit_does_not_divide_by_zero(): void
    {
        $this->assertSame(1, (new Pagination(page: 1, limit: 0, count: 92))->lastPage());
    }

    #[Test]
    public function it_reports_whether_more_pages_follow(): void
    {
        $this->assertTrue((new Pagination(page: 1, limit: 20, count: 92))->hasMorePages());
        $this->assertFalse((new Pagination(page: 5, limit: 20, count: 92))->hasMorePages());
    }

    #[Test]
    public function it_serializes_the_shape_the_frontend_already_reads(): void
    {
        $this->assertSame([
            'page' => 2,
            'limit' => 20,
            'count' => 92,
            'lastPage' => 5,
            'hasMorePages' => true,
        ], (new Pagination(page: 2, limit: 20, count: 92))->jsonSerialize());
    }
}
