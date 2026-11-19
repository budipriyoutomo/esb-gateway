<?php

namespace App\Http\Controllers\VisitPurposes;

use App\Actions\VisitPurposes\SaveVisitPurpose;
use App\Authorization\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisitPurposes\SaveVisitPurposeRequest;
use App\Models\ClosingRecap;
use App\Models\VisitPurpose;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Visit purposes kept in the gateway ([ESB-052]): the official list of the
 * visitPurposeName values closing recaps carry. Seeing them takes
 * closing-recaps.view (route middleware); changing them takes
 * closing-mappings.manage (the form request, and here for deletes).
 */
class VisitPurposeController extends Controller
{
    public function __construct(private readonly SaveVisitPurpose $save) {}

    /**
     * Each registered purpose with the number of recaps that carry it, and
     * the names recaps carry that are not registered. The recaps are few
     * per outlet and day, and only their sales column is read.
     */
    public function index(Request $request): Response
    {
        /** @var array<string, array{name: string, recaps: int}> $seen */
        $seen = [];

        foreach (ClosingRecap::query()->whereNotNull('sales')->select(['id', 'sales'])->lazyById(200) as $recap) {
            $inRecap = [];

            foreach ($recap->sales ?? [] as $sales) {
                $name = trim((string) ($sales['visitPurposeName'] ?? ''));
                $key = VisitPurpose::keyOf($name);

                if ($name === '' || isset($inRecap[$key])) {
                    continue;
                }

                $inRecap[$key] = true;
                $seen[$key] ??= ['name' => $name, 'recaps' => 0];
                $seen[$key]['recaps']++;
            }
        }

        $registered = VisitPurpose::query()->orderBy('name_key')->get();

        $visitPurposes = $registered->map(fn (VisitPurpose $purpose): array => [
            ...$purpose->toView(),
            'recaps' => $seen[$purpose->name_key]['recaps'] ?? 0,
        ])->all();

        $unregistered = array_diff_key($seen, $registered->keyBy('name_key')->all());
        ksort($unregistered);

        return Inertia::render('visit-purposes/index', [
            'canManage' => $request->user()?->can(Permission::ManageClosingMappings->value) ?? false,
            'visitPurposes' => $visitPurposes,
            'unregistered' => array_values($unregistered),
        ]);
    }

    public function store(SaveVisitPurposeRequest $request): RedirectResponse
    {
        $this->save->save(new VisitPurpose, $request->values(), $request->user()?->id);

        $this->toast('success', 'Visit purpose added.');

        return back();
    }

    public function update(SaveVisitPurposeRequest $request, VisitPurpose $visitPurpose): RedirectResponse
    {
        $this->save->save($visitPurpose, $request->values(), $request->user()?->id);

        $this->toast('success', 'Visit purpose saved.');

        return back();
    }

    public function destroy(Request $request, VisitPurpose $visitPurpose): RedirectResponse
    {
        abort_unless($request->user()?->can(Permission::ManageClosingMappings->value) ?? false, 403);

        $this->save->delete($visitPurpose);

        $this->toast('success', 'Visit purpose removed.');

        return back();
    }
}
