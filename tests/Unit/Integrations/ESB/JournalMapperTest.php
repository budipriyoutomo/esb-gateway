<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\JournalMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreAccounting;
use Tests\TestCase;

/**
 * The ESB Core accounting reads onto the canonical shape: general journal
 * entries and lines ([ESB-041]), ledger entries and summary rows
 * ([ESB-042]), memorial journals ([ESB-043]). 0 / "" ids and texts are
 * "none"; debit and credit are kept as sent.
 */
class JournalMapperTest extends TestCase
{
    private function mapper(): JournalMapper
    {
        return new JournalMapper;
    }

    #[Test]
    public function it_maps_a_general_journal_entry(): void
    {
        $entries = $this->mapper()->journalEntriesFromRows([
            CoreAccounting::journalRow(),
            CoreAccounting::journalRow(['journalHeadID' => 0]),
            'not a row',
        ]);

        $this->assertCount(1, $entries);
        $entry = $entries[0];
        $this->assertSame('esb', $entry->provider);
        $this->assertSame(CoreAccounting::JOURNAL_HEAD_ID, $entry->journalHeadId);
        $this->assertSame('2026-03-25', $entry->date);
        $this->assertSame('Disbursement', $entry->transactionType);
        $this->assertSame('DB202603250001', $entry->reference);
        $this->assertSame('Sewa ruko Maret', $entry->notes);
        $this->assertTrue($entry->viewableInErp);
    }

    #[Test]
    public function it_maps_a_journal_line_with_placeholders_as_none(): void
    {
        $lines = $this->mapper()->journalLinesFrom([CoreAccounting::journalLine(), null]);

        $this->assertCount(1, $lines);
        $line = $lines[0];
        $this->assertSame(990001, $line->id);
        $this->assertSame(3, $line->branchId);
        $this->assertSame('Outlet Senopati', $line->branchName);
        $this->assertSame('6101', $line->coaNo);
        $this->assertSame('Beban Sewa', $line->coaDescription);
        $this->assertSame('Operasional', $line->costCenter);
        $this->assertNull($line->project);
        $this->assertSame('Rupiah', $line->currencyName);
        $this->assertSame(1.0, $line->rate);
        $this->assertSame(15000000.5, $line->debit);
        $this->assertSame(0.0, $line->credit);
        $this->assertSame('Sewa Maret', $line->notes);
    }

    #[Test]
    public function it_maps_ledger_entries(): void
    {
        $entries = $this->mapper()->ledgerEntriesFrom([CoreAccounting::ledgerEntry(), ['coaNumber' => '']]);

        $this->assertCount(1, $entries);
        $entry = $entries[0];
        $this->assertSame('2026-03-25', $entry->date);
        $this->assertSame(3, $entry->branchId);
        $this->assertSame('Outlet Senopati', $entry->branchName);
        $this->assertSame('1101', $entry->coaNo);
        $this->assertSame('Kas Outlet', $entry->coaDescription);
        $this->assertSame('Disbursement', $entry->transactionType);
        $this->assertSame('DB202603250002', $entry->reference);
        $this->assertSame('Listrik', $entry->notes);
        $this->assertSame('Listrik Maret', $entry->info);
        $this->assertSame(0.0, $entry->debit);
        $this->assertSame(250000.0, $entry->credit);
        $this->assertNull($entry->costCenter);
        $this->assertNull($entry->project);
        $this->assertSame('2026-03-25 09:00:00', $entry->createdAt);
    }

    #[Test]
    public function it_maps_ledger_summary_rows(): void
    {
        $rows = $this->mapper()->ledgerSummaryFromRows([CoreAccounting::summaryRow(), ['accountNo' => '']]);

        $this->assertCount(1, $rows);
        $row = $rows[0];
        $this->assertSame('2026-03-25', $row->date);
        $this->assertSame(3, $row->branchId);
        $this->assertSame('SNP', $row->branchCode);
        $this->assertSame('Outlet Senopati', $row->branchName);
        $this->assertSame('1101', $row->accountNo);
        $this->assertSame('Kas Outlet', $row->accountDescription);
        $this->assertSame(1000000.0, $row->debit);
        $this->assertSame(250000.0, $row->credit);
    }

