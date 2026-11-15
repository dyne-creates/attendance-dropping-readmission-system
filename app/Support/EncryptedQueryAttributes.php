<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

final class EncryptedQueryAttributes
{
    /**
     * @param  Collection<int, object>  $records
     * @return Collection<int, object>
     */
    public static function decryptMany(Collection $records, string ...$attributes): Collection
    {
        return $records->each(
            fn (object $record): object => self::decrypt($record, ...$attributes)
        );
    }

    public static function decrypt(object $record, string ...$attributes): object
    {
        foreach ($attributes as $attribute) {
            if (isset($record->{$attribute})) {
                $record->{$attribute} = Crypt::decryptString((string) $record->{$attribute});
            }
        }

        return $record;
    }
}
