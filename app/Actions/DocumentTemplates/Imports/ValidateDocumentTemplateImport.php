<?php

namespace App\Actions\DocumentTemplates\Imports;

use App\Domain\Canonical\ProductDetailOption;
use App\Domain\Canonical\ProductUnits;
use App\Integrations\Contracts\DocumentTemplateSource;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\DocumentTemplateImport;
use App\Models\DocumentTemplateImportEntry;

/**
 * Check an import's templates against the ERP, reading only (ADR-023).
 *
 * For each template that can still be checked: every branch code must be an
 * active branch, every product code + unit must be one ERP product detail,
 * and the name must not be in the ERP yet. A template passing all three is
 * ready; otherwise it is blocked with every reason found, so the file can be
 * fixed in one pass.
 *
 * An unconfirmed template whose name the ERP now has was created after all,
 * and is settled as synced.
 *
 * The caller has claimed the import (status validating); it is released here.
 */
class ValidateDocumentTemplateImport
{
    /**
     * Products per upper-cased code, so a code shared by several templates
     * is looked up once per run.
     *
     * @var array<string, list<ProductUnits>>
     */
    private array $products = [];

    public function __construct(
        private readonly DocumentTemplateSource $templates,
        private readonly PurchaseRequestSource $branches,
        private readonly ErpTemplateNames $names,
    ) {}

    public function handle(DocumentTemplateImport $import): void
    {
        $this->products = [];

        try {
            $branchIds = $this->branchIdsByCode();

            $entries = $import->entries()
                ->whereIn('status', DocumentTemplateImportEntry::CHECKABLE)
                ->with('lines')
                ->get();

            foreach ($entries as $entry) {
                $this->check($entry, $branchIds);
            }
        } catch (EsbException $exception) {
            // safeMessage() already ends with the reference.
            $import->release(['last_error' => 'Validation stopped: '.$exception->safeMessage()]);

            return;
        }

        $import->release(['validated_at' => now()]);
    }

    /**
     * @param  array<string, int>  $branchIds
     *
     * @throws EsbException
     */
    private function check(DocumentTemplateImportEntry $entry, array $branchIds): void
    {
        $existing = $this->names->find($entry->name);

        if ($existing !== null && $entry->status === DocumentTemplateImportEntry::STATUS_UNCONFIRMED) {
            $entry->update([
                'status' => DocumentTemplateImportEntry::STATUS_SYNCED,
                'esb_template_id' => $existing->id,
                'problems' => [],
                'synced_at' => now(),
            ]);

            return;
        }

        $problems = [];

        if ($existing !== null) {
            $problems[] = ErpTemplateNames::takenMessage($existing);
        }

        $resolvedBranches = [];

        foreach ($entry->branch_codes as $code) {
            if (isset($branchIds[$code])) {
                $resolvedBranches[] = $branchIds[$code];
            } else {
                $problems[] = sprintf('Branch code %s is not an active branch in the ERP.', $code);
            }
        }

        $rowsByDetail = [];

        foreach ($entry->lines as $line) {
            [$detailId, $problem] = $this->resolveProduct($line->row_number, $line->product_code, $line->unit);

            if ($detailId !== null && isset($rowsByDetail[$detailId])) {
                $problem = sprintf('Row %d: is the same ERP product as row %d.', $line->row_number, $rowsByDetail[$detailId]);
            } elseif ($detailId !== null) {
                $rowsByDetail[$detailId] = $line->row_number;
            }

            if ($problem !== null) {
                $problems[] = $problem;
            }

            $line->update(['product_detail_id' => $detailId]);
        }

        $entry->update([
            'status' => $problems === []
                ? DocumentTemplateImportEntry::STATUS_READY
                : DocumentTemplateImportEntry::STATUS_BLOCKED,
            'branch_ids' => $resolvedBranches,
            'problems' => $problems,
        ]);
    }

    /**
     * @return array{0: int|null, 1: string|null} The product detail id, or why there is none.
     *
     * @throws EsbException
     */
    private function resolveProduct(int $row, string $code, string $unit): array
    {
        $key = strtoupper($code);
        $this->products[$key] ??= $this->templates->findProductsByCode($code);

        if ($this->products[$key] === []) {
            return [null, sprintf('Row %d: product code %s is not in the ERP.', $row, $code)];
        }

        // An inactive product cannot go on a template, whatever its units.
        $active = array_values(array_filter(
            $this->products[$key],
            static fn (ProductUnits $product): bool => $product->active !== false,
        ));

        if ($active === []) {
            return [null, sprintf('Row %d: product %s is inactive in the ERP.', $row, $code)];
        }

        $options = array_merge(...array_map(static fn (ProductUnits $product): array => $product->units, $active));

        foreach ($options as $option) {
            if ($this->sameUnit((string) $option->uomName, $unit)) {
                return [$option->productDetailId, null];
            }
        }

        $units = array_values(array_unique(array_filter(array_map(
            static fn (ProductDetailOption $option): ?string => $option->uomName,
            $options,
        ))));

        return [null, sprintf(
            'Row %d: product %s has no unit "%s" in the ERP (it has: %s).',
            $row,
            $code,
            $unit,
            $units === [] ? 'none' : implode(', ', $units),
        )];
    }

    /**
     * Units compare ignoring case and spacing ("BTL @0.946 LT" is
     * "btl @ 0.946 lt"); nothing looser, so a near miss is reported, not
     * guessed.
     */
    private function sameUnit(string $erp, string $sheet): bool
    {
        $normalize = static fn (string $unit): string => strtolower((string) preg_replace('/\s+/', '', $unit));

        return $normalize($erp) === $normalize($sheet);
    }

    /**
     * @return array<string, int> Upper-cased branch code => id.
     *
     * @throws EsbException
     */
    private function branchIdsByCode(): array
    {
        $ids = [];

        foreach ($this->branches->listBranches() as $branch) {
            if ($branch->code !== null && $branch->code !== '') {
                $ids[strtoupper(trim($branch->code))] = $branch->id;
            }
        }

        return $ids;
    }
}
