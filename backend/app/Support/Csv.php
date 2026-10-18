<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class Csv
{
    /**
     * Neutralise l'injection de formules (=, +, -, @, tabulation, retour chariot) à l'ouverture dans un tableur.
     */
    public static function cell(mixed $value): string
    {
        $value = is_array($value) ? implode(' | ', $value) : (string) ($value ?? '');

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    /**
     * @param  array<int, string>  $header
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour un affichage correct des accents dans Excel
            fputcsv($out, $header, ';');
            foreach ($rows as $row) {
                fputcsv($out, array_map([self::class, 'cell'], $row), ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
