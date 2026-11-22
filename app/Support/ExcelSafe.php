<?php

namespace App\Support;

class ExcelSafe
{
    /**
     * A string cell value starting with = + - @ \t \r is interpreted as a live formula
     * by Excel/Sheets (CSV/Excel formula injection) — a vendor's product name or a
     * customer's own display name is attacker-controlled free text with no character
     * restriction, and these report exports write it straight into a cell. Mirrors the
     * same guard already used for CSV exports in Admin\Crm\CrmContactController::export().
     */
    public static function cell(?string $value): ?string
    {
        if (is_string($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}
