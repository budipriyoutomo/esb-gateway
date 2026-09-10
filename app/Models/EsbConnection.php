<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A runtime override of one ESB base URL family (ADR-018).
 *
 * Credentials are encrypted at rest and write-only: they are hidden from every
 * array and JSON representation, so a model that reaches a log line, a job
 * payload, or an Inertia prop cannot carry one by accident. The only way to
 * read a credential is to ask for it by name, which is what the resolver does.
 *
 * @property int $id
 * @property string $family
 * @property string $environment
 * @property string $base_url
 * @property string|null $auth_mechanism
 * @property array<string, string>|null $credentials
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class EsbConnection extends Model
{
    protected $guarded = [];

    /**
     * @var list<string>
     */
    protected $hidden = ['credentials'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Laravel's encrypted cast, keyed by APP_KEY. Losing or rotating
            // that key without re-encrypting makes every stored credential
            // unrecoverable -- see docs/13-deployment.md.
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Is a credential stored, without saying what it is?
     *
     * This is the only credential-shaped thing the interface ever shows. It
     * answers the question an operator actually has.
     */
    public function hasCredential(): bool
    {
        return array_filter($this->credentials ?? []) !== [];
    }

    public function staticToken(): ?string
    {
        return $this->credential('static_token');
    }

    public function username(): ?string
    {
        return $this->credential('username');
    }

    public function password(): ?string
    {
        return $this->credential('password');
    }

    private function credential(string $key): ?string
    {
        $value = ($this->credentials ?? [])[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
