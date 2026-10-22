<?php

namespace App\Actions\MenuCategories;

use App\Actions\Support\MasterRecord;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\RecordWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\MenuCategoryDraft;
use App\Integrations\Contracts\MenuCategorySource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Create and replace POS menu categories ([ESB-048]). The rules are
 * RecordWrites'.
 */
class MenuCategoryWrites
{
    public function __construct(
        private readonly MenuCategorySource $categories,
        private readonly RecordWrites $writes,
    ) {}

    public static function record(): MasterRecord
    {
        return new MasterRecord(
            lockPrefix: 'menu-category',
            audits: [
                RecordOperation::Create->value => AuditAction::MenuCategoryCreated,
                RecordOperation::Update->value => AuditAction::MenuCategoryUpdated,
            ],
            writeUnconfirmed: AuditAction::MenuCategoryWriteUnconfirmed,
        );
    }

    /**
     * @return int|null The new id, when the ERP gave one.
     *
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function create(MenuCategoryDraft $draft): ?int
    {
        $id = $this->writes->create(
            self::record(),
            $this->categories->provider(),
            fn (): ?int => $this->categories->createCategory($draft),
            ['name' => $draft->name],
        );

        return is_int($id) ? $id : null;
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this category is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function update(int $id, MenuCategoryDraft $draft): void
    {
        $this->writes->run(
            self::record(),
            $this->categories->provider(),
            $id,
            RecordOperation::Update,
            fn () => $this->categories->updateCategory($id, $draft),
        );
    }
}
