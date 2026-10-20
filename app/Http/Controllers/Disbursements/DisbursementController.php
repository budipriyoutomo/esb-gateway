<?php

namespace App\Http\Controllers\Disbursements;

use App\Domain\Canonical\DisbursementQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Disbursements\ListDisbursementsRequest;
use App\Integrations\Contracts\DisbursementSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Disbursement list and detail ([ESB-037]). Authorization (esb.invoke) is
 * on the form request for the list and on the route for the detail.
 */
class DisbursementController extends Controller
{
    /**
     * The detail path shares its segment with the vendor's literal
     * /finance/disbursement/import, so a number must contain a digit.
     */
    public const NUMBER = '/^(?=.*[0-9])[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/';

    public function index(ListDisbursementsRequest $request, DisbursementSource $disbursements): Response
    {
        $page = $disbursements->listDisbursements($request->toQuery());

        return Inertia::render('disbursements/index', [
            'disbursements' => $page->disbursements,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
            'statuses' => self::statuses(),
        ]);
    }

    public function show(string $number, DisbursementSource $disbursements): Response
    {
        self::assertUsableNumber($number);

        $disbursement = $disbursements->findDisbursement($number);

        abort_if($disbursement === null, 404);

        return Inertia::render('disbursements/show', [
            'disbursement' => $disbursement,
        ]);
    }

    public static function assertUsableNumber(string $number): void
    {
        abort_unless(preg_match(self::NUMBER, $number) === 1, 404);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public static function statuses(): array
    {
        return array_map(
            static fn (int $id, string $name): array => ['id' => $id, 'name' => $name],
            array_keys(DisbursementQuery::STATUSES),
            DisbursementQuery::STATUSES,
        );
    }
}
