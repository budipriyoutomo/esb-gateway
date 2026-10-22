<?php

namespace App\Http\Requests\Promotions;

use App\Authorization\Permission;
use App\Domain\Canonical\PromotionQuery;
use App\Http\Requests\BaseFormRequest;

/**
 * The promotion list's filters ([ESB-049]): the branch and promotion codes
 * the vendor documents, within its sizes (String(20)), and the gateway's
 * page.
 */
class ListPromotionsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'branchCode' => ['sometimes', 'nullable', 'string', 'max:20'],
            'promotionCode' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }

    public function toQuery(): PromotionQuery
    {
        return new PromotionQuery(
            page: $this->validatedInt('page') ?? 1,
            branchCode: $this->validatedString('branchCode'),
            promotionCode: $this->validatedString('promotionCode'),
        );
    }

    /**
     * The filters echoed back to the page.
     *
     * @return array<string, int|string>
     */
    public function validatedFilters(): array
    {
        $query = $this->toQuery();

        return array_filter([
            'page' => $query->page,
            'branchCode' => $query->branchCode,
            'promotionCode' => $query->promotionCode,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
