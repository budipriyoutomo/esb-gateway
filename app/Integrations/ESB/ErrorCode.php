<?php

namespace App\Integrations\ESB;

use App\Integrations\ESB\Exceptions\EsbAuthenticationException;
use App\Integrations\ESB\Exceptions\EsbAuthorizationException;
use App\Integrations\ESB\Exceptions\EsbBusinessRejectionException;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use App\Integrations\ESB\Exceptions\EsbUnknownException;
use App\Integrations\ESB\Exceptions\EsbValidationException;

/**
 * The ESB status codes, transcribed from docs/05-api-integration.md.
 *
 * Deliberately a lookup table rather than an enum: the ESB will return codes
 * this table does not know about, and an enum would force every unknown value
 * through a failed `tryFrom` before it could be handled. Unknown codes are a
 * normal, expected case here.
 */
final class ErrorCode
{
    public const SUCCESS = 'EC03100000';

    /**
     * Success as the FnB backend family reports it (Push Sales Data V2,
     * [ESB-011]): the HTTP status, as a string, instead of an EC code.
     */
    public const HTTP_SUCCESS = '200';

    /**
     * Success as the ESB OMS master operations report it (Get Menu,
     * [ESB-013]).
     */
    public const OMS_SUCCESS = 'EC011200';

    /**
     * Code to exception class.
     *
     * @var array<string, class-string<EsbException>>
     */
    private const MAP = [
        // Authentication. EC03100001 covers both "Unauthorized" and "Invalid
        // Token"; one vendor example also uses it for a business rejection, so
        // the authentication reading is the common case, not the only one.
        'EC03100001' => EsbAuthenticationException::class,
        'EC011401' => EsbAuthenticationException::class,

        // Authorization -- the credential is valid but lacks the privilege.
        'EC03100002' => EsbAuthorizationException::class,
        'EC03100005' => EsbAuthorizationException::class,
        'EC03100009' => EsbAuthorizationException::class,

        // Validation -- our request was malformed.
        'EC03100003' => EsbValidationException::class,
        'EC03100023' => EsbValidationException::class,
        'EC03100024' => EsbValidationException::class,
        'EC03100025' => EsbValidationException::class,
        'EC03100026' => EsbValidationException::class,
        'EC03100027' => EsbValidationException::class,
        'EC03100036' => EsbValidationException::class,
        'EC03100039' => EsbValidationException::class,
        'EC03100040' => EsbValidationException::class,
        'EC03100042' => EsbValidationException::class,
        'EC03100043' => EsbValidationException::class,
        'EC03100045' => EsbValidationException::class,
        'EC03100046' => EsbValidationException::class,
        'EC03110000' => EsbValidationException::class,
        'EC04100001' => EsbValidationException::class,
        'EC0110' => EsbValidationException::class,

        // Not found.
        'EC03100004' => EsbNotFoundException::class,
        'EC03100038' => EsbNotFoundException::class,

        // Business rejection -- understood and refused on business grounds.
        'EC03100029' => EsbBusinessRejectionException::class,
        'EC031Custom' => EsbBusinessRejectionException::class,
        'EC04100022' => EsbBusinessRejectionException::class,

        // Documented, but not classifiable any further. EC03100032 is used for
        // both a generic "Unknown Error" and specific business rejections such
        // as "this transaction date has exceed close period date", so its
        // message is worth logging but must not drive business logic. EC0118 is
        // an upstream PHP notice leaking through the envelope.
        'EC03100032' => EsbUnknownException::class,
        'EC0118' => EsbUnknownException::class,
    ];

    /**
     * The FnB backend (Push Sales Data V2, [ESB-011]) answers with HTTP-style
     * numbers instead. Only the two it documents are mapped: 400 for missing,
     * blank, or unknown attributes, and 401 for invalid credentials. Kept
     * apart from MAP because PHP turns numeric string keys into integers.
     *
     * @var array<int, class-string<EsbException>>
     */
    private const HTTP_STYLE_MAP = [
        400 => EsbValidationException::class,
        401 => EsbAuthenticationException::class,
    ];

    public static function isSuccess(string $code): bool
    {
        return in_array($code, [self::SUCCESS, self::HTTP_SUCCESS, self::OMS_SUCCESS], true);
    }

    /**
     * The exception class for a code, or null when the code is undocumented.
     *
     * @return class-string<EsbException>|null
     */
    public static function exceptionClassFor(string $code): ?string
    {
        if (preg_match('/^\d{3}$/', $code) === 1) {
            return self::HTTP_STYLE_MAP[(int) $code] ?? null;
        }

        return self::MAP[$code] ?? null;
    }

    public static function isDocumented(string $code): bool
    {
        return self::isSuccess($code) || self::exceptionClassFor($code) !== null;
    }
}
