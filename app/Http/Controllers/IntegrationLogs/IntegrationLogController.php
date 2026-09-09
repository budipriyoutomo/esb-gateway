<?php

namespace App\Http\Controllers\IntegrationLogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntegrationLogs\ListIntegrationLogsRequest;
use App\Integrations\ESB\EsbFamily;
use App\Models\IntegrationLog;
use App\Support\IntegrationOutcome;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationLogController extends Controller
{
    private const PER_PAGE = 25;

    public function index(ListIntegrationLogsRequest $request): Response
    {
        $filters = $request->filters();

        $logs = IntegrationLog::query()
            ->with('user:id,name')
            ->when(
                $filters['correlationId'] ?? null,
                fn ($query, string $value) => $query->where('correlation_id', $value),
            )
            ->when(
                $filters['operation'] ?? null,
                fn ($query, string $value) => $query->where('operation', 'like', '%'.$value.'%'),
            )
            ->when(
                $filters['outcome'] ?? null,
                fn ($query, string $value) => $query->where('outcome', $value),
            )
            ->when(
                $filters['family'] ?? null,
                fn ($query, string $value) => $query->where('family', $value),
            )
            ->when(
                $filters['dateFrom'] ?? null,
                fn ($query, string $value) => $query->where('created_at', '>=', $value.' 00:00:00'),
            )
            ->when(
                $filters['dateTo'] ?? null,
                fn ($query, string $value) => $query->where('created_at', '<=', $value.' 23:59:59'),
            )
            ->latest('created_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (IntegrationLog $log): array => [
                'id' => $log->id,
                'correlationId' => $log->correlation_id,
                'operation' => $log->operation,
                'family' => $log->family,
                'environment' => $log->environment,
                'method' => $log->method,
                // The path is stored without a host, so the viewer cannot
                // become a way to discover the ESB (ADR-006).
                'path' => $log->path,
                'outcome' => $log->outcome,
                'esbCode' => $log->esb_code,
                'message' => $log->safe_error_message,
                'upstreamStatus' => $log->upstream_status,
                'durationMs' => $log->duration_ms,
                'retryCount' => $log->retry_count,
                'user' => $log->user?->name,
                'createdAt' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('integration-logs/index', [
            'logs' => $logs,
            'filters' => $filters,
            'outcomes' => IntegrationOutcome::all(),
            'families' => array_column(EsbFamily::cases(), 'value'),
        ]);
    }
}
