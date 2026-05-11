<?php

namespace App\Domain\InspectionSheets\Support;

/**
 * Conversor de números enteros (hasta billones) a su representación en
 * palabras en español. Útil para la línea "SON: ... pesos" de la ficha
 * técnica de avalúo.
 */
class NumberToSpanishWords
{
    private const UNIDADES = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];

    private const ESPECIALES = [
        10 => 'diez', 11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce',
        15 => 'quince', 16 => 'dieciséis', 17 => 'diecisiete', 18 => 'dieciocho', 19 => 'diecinueve',
        20 => 'veinte', 21 => 'veintiuno', 22 => 'veintidós', 23 => 'veintitrés',
        24 => 'veinticuatro', 25 => 'veinticinco', 26 => 'veintiséis',
        27 => 'veintisiete', 28 => 'veintiocho', 29 => 'veintinueve',
    ];

    private const DECENAS = ['', '', '', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];

    private const CENTENAS = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

    /**
     * Convierte un entero a palabras en español.
     * Ejemplo: 27500 → "veintisiete mil quinientos"
     */
    public static function toWords(int $number): string
    {
        if ($number === 0) {
            return 'cero';
        }
        if ($number < 0) {
            return 'menos '.self::toWords(abs($number));
        }
        if ($number >= 1_000_000_000_000) {
            return 'número fuera de rango';
        }

        return trim(self::convert($number));
    }

    /**
     * Convierte a la representación monetaria estándar: "Veintisiete mil quinientos pesos".
     */
    public static function toCurrencyPesos(int|float|null $amount): string
    {
        if ($amount === null) {
            return '';
        }

        $integer = (int) floor((float) $amount);
        $words = self::toWords($integer);
        $words = mb_strtoupper(mb_substr($words, 0, 1)).mb_substr($words, 1);

        return $words.' pesos';
    }

    private static function convert(int $n): string
    {
        if ($n < 10) {
            return self::UNIDADES[$n];
        }
        if ($n < 30) {
            return self::ESPECIALES[$n];
        }
        if ($n < 100) {
            $tens = intdiv($n, 10);
            $ones = $n % 10;

            return $ones === 0
                ? self::DECENAS[$tens]
                : self::DECENAS[$tens].' y '.self::UNIDADES[$ones];
        }
        if ($n < 1000) {
            if ($n === 100) {
                return 'cien';
            }
            $hundreds = intdiv($n, 100);
            $rest = $n % 100;

            return $rest === 0
                ? self::CENTENAS[$hundreds]
                : self::CENTENAS[$hundreds].' '.self::convert($rest);
        }
        if ($n < 1_000_000) {
            $thousands = intdiv($n, 1000);
            $rest = $n % 1000;
            $thousandsWord = $thousands === 1 ? 'mil' : self::convert($thousands).' mil';

            return $rest === 0 ? $thousandsWord : $thousandsWord.' '.self::convert($rest);
        }
        // millones
        $millions = intdiv($n, 1_000_000);
        $rest = $n % 1_000_000;
        $millionsWord = $millions === 1 ? 'un millón' : self::convert($millions).' millones';

        return $rest === 0 ? $millionsWord : $millionsWord.' '.self::convert($rest);
    }
}