    #[Test]
    public function it_maps_a_memorial_journal_row(): void
    {
        $summary = $this->mapper()->memorialJournalFromRow(CoreAccounting::mjRow());

        $this->assertNotNull($summary);
        $this->assertSame(CoreAccounting::MJ, $summary->number);
        $this->assertSame('2026-03-26', $summary->date);
        $this->assertSame('Koreksi biaya sewa', $summary->notes);
        $this->assertTrue($summary->hasApproval);
        $this->assertSame('andi', $summary->createdBy);
        $this->assertSame(1, $summary->statusId);
        $this->assertSame('New', $summary->statusName);

        $this->assertNull($this->mapper()->memorialJournalFromRow(CoreAccounting::mjRow(['memorialJournalNum' => ''])));
    }

    #[Test]
    public function it_maps_a_memorial_journal_with_its_lines(): void
    {
        $journal = $this->mapper()->memorialJournalFromDetail(CoreAccounting::mjDetailResult());

        $this->assertNotNull($journal);
        $this->assertSame(CoreAccounting::MJ, $journal->number);
        $this->assertSame('2026-03-26', $journal->date);
        $this->assertTrue($journal->hasApproval);
        $this->assertSame(1, $journal->attachmentCount);
        $this->assertSame('Andi Pratama', $journal->createdBy);
        $this->assertSame('2026-03-26 08:00:00', $journal->createdAt);
        $this->assertNull($journal->editedBy);
        $this->assertNull($journal->authorizedBy);

        $this->assertCount(2, $journal->lines);
        $this->assertSame(71, $journal->lines[0]->id);
        $this->assertSame('6101', $journal->lines[0]->coaNo);
        $this->assertSame('Beban Sewa', $journal->lines[0]->coaDescription);
        $this->assertSame(500000.0, $journal->lines[0]->debit);
        $this->assertSame(500000.0, $journal->lines[1]->credit);
        $this->assertNull($journal->lines[1]->notes);
        $this->assertNull($journal->lines[1]->costCenter);
        $this->assertSame(1, $journal->lines[0]->currencyId);
    }

    #[Test]
    public function a_memorial_journal_keeps_each_lines_cost_center_and_project_on_the_server(): void
    {
        $detail = CoreAccounting::mjDetailResult();
        $detail['memorialJournalDetails'][0] = [...$detail['memorialJournalDetails'][0], 'costCenterID' => 7, 'projectID' => 4];

        $journal = $this->mapper()->memorialJournalFromDetail($detail);

        $this->assertNotNull($journal);
        $this->assertSame(['costCenterId' => 7, 'projectId' => 4], $journal->carryOver->forLine(71));
        // The placeholder 0 is none; an unknown or missing id carries nothing.
        $this->assertSame(['costCenterId' => null, 'projectId' => null], $journal->carryOver->forLine(72));
        $this->assertSame(['costCenterId' => null, 'projectId' => null], $journal->carryOver->forLine(555));
        $this->assertSame(['costCenterId' => null, 'projectId' => null], $journal->carryOver->forLine(null));
        $this->assertArrayNotHasKey('carryOver', $journal->jsonSerialize());
    }

    #[Test]
    public function a_general_journal_line_has_its_currency_id(): void
    {
        $this->assertSame(1, $this->mapper()->journalLinesFrom([CoreAccounting::journalLine()])[0]->currencyId);
    }

    #[Test]
    public function a_creator_without_a_full_name_is_shown_by_username(): void
    {
        $journal = $this->mapper()->memorialJournalFromDetail(CoreAccounting::mjDetailResult([
            'creator' => ['fullName' => '', 'username' => 'andi'],
            'authorizedBy' => 'budi',
            'authorizer' => null,
        ]));

        $this->assertNotNull($journal);
        $this->assertSame('andi', $journal->createdBy);
        $this->assertSame('budi', $journal->authorizedBy);
        $this->assertNull($this->mapper()->memorialJournalFromDetail([]));
    }
}
