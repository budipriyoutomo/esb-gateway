<?php

namespace App\Http\Controllers\DocumentTemplates;

use App\Actions\DocumentTemplates\CreateDocumentTemplate;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\DocumentTemplateType;
use App\Domain\Canonical\PurposeQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentTemplates\DocumentTemplateFormQuery;
use App\Http\Requests\DocumentTemplates\SaveDocumentTemplateRequest;
use App\Integrations\Contracts\DocumentTemplateSource;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\Contracts\PurposeSource;
use App\Integrations\ESB\Exceptions\EsbException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Create a document template ([ESB-016]).
 *
 * The screen's lookups are closures so an Inertia partial reload -- a new
 * product search -- asks the ESB only for products, not for branches and
 * purposes again. The branch list is the one the purchase request screen
 * already reads (GET /branch); it lives on PurchaseRequestSource for now.
 *
 * Where each outcome lands:
 * - success: the new template, or the list when the ERP returned no id;
 * - a definite refusal: back to the form, with the ESB's field errors on the
 *   fields they name, so nothing typed is lost;
 * - an unconfirmed create: the list, where the user can see whether it exists.
 */
class DocumentTemplateFormController extends Controller
{
    /**
     * The vendor's request field names, and the form fields they belong to.
     * Matched on the name before any index or dot, so
     * "requestTemplateDetails[0].productDetailID" lands on the lines. An
     * error on anything else is shown for the form as a whole.
     */
    private const FIELD_MAP = [
        'requestTemplateName' => 'name',
        'requestTemplateTypeID' => 'type',
        'flagActive' => 'active',
        'branchIDs' => 'branchIds',
        'requestTemplateDetails' => 'lines',
    ];

    public function create(
        DocumentTemplateFormQuery $query,
        PurchaseRequestSource $branches,
        PurposeSource $purposes,
        DocumentTemplateSource $templates,
    ): Response {
        $search = $query->productSearch();

        return Inertia::render('document-templates/create', [
            'types' => DocumentTemplateType::options(),
            'branches' => fn (): array => $branches->listBranches(),
            'purposes' => fn (): array => $purposes->listPurposes(new PurposeQuery(
                limit: PurposeQuery::MAX_LIMIT,
                activeOnly: true,
            ))->purposes,
            'products' => fn (): array => $search === null ? [] : $templates->browseProductDetails($search),
            'productSearch' => $search,
        ]);
    }

    public function store(SaveDocumentTemplateRequest $request, CreateDocumentTemplate $create): RedirectResponse
    {
        try {
            $id = $create->handle($request->toDraft());
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', 'Your previous document template is still being created. Check the list before trying again.');

            return redirect()->route('document-templates.index');
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm the new document template. It may or may not have been created -- check the list before trying again. (reference: %s)',
                    $exception->context->correlationId,
                ));

                return redirect()->route('document-templates.index');
            }

            throw $this->asFormErrors($exception);
        }

        $this->toast('success', 'The document template was created.');

        return $id === null
            ? redirect()->route('document-templates.index')
            : redirect()->route('document-templates.show', $id);
    }

    /**
     * A definite refusal, as validation errors on the form. The ESB's
     * per-field messages are meant for whoever filled the form; without any,
     * the safe summary (with its reference) is shown instead.
     */
    private function asFormErrors(EsbException $exception): ValidationException
    {
        $messages = [];

        foreach ($exception->fieldErrors as $fieldError) {
            $base = preg_split('/[.\[]/', $fieldError->field ?? '')[0] ?? '';
            $key = self::FIELD_MAP[$base] ?? 'form';
            $messages[$key][] = $fieldError->message;
        }

        if ($messages === []) {
            $messages['form'] = [$exception->safeMessage()];
        }

        // Inertia shares one message per field, so several for the same
        // field are joined rather than all but the first dropped.
        return ValidationException::withMessages(array_map(
            static fn (array $forField): string => implode(' ', array_unique($forField)),
            $messages,
        ));
    }
}
