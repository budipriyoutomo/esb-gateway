<?php

namespace App\Http\Requests\MasterData;

use App\Authorization\Permission;
use App\Domain\Canonical\MasterDataImport;
use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Concerns\ValidatesExcelUpload;

/**
 * A master data spreadsheet to hand to the ERP ([ESB-032]). Needs esb.invoke
 * and the module's own manage permission. 2 MB is the vendor's documented
 * limit for products and pricelists, and is applied to every kind (the
 * others document none).
 */
class ImportMasterDataRequest extends BaseFormRequest
{
    use ValidatesExcelUpload;

    public const MAX_KILOBYTES = 2048;

    public static function permissionFor(MasterDataImport $kind): Permission
    {
        return match ($kind) {
            MasterDataImport::Units => Permission::ManageUnits,
            MasterDataImport::ProductCategories => Permission::ManageProductCategories,
            MasterDataImport::ProductSubCategories => Permission::ManageProductSubCategories,
            MasterDataImport::NewProducts, MasterDataImport::ProductChanges => Permission::ManageProducts,
            MasterDataImport::Pricelists => Permission::ManagePricelists,
            MasterDataImport::NewSuppliers, MasterDataImport::SupplierChanges => Permission::ManageSuppliers,
            MasterDataImport::CoaAccounts => Permission::ManageCoa,
            MasterDataImport::SimpleTransfers => Permission::ManageSimpleTransfers,
            MasterDataImport::Disbursements => Permission::ManageDisbursements,
        };
    }

    public function kind(): MasterDataImport
    {
        $kind = $this->route('kind');

        return $kind instanceof MasterDataImport ? $kind : MasterDataImport::from((string) $kind);
    }

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, self::permissionFor($this->kind()));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['file' => $this->excelFileRules(self::MAX_KILOBYTES, $this->kind()->extensions())];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'The ERP accepts files of at most 2 MB.',
            'file.extensions' => $this->excelTypeMessage($this->kind()->extensions()),
        ];
    }

    public function safeFilename(): string
    {
        return $this->safeExcelFilename($this->kind()->value);
    }
}
