<?php

namespace App\Actions\DocumentTemplates;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\DocumentTemplateDraft;
use App\Integrations\Contracts\DocumentTemplateSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Create a document template in the ERP, on behalf of a person ([ESB-016]).
 */
class CreateDocumentTemplate
{
    public function __construct(
        private readonly DocumentTemplateSource $templates,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @param  int|null  $actorId  Who it is done for, when not the signed-in
     *                             user -- a queued import sync (ADR-023).
     * @param  array<string, mixed>  $context  Extra audit metadata, such as the import id.
     * @return int|null The new template's id, when the ERP returned one.
     *
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(DocumentTemplateDraft $draft, ?int $actorId = null, array $context = []): ?int
    {
        // Null actor: AuditLog::record() falls back to the signed-in user.
        $lockOwner = $actorId ?? Auth::id();
        $provider = $this->templates->provider();
        $details = [
            'name' => $draft->name,
            'type' => $draft->type->value,
            'branch_ids' => $draft->branchIds,
            'lines' => count($draft->lines),
            ...$context,
        ];

        $id = $this->guard->run(
            DocumentTemplateLocks::create($lockOwner),
            fn (): ?int => $this->templates->createDocumentTemplate($draft),
            function (EsbException $exception) use ($provider, $details, $actorId): void {
                AuditLog::record(
                    action: AuditAction::DocumentTemplateWriteUnconfirmed,
                    actorId: $actorId,
                    metadata: [
                        'provider' => $provider,
                        'operation' => 'create',
                        ...$details,
                        'failure' => $exception->kind(),
                        'correlation_id' => $exception->context->correlationId,
                    ],
                );
            },
        );

        AuditLog::record(
            action: AuditAction::DocumentTemplateCreated,
            actorId: $actorId,
            metadata: [
                'provider' => $provider,
                'template_id' => $id,
                ...$details,
            ],
        );

        return $id;
    }
}
