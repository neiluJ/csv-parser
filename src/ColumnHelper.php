<?php
namespace Kaliop\CsvParser;

class ColumnHelper
{
    /**
     * Transforms lettered column index into Column # (zéro indexed)
     * ex: AU = 46
     *
     * @param string $pString
     *
     * @return int
     */
    public static function index($pString)
    {
        static $_columnLookup = array(
            'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8, 'I' => 9, 'J' => 10, 'K' => 11, 'L' => 12, 'M' => 13,
            'N' => 14, 'O' => 15, 'P' => 16, 'Q' => 17, 'R' => 18, 'S' => 19, 'T' => 20, 'U' => 21, 'V' => 22, 'W' => 23, 'X' => 24, 'Y' => 25, 'Z' => 26
        );

        $result = false;
        $pString = strtoupper($pString);
        if (isset($pString{0})) {
            if (!isset($pString{1})) {
                $result = $_columnLookup[$pString];
            } elseif(!isset($pString{2})) {
                $result = $_columnLookup[$pString{0}] * 26 + $_columnLookup[$pString{1}];
            } elseif(!isset($pString{3})) {
                $result = $_columnLookup[$pString{0}] * 676 + $_columnLookup[$pString{1}] * 26 + $_columnLookup[$pString{2}];
            }
        }

        if (!$result) {
            throw new \InvalidArgumentException(sprintf('Invalid column index: %s', $pString));
        }

        return $result-1; // 0-indexed
    }
}