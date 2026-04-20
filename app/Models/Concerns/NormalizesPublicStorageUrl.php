<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Grąžina nuotrauką kaip santykinį /storage/... kelią, net jei DB dar laiko seną pilną URL
 * (pvz. po Railway domeno pasikeitimo — kitaip naršyklė kreiptųsi į nebeegzistuojantį hostą).
 */
trait NormalizesPublicStorageUrl
{
    protected function nuotrauka(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if ($value === null || $value === '') {
                    return null;
                }
                $v = trim($value);
                if (preg_match('#^https?://[^/]+(/storage/.+)$#i', $v, $m)) {
                    return $m[1];
                }

                return $v;
            },
        );
    }
}
