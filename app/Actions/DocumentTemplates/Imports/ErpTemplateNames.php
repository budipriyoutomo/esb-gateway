<?php

namespace App\Actions\DocumentTemplates\Imports;

use App\Domain\Canonical\DocumentTemplateQuery;
use App\Domain\Canonical\DocumentTemplateSummary;
use App\Integrations\Contracts\DocumentTemplateSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Whether the ERP already has a template by a name (ADR-023).
 *
 * The owner's rule (2026-10-15): a name the ERP already has stops that
 * template, with the reason. Names compare ignoring case and surrounding
 * space; whether the ESB's own filter matches partially is undocumented, so
 * its rows are compared here.
 */
class ErpTemplateNames
{
    public function __construct(private readonly DocumentTemplateSource $templates) {}

    /**
     * @throws EsbException
     */
    public function find(string $name): ?DocumentTemplateSummary
    {
        $page = $this->templates->listDocumentTemplates(new DocumentTemplateQuery(
            limit: DocumentTemplateQuery::MAX_LIMIT,
            name: $name,
        ));

        $wanted = mb_strtolower(trim($name));

        foreach ($page->templates as $template) {
            if ($template->id !== null && mb_strtolower(trim($template->name)) === $wanted) {
                return $template;
            }
        }

        return null;
    }

    public static function takenMessage(DocumentTemplateSummary $existing): string
    {
        return sprintf(
            'A template named "%s" already exists in the ERP (id %d). Rename the template in the file, or check it in the ERP.',
            $existing->name,
            $existing->id,
        );
    }
}
