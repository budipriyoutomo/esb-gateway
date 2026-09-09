<?php

namespace Tests\Feature\Authorization;

use App\Authorization\Permission;
use App\Authorization\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The frontend mirrors the permission and role names so React can type-check
 * `can('...')` calls. Two lists of the same strings in two languages drift
 * silently, and the symptom would be a control that never renders rather than
 * an error. These tests make the drift fail the build instead.
 */
class PermissionTypesInSyncTest extends TestCase
{
    private const TYPES_FILE = 'resources/js/types/permissions.ts';

    /**
     * @return list<string>
     */
    private function literalsInUnion(string $contents, string $typeName): array
    {
        $matched = preg_match(
            '/export type '.preg_quote($typeName, '/').'\s*=\s*(.*?);/s',
            $contents,
            $matches,
        );

        $this->assertSame(1, $matched, "Could not find `export type {$typeName}` in ".self::TYPES_FILE);

        preg_match_all("/'([^']+)'/", $matches[1], $literals);

        return $literals[1];
    }

    #[Test]
    public function the_frontend_permission_union_matches_the_php_enum(): void
    {
        $contents = file_get_contents(base_path(self::TYPES_FILE));

        $this->assertIsString($contents);

        $expected = Permission::names();
        $actual = $this->literalsInUnion($contents, 'Permission');

        sort($expected);
        sort($actual);

        $this->assertSame(
            $expected,
            $actual,
            'resources/js/types/permissions.ts is out of sync with App\Authorization\Permission.',
        );
    }

    #[Test]
    public function the_frontend_role_union_matches_the_php_enum(): void
    {
        $contents = file_get_contents(base_path(self::TYPES_FILE));

        $this->assertIsString($contents);

        $expected = array_column(Role::cases(), 'value');
        $actual = $this->literalsInUnion($contents, 'RoleName');

        sort($expected);
        sort($actual);

        $this->assertSame(
            $expected,
            $actual,
            'resources/js/types/permissions.ts is out of sync with App\Authorization\Role.',
        );
    }
}
