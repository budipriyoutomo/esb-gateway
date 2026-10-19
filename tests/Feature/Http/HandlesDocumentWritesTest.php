<?php

namespace Tests\Feature\Http;

use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\ApprovalDecision;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Integrations\ESB\EsbContext;
use App\Integrations\ESB\Exceptions\EsbBusinessRejectionException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use App\Integrations\ESB\Exceptions\EsbTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;

/**
 * How a decision or delete outcome becomes a toast and a redirect, for every
 * approvable document. The per-module feature tests prove the wiring.
 */
class HandlesDocumentWritesTest extends TestCase
{
    private const NUMBER = 'W-0001';

    protected function setUp(): void
    {
        parent::setUp();

        FakeDocumentWriteController::$throw = null;

        Route::middleware('web')->group(function (): void {
            Route::get('test-widgets', fn () => 'index')->name('test-widgets.index');
            Route::get('test-widgets/{number}', fn () => 'show')->name('test-widgets.show');
            Route::post('test-widgets/{number}/{decision}', [FakeDocumentWriteController::class, 'decide']);
            Route::delete('test-widgets/{number}', [FakeDocumentWriteController::class, 'destroy']);
        });

        Route::getRoutes()->refreshNameLookups();
    }

    private static function context(): EsbContext
    {
        return new EsbContext(correlationId: 'corr-123', operation: 'test');
    }

    private function show(): string
    {
        return route('test-widgets.show', self::NUMBER);
    }

    private function index(): string
    {
        return route('test-widgets.index');
    }

    // -------------------------------------------------------------- decide

    #[Test]
    public function an_authorization_is_reported_on_the_detail_page(): void
    {
        $this->post('test-widgets/'.self::NUMBER.'/authorize')
            ->assertRedirect($this->show())
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'success',
                'message' => 'Test widget W-0001 was authorized.',
            ]);
    }

    #[Test]
    public function a_rejection_is_reported_on_the_detail_page(): void
    {
        $this->post('test-widgets/'.self::NUMBER.'/reject')
            ->assertRedirect($this->show())
            ->assertSessionHas('inertia.flash_data.toast.message', 'Test widget W-0001 was rejected.');
    }

    #[Test]
    public function a_decision_in_flight_asks_to_check_the_status(): void
    {
        FakeDocumentWriteController::$throw = new WriteAlreadyInFlight('test-widget-write:W-0001');

        $this->post('test-widgets/'.self::NUMBER.'/authorize')
            ->assertRedirect($this->show())
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'warning',
                'message' => 'Another change to W-0001 is still being processed. Check its status before trying again.',
            ]);
    }

    #[Test]
    public function an_unconfirmed_decision_warns_with_the_reference(): void
    {
        FakeDocumentWriteController::$throw = new EsbTimeoutException(self::context());

        $this->post('test-widgets/'.self::NUMBER.'/reject')
            ->assertRedirect($this->show())
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'warning',
                'message' => 'The ERP did not confirm the request to reject W-0001. It may or may not have been applied -- check the status below before trying again. (reference: corr-123)',
            ]);
    }

    #[Test]
    public function a_refused_decision_is_an_error_with_the_safe_message(): void
    {
        $exception = new EsbBusinessRejectionException(self::context());
        FakeDocumentWriteController::$throw = $exception;

        $this->post('test-widgets/'.self::NUMBER.'/authorize')
            ->assertRedirect($this->show())
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'error',
                'message' => 'Could not authorize W-0001. '.$exception->safeMessage(),
            ]);
    }

    #[Test]
    public function an_unknown_document_is_left_to_the_404_handler(): void
    {
        FakeDocumentWriteController::$throw = new EsbNotFoundException(self::context());

        $this->post('test-widgets/'.self::NUMBER.'/authorize')->assertNotFound();
    }

    // -------------------------------------------------------------- delete

    #[Test]
    public function a_delete_returns_to_the_list(): void
    {
        $this->delete('test-widgets/'.self::NUMBER)
            ->assertRedirect($this->index())
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'success',
                'message' => 'Test widget W-0001 was deleted.',
            ]);
    }

    #[Test]
    public function a_delete_in_flight_stays_on_the_detail_page(): void
    {
        FakeDocumentWriteController::$throw = new WriteAlreadyInFlight('test-widget-write:W-0001');

        $this->delete('test-widgets/'.self::NUMBER)
            ->assertRedirect($this->show())
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'warning',
                'message' => 'Another change to W-0001 is still being processed. Check it before trying again.',
            ]);
    }

    #[Test]
    public function an_unconfirmed_delete_returns_to_the_list_with_the_reference(): void
    {
        FakeDocumentWriteController::$throw = new EsbTimeoutException(self::context());

        $this->delete('test-widgets/'.self::NUMBER)
            ->assertRedirect($this->index())
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'warning',
                'message' => 'The ERP did not confirm deleting W-0001. It may or may not be gone -- check the list before trying again. (reference: corr-123)',
            ]);
    }

    #[Test]
    public function a_refused_delete_stays_on_the_detail_page(): void
    {
        $exception = new EsbBusinessRejectionException(self::context());
        FakeDocumentWriteController::$throw = $exception;

        $this->delete('test-widgets/'.self::NUMBER)
            ->assertRedirect($this->show())
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'error',
                'message' => 'Could not delete W-0001. '.$exception->safeMessage(),
            ]);
    }

    #[Test]
    public function an_unknown_document_on_delete_is_left_to_the_404_handler(): void
    {
        FakeDocumentWriteController::$throw = new EsbNotFoundException(self::context());

        $this->delete('test-widgets/'.self::NUMBER)->assertNotFound();
    }
}

class FakeDocumentWriteController extends Controller
{
    use HandlesDocumentWrites;

    public static ?Throwable $throw = null;

    public function decide(string $number, string $decision): RedirectResponse
    {
        return $this->decisionOutcome(
            'Test widget', 'test-widgets', $number, ApprovalDecision::from($decision),
            fn () => self::fail(),
        );
    }

    public function destroy(string $number): RedirectResponse
    {
        return $this->deleteOutcome('Test widget', 'test-widgets', $number, fn () => self::fail());
    }

    private static function fail(): void
    {
        if (self::$throw !== null) {
            throw self::$throw;
        }
    }
}
