<?php

namespace Tests\Fixtures;

/**
 * The ESB Core response envelope around a fixture's rows, as the vendor's
 * OpenAPI documents it.
 */
trait EsbEnvelope
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private static function paginated(string $path, array $rows, int $count, int $page, int $limit = 20): array
    {
        return self::envelope($path, [
            'count' => $count,
            'data' => $rows,
            'limit' => $limit,
            'next' => '',
            'page' => $page,
            'prev' => '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function envelope(string $path, mixed $result): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int'.$path,
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => $result,
            'errors' => null,
        ];
    }

    /**
     * EC03100004, as the Core details answer an unknown id.
     *
     * @return array<string, mixed>
     */
    public static function notFoundResponse(string $path): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int'.$path,
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'fail',
            'code' => 'EC03100004',
            'message' => 'Not Found',
            'result' => null,
            'errors' => null,
        ];
    }
}
