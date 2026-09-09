<?php

namespace App\Exceptions;

use App\Integrations\ESB\Exceptions\EsbBusinessRejectionException;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use App\Integrations\ESB\Exceptions\EsbTimeoutException;
use App\Integrations\ESB\Exceptions\EsbValidationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns a normalized ESB failure into a response.
 *
 * Only ever renders the exception's safe message. The upstream message can
 * carry internal document numbers and, in at least one documented case, an
 * upstream PHP notice complete with a file path.
 */
class EsbExceptionRenderer
{
    public function render(EsbException $exception, Request $request): Response
    {
        $status = $this->statusFor($exception);
        $message = $exception->safeMessage();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'correlationId' => $exception->context->correlationId,
            ], $status);
        }

        return Inertia::render('error', [
            'status' => $status,
            'message' => $message,
            'correlationId' => $exception->context->correlationId,
        ])->toResponse($request)->setStatusCode($status);
    }

    /**
     * Map a failure onto an HTTP status.
     *
     * Note what is deliberately absent: an ESB authorization failure does not
     * become a 403. The gateway's own ESB credential lacking a privilege is an
     * upstream configuration problem, and telling the signed-in user they are
     * forbidden would send them to request a role that would not help.
     */
    private function statusFor(EsbException $exception): int
    {
        return match (true) {
            $exception instanceof EsbNotFoundException => 404,
            $exception instanceof EsbValidationException,
            $exception instanceof EsbBusinessRejectionException => 422,
            $exception instanceof EsbTimeoutException => 504,
            // Authentication, authorization, connection, upstream, malformed,
            // and unknown are all "the ERP service could not serve this".
            default => 502,
        };
    }
}
