<?php

namespace App\Helpers;

class TerbilangHelper
{
    /**
     * Konversi nominal uang ke terbilang dalam Bahasa Indonesia
     * Contoh: 2000000 -> "Dua Juta Rupiah"
     *
     * @param int|float $number
     * @return string
     */
    public static function terbilang($number)
    {
        $number = (int) $number;

        $units = [
            0 => '',
            1 => 'Satu',
            2 => 'Dua',
            3 => 'Tiga',
            4 => 'Empat',
            5 => 'Lima',
            6 => 'Enam',
            7 => 'Tujuh',
            8 => 'Delapan',
            9 => 'Sembilan',
            10 => 'Sepuluh',
            11 => 'Sebelas',
            12 => 'Dua Belas',
            13 => 'Tiga Belas',
            14 => 'Empat Belas',
            15 => 'Lima Belas',
            16 => 'Enam Belas',
            17 => 'Tujuh Belas',
            18 => 'Delapan Belas',
            19 => 'Sembilan Belas',
            20 => 'Dua Puluh',
            30 => 'Tiga Puluh',
            40 => 'Empat Puluh',
            50 => 'Lima Puluh',
            60 => 'Enam Puluh',
            70 => 'Tujuh Puluh',
            80 => 'Delapan Puluh',
            90 => 'Sembilan Puluh',
        ];

        $scales = [
            1000000000000 => 'Triliun',
            1000000000 => 'Miliar',
            1000000 => 'Juta',
            1000 => 'Ribu',
            1 => '',
        ];

        if ($number == 0) {
            return 'Nol Rupiah';
        }

        if ($number < 0) {
            return 'Minus ' . self::terbilang(-$number);
        }

        $result = '';

        foreach ($scales as $scale => $scaleName) {
            if ($number >= $scale) {
                $quotient = intdiv($number, $scale);
                $number = $number % $scale;

                // Process quotient: break down into hundreds, tens, ones
                $hundreds = intdiv($quotient, 100);
                $remainder = $quotient % 100;

                // Add hundreds
                if ($hundreds > 0) {
                    $result .= $units[$hundreds] . ' Ratus';
                    if ($remainder > 0) {
                        $result .= ' ';
                    }
                }

                // Add tens and ones
                if ($remainder > 0) {
                    if ($remainder <= 19) {
                        $result .= $units[$remainder];
                    } else {
                        $tens = intdiv($remainder, 10) * 10;
                        $ones = $remainder % 10;
                        $result .= $units[$tens];
                        if ($ones > 0) {
                            $result .= ' ' . $units[$ones];
                        }
                    }
                }

                if ($scaleName) {
                    $result .= ' ' . $scaleName;
                }

                if ($number > 0) {
                    $result .= ' ';
                }
            }
        }

        return trim($result) . ' Rupiah';
    }
}
