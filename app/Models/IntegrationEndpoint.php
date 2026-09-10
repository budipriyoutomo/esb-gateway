<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One ESB operation from the vendor catalog.
 *
 * Imported from docs/esb/ by `php artisan esb:import-catalog`. Nothing
 * dispatches from this table -- it is a searchable index of what the ESB
 * offers, not a runtime registry.
 *
 * @property int $id
 * @property string $code
 * @property string $product
 * @property string $group
 * @property string $name
 * @property string $version
 * @property string|null $method
 * @property string|null $path
 * @property bool $is_operation
 * @property string $family
 * @property string $docs_url
 */
class IntegrationEndpoint extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_operation' => 'boolean',
        ];
    }
}
