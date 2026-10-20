<?php

namespace Tests\Feature\Http;

use App\Actions\Support\RecordInUse;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Http\Controllers\Concerns\HandlesRecordWrites;
use App\Http\Controllers\Controller;
use App\Integrations\ESB\EsbContext;
use App\Integrations\ESB\Exceptions\EsbBusinessRejectionException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use App\Integrations\ESB\Exceptions\EsbTimeoutException;
use App\Integrations\ESB\Exceptions\EsbValidationException;
use App\Integrations\ESB\Responses\EsbFieldError;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;

/**
 * How a master data write outcome becomes a toast and a redirect.
 */
class HandlesRecordWritesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FakeRecordWriteController::$throw = null;

        Route::middleware('web')->group(function (): void {
            Route::get('test-things', fn () => 'index')->name('test-things.index');
            Route::get('test-things/{id}', fn () => 'show')->name('test-things.show');
            Route::post('test-things/{id}/{operation}', [FakeRecordWriteController::class, 'write']);
        });

        Route::getRoutes()->refreshNameLookups();
    }

    private static function context(): EsbContext
    {
        return new EsbContext(correlationId: 'corr-9', operation: 'test');
    }

    private function send(string $operation)
    {
        return $this->post("test-things/3/{$operation}");
    }

    #[Test]
    public function a_deactivation_or_restore_stays_on_the_record(): void
    {
        $this->send('deactivate')
            ->assertRedirect(route('test-things.show', 3))
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'success',
                'message' => 'Thing Bahan Baku was deactivated.',
            ]);

        $this->send('restore')
            ->assertSessionHas('inertia.flash_data.toast.message', 'Thing Bahan Baku was restored.');
    }

    #[Test]
    public function a_delete_returns_to_the_list(): void
    {
        $this->send('delete')
            ->assertRedirect(route('test-things.index'))
            ->assertSessionHas('inertia.flash_data.toast.message', 'Thing Bahan Baku was deleted.');
    }

    #[Test]
    public function a_record_in_use_is_explained(): void
    {
        FakeRecordWriteController::$throw = new RecordInUse;

        $this->send('delete')
            ->assertRedirect(route('test-things.show', 3))
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'error',
                'message' => 'Bahan Baku is still in use, so the ERP will not delete it.',
            ]);
    }

    #[Test]
    public function the_erps_own_reasons_are_shown_with_a_refusal(): void
    {
        $exception = new EsbValidationException(
            self::context(),
            fieldErrors: [new EsbFieldError(field: 'uomName', row: null, message: 'Name already used by another unit')],
        );
        FakeRecordWriteController::$throw = $exception;

        $this->send('restore')
            ->assertRedirect(route('test-things.show', 3))
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'error',
                'message' => 'Could not restore Bahan Baku. '.$exception->safeMessage().' Name already used by another unit',
            ]);
    }

    #[Test]
    public function a_refusal_without_reasons_shows_the_safe_message(): void
    {
        $exception = new EsbBusinessRejectionException(self::context());
        FakeRecordWriteController::$throw = $exception;

        $this->send('deactivate')
            ->assertSessionHas('inertia.flash_data.toast.message', 'Could not deactivate Bahan Baku. '.$exception->safeMessage());
    }

    #[Test]
    public function an_unconfirmed_write_warns_with_the_reference(): void
    {
        FakeRecordWriteController::$throw = new EsbTimeoutException(self::context());

        $this->send('restore')
            ->assertRedirect(route('test-things.show', 3))
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'warning',
                'message' => 'The ERP did not confirm the request to restore Bahan Baku. It may or may not have been applied -- check it before trying again. (reference: corr-9)',
            ]);
    }

    #[Test]
    public function an_unconfirmed_delete_lands_on_the_list(): void
    {
        FakeRecordWriteController::$throw = new EsbTimeoutException(self::context());

        $this->send('delete')->assertRedirect(route('test-things.index'));
    }

    #[Test]
    public function a_write_in_flight_asks_to_wait(): void
    {
        FakeRecordWriteController::$throw = new WriteAlreadyInFlight('thing-write:3');

        $this->send('delete')
            ->assertRedirect(route('test-things.show', 3))
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'warning',
                'message' => 'Another change to Bahan Baku is still being processed. Check it before trying again.',
            ]);
    }

    #[Test]
    public function an_unknown_record_is_a_404(): void
    {
        FakeRecordWriteController::$throw = new EsbNotFoundException(self::context());

        $this->send('delete')->assertNotFound();
    }
}

class FakeRecordWriteController extends Controller
{
    use HandlesRecordWrites;

    public static ?Throwable $throw = null;

    public function write(int $id, string $operation): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Thing',
            name: 'Bahan Baku',
            routePrefix: 'test-things',
            id: $id,
            operation: RecordOperation::from($operation),
            write: function (): void {
                if (self::$throw !== null) {
                    throw self::$throw;
                }
            },
        );
    }
}
