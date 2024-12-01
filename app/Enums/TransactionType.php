<?php

namespace App\Enums;

enum TransactionType: string
{
    case Kredit = 'Kredit';
    case Debit = 'Debit';

    /**
     * Get the names of the transaction types as an array of strings.
     *
     * @return array<string> An array containing the names of the transaction types.
     */
    public static function casesInString(): array
    {
        return [
            self::Kredit->name,
            self::Debit->name,
        ];
    }

    /**
     * Returns an associative array of transaction types with the enum value as
     * the key and the name as the value. This is useful for populating a
     * `<select>` dropdown.
     *
     * @return array<string, string> An associative array of the transaction types.
     */
    public static function forSelect(): array
    {
        return array_column(self::cases(), 'name', 'value');;
    }
}
