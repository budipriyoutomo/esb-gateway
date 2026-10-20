<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Canonical\InventoryKind;
use App\Http\Concerns\ResolvesInventoryKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ListInventoryDocumentsRequest;
use App\Integrations\Contracts\InventoryDocumentSource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * List and detail of the inventory documents ([ESB-033], ADR-026). The kind
 * is a route default, so each document keeps its own URLs and route names.
 */
class InventoryDocumentController extends Controller
{
    use ResolvesInventoryKind;

    /**
     * Every detail path shares its segment with literal vendor paths
     * (/goods-receipt/initialize, /goods-delivery/create, ...), so a number
     * must contain a digit.
     */
    public const NUMBER = '/^(?=.*[0-9])[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/';

    public function index(ListInventoryDocumentsRequest $request, InventoryDocumentSource $documents): Response
    {
        $kind = $this->inventoryKind($request);
        $page = $documents->listDocuments($kind, $request->toQuery());

        return Inertia::render('inventory/index', [
            'kind' => self::describe($kind),
            'documents' => $page->documents,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(string $number, Request $request, InventoryDocumentSource $documents): Response
    {
        $kind = $this->inventoryKind($request);

        abort_unless(preg_match(self::NUMBER, $number) === 1, 404);

        $document = $documents->findDocument($kind, $number);

        abort_if($document === null, 404);

        return Inertia::render('inventory/show', [
            'kind' => self::describe($kind),
            'document' => $document,
        ]);
    }

    /**
     * What the pages need to know about a kind.
     *
     * @return array<string, mixed>
     */
    public static function describe(InventoryKind $kind): array
    {
        return [
            'value' => $kind->value,
            'label' => $kind->label(),
            'pluralLabel' => $kind->pluralLabel(),
            'statuses' => array_map(
                static fn (int $id, string $name): array => ['id' => $id, 'name' => $name],
                array_keys($kind->statuses()),
                $kind->statuses(),
            ),
            'filtersByBranch' => $kind->filtersByBranch(),
            'searchable' => $kind->searchable(),
            'rejectTakesReason' => $kind->rejectTakesReason(),
            'editable' => $kind->editable(),
            'attachments' => $kind->takesAttachments() ? [
                'uploadReplaces' => $kind->attachmentUploadReplaces(),
                'removable' => $kind->attachmentsRemovable(),
                'extensions' => $kind->attachmentExtensions(),
            ] : null,
        ];
    }
}
