<?php

namespace Tests\Feature\Http;

use App\Actions\Support\CreatedRecord;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Http\Controllers\Concerns\HandlesRecordForms;
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
 * How a master data create / update outcome becomes a toast, a redirect, or
 * errors on the form (TODO-modules.md §2, S3).
 */
class HandlesRecordFormsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FakeRecordFormController::$throw = null;
        FakeRecordFormController::$newId = null;

        Route::middleware('web')->group(function (): void {
            Route::get('test-things', fn () => 'index')->name('test-things.index');
            Route::get('test-things/create', fn () => 'create')->name('test-things.create');
            Route::get('test-things/{id}', fn () => 'show')->name('test-things.show');
            Route::get('test-things/{id}/edit', fn () => 'edit')->name('test-things.edit');
            Route::post('test-things', [FakeRecordFormController::class, 'store']);
            Route::put('test-things/{id}', [FakeRecordFormController::class, 'update']);
        });

        Route::getRoutes()->refreshNameLookups();
    }

    private static function context(): EsbContext
    {
        return new EsbContext(correlationId: 'corr-9', operation: 'test');
    }

    private function create()
    {
        return $this->from(route('test-things.create'))->post('test-things');
    }

    private function update()
    {
        return $this->from(route('test-things.edit', 3))->put('test-things/3');
    }

    #[Test]
    public function a_create_returns_to_the_list_because_the_erp_returns_no_id(): void
    {
        $this->create()
            ->assertRedirect(route('test-things.index'))
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'success',
                'message' => 'Thing Bahan Baku was created.',
            ]);
    }

    #[Test]
    public function a_create_lands_on_the_new_record_when_the_erp_returns_its_id(): void
    {
        FakeRecordFormController::$newId = 12;

        $this->create()
            ->assertRedirect(route('test-things.show', 12))
            ->assertSessionHas('inertia.flash_data.toast.message', 'Thing Bahan Baku was created.');
    }

    #[Test]
    public function a_create_waiting_for_approval_says_so_and_returns_to_the_list(): void
    {
        FakeRecordFormController::$newId = new CreatedRecord(12, pendingApproval: true);

        $this->create()
            ->assertRedirect(route('test-things.index'))
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'success',
                'message' => 'Thing Bahan Baku was created and is waiting for approval in the ERP.',
            ]);
    }

    #[Test]
    public function a_created_record_with_an_id_is_opened(): void
    {
        FakeRecordFormController::$newId = new CreatedRecord(12);

        $this->create()->assertRedirect(route('test-things.show', 12));
    }

    #[Test]
    public function an_update_lands_on_the_record(): void
    {
        $this->update()
            ->assertRedirect(route('test-things.show', 3))
            ->assertSessionHas('inertia.flash_data.toast.message', 'Thing Bahan Baku was updated.');
    }

    #[Test]
    public function the_erps_field_errors_go_back_to_the_fields_they_name(): void
    {
        FakeRecordFormController::$throw = new EsbValidationException(self::context(), fieldErrors: [
            new EsbFieldError(field: 'uomName', row: null, message: 'Name already used'),
            new EsbFieldError(field: 'uomName', row: null, message: 'Name too long'),
            new EsbFieldError(field: 'somethingElse', row: null, message: 'Odd'),
        ]);

        $this->create()
            ->assertRedirect(route('test-things.create'))
            ->assertSessionHasErrors([
                'name' => 'Name already used Name too long',
                'form' => 'Odd',
            ]);
    }

    #[Test]
    public function a_refusal_without_field_errors_is_shown_for_the_whole_form(): void
    {
        $exception = new EsbBusinessRejectionException(self::context());
        FakeRecordFormController::$throw = $exception;

        $this->update()
            ->assertRedirect(route('test-things.edit', 3))
            ->assertSessionHasErrors(['form' => $exception->safeMessage()]);
    }

    #[Test]
    public function an_unconfirmed_create_sends_the_person_to_the_list_with_the_reference(): void
    {
        FakeRecordFormController::$throw = new EsbTimeoutException(self::context());

        $this->create()
            ->assertRedirect(route('test-things.index'))
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'warning',
                'message' => 'The ERP did not confirm the request to create Bahan Baku. It may or may not have been applied -- check it before trying again. (reference: corr-9)',
            ]);
    }

    #[Test]
    public function an_unconfirmed_update_lands_on_the_record(): void
    {
        FakeRecordFormController::$throw = new EsbTimeoutException(self::context());

        $this->update()
            ->assertRedirect(route('test-things.show', 3))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
    }

    #[Test]
    public function a_write_in_flight_asks_to_wait(): void
    {
        FakeRecordFormController::$throw = new WriteAlreadyInFlight('thing-create:1');

        $this->create()
            ->assertRedirect(route('test-things.index'))
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'warning',
                'message' => 'Your previous new thing is still being created. Check the list before trying again.',
            ]);

        $this->update()
            ->assertRedirect(route('test-things.show', 3))
            ->assertSessionHas('inertia.flash_data.toast.message', 'Another change to Bahan Baku is still being processed. Check it before trying again.');
    }

    #[Test]
    public function updating_an_unknown_record_is_a_404(): void
    {
        FakeRecordFormController::$throw = new EsbNotFoundException(self::context());

        $this->update()->assertNotFound();
    }
}

class FakeRecordFormController extends Controller
{
    use HandlesRecordForms;

    public static ?Throwable $throw = null;

    public static int|CreatedRecord|null $newId = null;

    private function write(): int|CreatedRecord|null
    {
        if (self::$throw !== null) {
            throw self::$throw;
        }

        return self::$newId;
    }

    public function store(): RedirectResponse
    {
        return $this->recordSaveOutcome('Thing', 'Bahan Baku', 'test-things', null, fn () => $this->write(), ['uomName' => 'name']);
    }

    public function update(int $id): RedirectResponse
    {
        return $this->recordSaveOutcome('Thing', 'Bahan Baku', 'test-things', $id, fn () => $this->write(), ['uomName' => 'name']);
    }
}
