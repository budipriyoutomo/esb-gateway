<?php

namespace App\Http\Controllers\Suppliers;

use App\Actions\Suppliers\SupplierWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Http\Controllers\Concerns\HandlesRecordWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\Suppliers\AttachSupplierFilesRequest;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Attach files to a supplier, or remove all of them ([ESB-032], S4). The
 * outcome lands on the supplier. Authorization (esb.invoke +
 * suppliers.manage) is on the routes and the form request.
 */
class SupplierAttachmentController extends Controller
{
    use HandlesRecordWrites;

    public function store(int $supplierId, AttachSupplierFilesRequest $request, SupplierWrites $writes): RedirectResponse
    {
        $files = $request->attachments();
        $name = $this->recordName($request, $supplierId);

        return $this->outcome($supplierId, $name, 'attach files to', fn () => $writes->attach($supplierId, $files), sprintf(
            count($files) === 1 ? '%d file was attached to %s.' : '%d files were attached to %s.',
            count($files),
            $name,
        ));
    }

    public function destroy(int $supplierId, Request $request, SupplierWrites $writes): RedirectResponse
    {
        $name = $this->recordName($request, $supplierId);

        return $this->outcome($supplierId, $name, 'remove the attachments of', fn () => $writes->deleteAttachments($supplierId), sprintf('Every attachment of %s was removed.', $name));
    }

    /**
     * @param  string  $doing  e.g. "attach files to", for messages.
     * @param  Closure(): void  $write
     */
    private function outcome(int $supplierId, string $name, string $doing, Closure $write, string $success): RedirectResponse
    {
        $back = redirect()->route('suppliers.show', $supplierId);

        try {
            $write();
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', sprintf('Another change to %s is still being processed. Check it before trying again.', $name));

            return $back;
        } catch (EsbNotFoundException $exception) {
            throw $exception;
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm the request to %s %s. It may or may not have been applied -- check it before trying again. (reference: %s)',
                    $doing,
                    $name,
                    $exception->context->correlationId,
                ));
            } else {
                $this->toast('error', trim(sprintf('Could not %s %s. %s', $doing, $name, $exception->safeMessage())));
            }

            return $back;
        }

        $this->toast('success', $success);

        return $back;
    }
}
