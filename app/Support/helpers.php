<?php

if (! function_exists('currency_code')) {
    function currency_code(): string
    {
        return (string) config('app.currency_code', 'EGP');
    }
}

if (! function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return (string) config('app.currency_symbol', 'ج.م');
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount, int $decimals = 2, bool $withCode = false): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0.0;
        $formatted = number_format($value, $decimals);
        $moneyValue = currency_symbol().' '.$formatted;

        return $withCode ? $moneyValue.' '.currency_code() : $moneyValue;
    }
}

