<?php

namespace App\Http\Requests;

use App\Authorization\Permission;
use App\Integrations\ESB\EsbConnectionResolver;
use App\Integrations\ESB\EsbFamily;
use App\Models\EsbConnection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates an ESB connection edit (ADR-018).
 *
 * The rules here are the last point at which a person can put the gateway
 * somewhere it should not be, so they are stricter than "is this a string".
 */
class UpdateEsbConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageEsbConnections->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $usingLogin = $this->input('auth_mechanism') === 'login';

        return [
            // HTTPS only. Plain HTTP would put the ESB bearer token and ERP
            // data on the wire in clear text, and this form is the one place a
            // person can introduce it by hand.
            'base_url' => ['required', 'string', 'url', 'starts_with:https://', 'max:2048'],

            'auth_mechanism' => ['required', Rule::in(['static_token', 'login'])],

            // Credentials are write-only, so the form cannot pre-fill them and
            // they come back empty on every later edit. Empty therefore means
            // "leave what is stored", never "clear it" -- otherwise changing a
            // base URL would silently wipe the credential. That is also why
            // the login fields are only required when nothing is stored yet:
            // a later edit cannot resupply what it is never shown.
            'static_token' => ['nullable', 'string', 'max:4096'],
            'username' => [$usingLogin && ! $this->hasStored('username') ? 'required' : 'nullable', 'string', 'max:255'],
            'password' => [$usingLogin && ! $this->hasStored('password') ? 'required' : 'nullable', 'string', 'max:255'],

            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'base_url.starts_with' => 'The base URL must use HTTPS. Plain HTTP would send the ESB credential and ERP data in clear text.',
        ];
    }

    public function existingConnection(): ?EsbConnection
    {
        $family = EsbFamily::tryFrom((string) $this->route('family'));

        if ($family === null) {
            return null;
        }

        return EsbConnection::query()
            ->where('family', $family->value)
            ->where('environment', app(EsbConnectionResolver::class)->configuredEnvironment()->value)
            ->first();
    }

    private function hasStored(string $field): bool
    {
        $connection = $this->existingConnection();

        if ($connection === null) {
            return false;
        }

        $credentials = $connection->credentials ?? [];

        return isset($credentials[$field]) && $credentials[$field] !== '';
    }

    /**
     * The validated payload, shaped for the action.
     *
     * validated() is array<string, mixed>, which tells the action nothing.
     * Naming the shape here means a missing or mistyped field is a static
     * error rather than a runtime surprise.
     *
     * @return array{
     *     base_url: string,
     *     auth_mechanism: string,
     *     static_token: string|null,
     *     username: string|null,
     *     password: string|null,
     *     is_active: bool
     * }
     */
    public function connectionAttributes(): array
    {
        return [
            'base_url' => (string) $this->validated('base_url'),
            'auth_mechanism' => (string) $this->validated('auth_mechanism'),
            'static_token' => $this->credentialInput('static_token'),
            'username' => $this->credentialInput('username'),
            'password' => $this->credentialInput('password'),
            'is_active' => (bool) $this->validated('is_active'),
        ];
    }

    private function credentialInput(string $field): ?string
    {
        $value = $this->validated($field);

        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
