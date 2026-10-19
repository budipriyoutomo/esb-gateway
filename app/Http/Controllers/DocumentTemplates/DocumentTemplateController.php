<?php

namespace App\Http\Controllers\DocumentTemplates;

use App\Domain\Canonical\DocumentTemplateType;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentTemplates\ListDocumentTemplatesRequest;
use App\Integrations\Contracts\DocumentTemplateSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Document template list and detail, read-only ([ESB-016]).
 *
 * Depends on the contract, not on the ESB adapter (ADR-016). The id reaching
 * show() has already been constrained by the route. A not-found refusal from
 * the ESB is rendered as a 404 by EsbExceptionRenderer.
 */
class DocumentTemplateController extends Controller
{
    public function index(ListDocumentTemplatesRequest $request, DocumentTemplateSource $templates): Response
    {
        $page = $templates->listDocumentTemplates($request->toQuery());

        return Inertia::render('document-templates/index', [
            'templates' => $page->templates,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
            'types' => DocumentTemplateType::options(),
        ]);
    }

    public function show(int $templateId, DocumentTemplateSource $templates): Response
    {
        $template = $templates->findDocumentTemplate($templateId);

        abort_if($template === null, 404);

        return Inertia::render('document-templates/show', [
            'template' => $template,
        ]);
    }
}
