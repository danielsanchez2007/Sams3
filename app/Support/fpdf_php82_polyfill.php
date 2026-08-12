<?php

/**
 * FPDF 1.8 (setasign/fpdf) aún llama a get_magic_quotes_runtime(), eliminada en PHP 8.
 * Estas funciones permiten usar FPDI + FPDF para fusionar PDFs del manual.
 */
if (! function_exists('get_magic_quotes_runtime')) {
    function get_magic_quotes_runtime(): bool
    {
        return false;
    }
}

if (! function_exists('set_magic_quotes_runtime')) {
    function set_magic_quotes_runtime(bool $new_setting): bool
    {
        return false;
    }
}
