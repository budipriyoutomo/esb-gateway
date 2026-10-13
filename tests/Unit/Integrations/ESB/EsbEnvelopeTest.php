<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Responses\EsbEnvelope;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every documented ESB operation returns the same envelope. See
 * docs/05-api-integration.md for the field meanings and why `code` is the only
 * reliable discriminator.
 */
class EsbEnvelopeTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function successBody(): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core/product/list',
            'timestamp' => '2024-05-28 18:20:51',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'page' => 1,
                'limit' => 20,
                'count' => 92,
                'data' => [['productID' => 75, 'productName' => 'Air mineral']],
                'prev' => '',
                'next' => 'https://stg7.esb.co.id/core/product/list?page=2&limit=20',
            ],
            'errors' => null,
        ];
    }

    #[Test]
    public function it_reads_the_envelope_fields(): void
    {
        $envelope = EsbEnvelope::fromArray($this->successBody());

        $this->assertSame('EC03100000', $envelope->code);
        $this->assertSame('OK', $envelope->message);
        $this->assertSame('https://stg7.esb.co.id/core/product/list', $envelope->path);
        $this->assertTrue($envelope->isSuccessful());
    }

    #[Test]
    public function success_is_decided_by_code_not_by_status(): void
    {
        // The vendor's own examples use "ok", "Ok" and "OK" for the message,
        // and both "fail" and "failed" for status. Some examples even put
        // unrelated domain values in status. Only `code` is dependable.
        $body = $this->successBody();
        $body['status'] = 'failed';

        $this->assertTrue(EsbEnvelope::fromArray($body)->isSuccessful());

        $body = $this->successBody();
        $body['status'] = 'ok';
        $body['code'] = 'EC03100003';

        $this->assertFalse(EsbEnvelope::fromArray($body)->isSuccessful());
    }

    #[Test]
    public function the_fnb_backend_reports_success_as_code_200(): void
    {
        // Push Sales Data V2 (FnB backend family) answers with an HTTP-style
        // code, as a string, instead of EC03100000. Its failures use the
        // number 400 or 401 (docs/05-api-integration.md, [ESB-011]).
        $body = [
            'path' => 'http://stg7.esb.co.id/api-fnb-backend-int/web/extv1/push/sales-data',
            'timestamp' => '2024-10-01 16:42:46',
            'code' => '200',
            'status' => 'ok',
            'message' => null,
            'result' => ['status' => '00', 'syncDate' => '2024-10-01 16:42:46'],
        ];

        $this->assertTrue(EsbEnvelope::fromArray($body)->isSuccessful());

        $body['code'] = 200;
        $this->assertTrue(EsbEnvelope::fromArray($body)->isSuccessful());

        $body['code'] = 400;
        $this->assertFalse(EsbEnvelope::fromArray($body)->isSuccessful());
    }

    #[Test]
    public function the_oms_master_operations_report_success_as_ec011200(): void
    {
        // ESB OMS Master Menu ([ESB-013]) answers with its own success code.
        $this->assertTrue(EsbEnvelope::fromArray(['code' => 'EC011200', 'status' => 'ok'])->isSuccessful());
    }

    #[Test]
    public function a_body_without_a_code_is_malformed(): void
    {
        $body = $this->successBody();
        unset($body['code']);

        $this->assertNull(EsbEnvelope::tryFromArray($body));
    }

    #[Test]
    public function it_exposes_pagination_from_a_list_result(): void
    {
        $pagination = EsbEnvelope::fromArray($this->successBody())->pagination();

        $this->assertNotNull($pagination);
        $this->assertSame(1, $pagination->page);
        $this->assertSame(20, $pagination->limit);
        $this->assertSame(92, $pagination->count);
        $this->assertSame(5, $pagination->lastPage());
        $this->assertTrue($pagination->hasMorePages());
    }

    #[Test]
    public function pagination_never_exposes_the_upstream_cursor_urls(): void
    {
        $pagination = EsbEnvelope::fromArray($this->successBody())->pagination();

        // The ESB returns absolute prev/next URLs containing its own host.
        // Passing those to the browser would leak the ESB host and invite
        // direct browser-to-ESB calls, which ADR-006 forbids.
        $encoded = json_encode($pagination);

        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('esb.co.id', $encoded);
        $this->assertStringNotContainsString('http', $encoded);
    }

    #[Test]
    public function a_non_list_result_has_no_pagination(): void
    {
        $body = $this->successBody();
        $body['result'] = ['productID' => 75];

        $this->assertNull(EsbEnvelope::fromArray($body)->pagination());
    }

    #[Test]
    public function it_reads_the_list_rows(): void
    {
        $rows = EsbEnvelope::fromArray($this->successBody())->rows();

        $this->assertCount(1, $rows);
        $this->assertSame('Air mineral', $rows[0]['productName']);
    }

    #[Test]
    public function it_parses_the_timestamp_as_jakarta_time(): void
    {
        $envelope = EsbEnvelope::fromArray($this->successBody());

        $timestamp = $envelope->timestampAsUtc();

        // The ESB sends "Y-m-d H:i:s" with no offset and means Asia/Jakarta.
        // Parsed as UTC it would land seven hours off
        // (docs/07-database-design.md).
        $this->assertNotNull($timestamp);
        $this->assertSame('2024-05-28 11:20:51', $timestamp->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $timestamp->getTimezone()->getName());
    }

    #[Test]
    public function an_unparseable_timestamp_is_null_rather_than_an_error(): void
    {
        $body = $this->successBody();
        $body['timestamp'] = 'not a date';

        // A malformed timestamp is a diagnostic annoyance, not a reason to
        // discard an otherwise valid business response.
        $this->assertNull(EsbEnvelope::fromArray($body)->timestampAsUtc());
    }
}
