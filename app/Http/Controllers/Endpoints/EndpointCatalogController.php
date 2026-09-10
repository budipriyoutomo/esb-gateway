<?php

namespace App\Http\Controllers\Endpoints;

use App\Http\Controllers\Controller;
use App\Http\Requests\Endpoints\ListEndpointsRequest;
use App\Models\IntegrationEndpoint;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only browser over the imported ESB catalog.
 *
 * Deliberately has no write actions: the catalog mirrors the vendor
 * documentation in docs/esb/, and editing a row here would create a second
 * source of truth that silently disagrees with it.
 */
class EndpointCatalogController extends Controller
{
    private const PER_PAGE = 50;

    public function index(ListEndpointsRequest $request): Response
    {
        $filters = $request->filters();

        $endpoints = IntegrationEndpoint::query()
            ->when(
                $filters['search'] ?? null,
                fn ($query, string $value) => $query->where(function ($query) use ($value): void {
                    $query->where('name', 'like', '%'.$value.'%')
                        ->orWhere('path', 'like', '%'.$value.'%')
                        ->orWhere('group', 'like', '%'.$value.'%');
                }),
            )
            ->when($filters['product'] ?? null, fn ($query, string $value) => $query->where('product', $value))
            ->when($filters['group'] ?? null, fn ($query, string $value) => $query->where('group', $value))
            ->when($filters['method'] ?? null, fn ($query, string $value) => $query->where('method', $value))
            ->when($filters['family'] ?? null, fn ($query, string $value) => $query->where('family', $value))
            ->orderBy('product')
            ->orderBy('group')
            ->orderBy('path')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (IntegrationEndpoint $endpoint): array => [
                'id' => $endpoint->id,
                'code' => $endpoint->code,
                'product' => $endpoint->product,
                'group' => $endpoint->group,
                'name' => $endpoint->name,
                'version' => $endpoint->version,
                'method' => $endpoint->method,
                'path' => $endpoint->path,
                'family' => $endpoint->family,
                'isOperation' => $endpoint->is_operation,
                'docsUrl' => $endpoint->docs_url,
            ]);

        return Inertia::render('endpoints/index', [
            'endpoints' => $endpoints,
            'filters' => $filters,
            // Only options the catalog actually holds -- offering an empty
            // group would be a dead end.
            'groups' => IntegrationEndpoint::query()->distinct()->orderBy('group')->pluck('group'),
            'methods' => IntegrationEndpoint::query()
                ->whereNotNull('method')
                ->distinct()
                ->orderBy('method')
                ->pluck('method'),
            'families' => IntegrationEndpoint::query()->distinct()->orderBy('family')->pluck('family'),
        ]);
    }
}
