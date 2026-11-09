<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Utilitas import / export CSV (kompatibel Excel: UTF-8 BOM, deteksi ; atau ,).
 */
class CsvService
{
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ',', '"', '');
            foreach ($rows as $row) {
                fputcsv($out, array_values($row), ',', '"', '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{headers: array<int,string>, rows: array<int, array<string,string|null>>}
     */
    public static function parse(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $lines = preg_split('/\r\n|\n|\r/', trim($content));
        $delimiter = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';

        $headers = array_map(fn ($h) => strtolower(trim($h)), str_getcsv(array_shift($lines) ?? '', $delimiter, '"', ''));
        $rows = [];

        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }
            $values = str_getcsv($line, $delimiter, '"', '');
            $row = [];
            foreach ($headers as $i => $header) {
                $value = isset($values[$i]) ? trim($values[$i]) : null;
                $row[$header] = $value === '' ? null : $value;
            }
            $row['__line'] = $index + 2;
            $rows[] = $row;
        }

        return compact('headers', 'rows');
    }
}
