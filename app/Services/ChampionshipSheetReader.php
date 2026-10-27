<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PharData;
use RecursiveIteratorIterator;
use SimpleXMLElement;
use Throwable;

class ChampionshipSheetReader
{
    /** @return list<array{name: string, rows: list<list<string>>}> */
    public function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'tsv', 'xlsx'], true) || $file->getSize() > 2 * 1024 * 1024) {
            $this->fail();
        }
        if ($extension !== 'xlsx') {
            return [['name' => $file->getClientOriginalName(), 'rows' => $this->csv($file->getPathname(), $extension)]];
        }
        $temporary = tempnam(sys_get_temp_dir(), 'pm-sheet-');
        if ($temporary === false) {
            $this->fail();
        }
        $archivePath = $temporary.'.xlsx';
        try {
            copy($file->getPathname(), $archivePath);
            $archive = new PharData($archivePath);
            $size = 0;
            $count = 0;
            foreach (new RecursiveIteratorIterator($archive) as $entry) {
                $size += $entry->getSize();
                if (++$count > 2000 || $size > 20 * 1024 * 1024 || $entry->getSize() > 8 * 1024 * 1024 || $entry->isLink()) {
                    $this->fail();
                }
            }
            $workbook = $this->xml($archive, 'xl/workbook.xml');
            $relationships = $this->xml($archive, 'xl/_rels/workbook.xml.rels');
            $targets = [];
            foreach ($relationships->xpath('//*[local-name()="Relationship"]') ?: [] as $relationship) {
                if ((string) $relationship['TargetMode'] !== 'External') {
                    $targets[(string) $relationship['Id']] = (string) $relationship['Target'];
                }
            }
            $shared = [];
            if (isset($archive['xl/sharedStrings.xml'])) {
                foreach ($this->xml($archive, 'xl/sharedStrings.xml')->xpath('//*[local-name()="si"]') ?: [] as $item) {
                    $shared[] = implode('', array_map(static fn (SimpleXMLElement $text): string => (string) $text, $item->xpath('.//*[local-name()="t"]') ?: []));
                    if (count($shared) > 50000) {
                        $this->fail();
                    }
                }
            }
            $dateStyles = [];
            if (isset($archive['xl/styles.xml'])) {
                $styles = $this->xml($archive, 'xl/styles.xml');
                $formats = [];
                foreach ($styles->xpath('//*[local-name()="numFmt"]') ?: [] as $format) {
                    $formats[(int) $format['numFmtId']] = (string) $format['formatCode'];
                }
                foreach ($styles->xpath('//*[local-name()="cellXfs"]/*[local-name()="xf"]') ?: [] as $index => $style) {
                    $formatId = (int) $style['numFmtId'];
                    $dateStyles[$index] = in_array($formatId, [14, 15, 16, 17, 22], true) || preg_match('/[yd]/i', preg_replace('/"[^"]*"|\[[^\]]*\]|\\\\./', '', $formats[$formatId] ?? '') ?? '') === 1;
                }
            }
            $properties = $workbook->xpath('//*[local-name()="workbookPr"]') ?: [];
            $dateBase = isset($properties[0]) && in_array((string) $properties[0]['date1904'], ['1', 'true'], true) ? '1904-01-01' : '1899-12-30';
            $sheets = [];
            foreach ($workbook->xpath('//*[local-name()="sheet"]') ?: [] as $sheet) {
                if (count($sheets) >= 10) {
                    $this->fail();
                }
                $id = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                $target = $targets[$id] ?? '';
                if ($target === '' || str_contains($target, '..') || str_contains($target, '\\')) {
                    $this->fail();
                }
                $path = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
                $rows = [];
                foreach ($this->xml($archive, $path)->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $row) {
                    $number = (int) $row['r'];
                    $cells = [];
                    foreach ($row->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                        preg_match('/^([A-Z]+)[0-9]+$/', (string) $cell['r'], $match);
                        $column = 0;
                        foreach (str_split($match[1] ?? '') as $letter) {
                            $column = $column * 26 + ord($letter) - 64;
                        }
                        $values = $cell->xpath('./*[local-name()="v"]') ?: [];
                        $value = isset($values[0]) ? (string) $values[0] : '';
                        if ((string) $cell['t'] === 's') {
                            $value = $shared[(int) $value] ?? '';
                        } elseif ((string) $cell['t'] === 'inlineStr') {
                            $value = implode('', array_map(static fn (SimpleXMLElement $text): string => (string) $text, $cell->xpath('.//*[local-name()="t"]') ?: []));
                        } elseif (is_numeric($value) && ($dateStyles[(int) $cell['s']] ?? false)) {
                            $value = (new DateTimeImmutable($dateBase))->modify('+'.(int) floor((float) $value).' days')->format('Y-m-d');
                        }
                        if ($value !== '' && ($column < 1 || $column > 50 || $number < 1 || $number > 1020 || mb_strlen($value) > 2000)) {
                            $this->fail();
                        }
                        if ($column >= 1 && $column <= 50) {
                            $cells[$column - 1] = trim($value);
                        }
                    }
                    if (array_filter($cells, static fn (string $value): bool => $value !== '') !== []) {
                        $rows[$number - 1] = $cells;
                    }
                }
                $last = $rows === [] ? -1 : max(array_keys($rows));
                $normalized = [];
                for ($index = 0; $index <= $last; $index++) {
                    $cells = $rows[$index] ?? [];
                    $normalized[] = $cells === [] ? [] : array_values(array_replace(array_fill(0, max(array_keys($cells)) + 1, ''), $cells));
                }
                $sheets[] = ['name' => (string) $sheet['name'], 'rows' => $normalized];
            }
            if ($sheets === [] || array_sum(array_map(static fn (array $sheet): int => count($sheet['rows']), $sheets)) > 5000) {
                $this->fail();
            }

            return $sheets;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['file' => __('championship.invalid_file')]);
        } finally {
            @unlink($archivePath);
            @unlink($temporary);
        }
    }

    private function xml(PharData $archive, string $path): SimpleXMLElement
    {
        if (! isset($archive[$path])) {
            $this->fail();
        }
        $contents = $archive[$path]->getContent();
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $contents) === 1) {
            $this->fail();
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET);
            if ($xml === false) {
                $this->fail();
            }

            return $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /** @return list<list<string>> */
    private function csv(string $path, string $extension): array
    {
        $contents = (string) file_get_contents($path);
        if (str_starts_with($contents, "\xff\xfe") || str_starts_with($contents, "\xfe\xff")) {
            $contents = mb_convert_encoding($contents, 'UTF-8', str_starts_with($contents, "\xff\xfe") ? 'UTF-16LE' : 'UTF-16BE');
        } elseif (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }
        $contents = ltrim($contents, "\xef\xbb\xbf");
        if (str_contains($contents, "\0")) {
            $this->fail();
        }
        $line = strtok($contents, "\r\n") ?: '';
        $delimiter = $extension === 'tsv' ? "\t" : ',';
        $maximum = 0;
        foreach ([',', ';', "\t"] as $candidate) {
            $columns = count(str_getcsv($line, $candidate, '"', ''));
            if ($columns > $maximum) {
                $maximum = $columns;
                $delimiter = $candidate;
            }
        }
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            $this->fail();
        }
        try {
            fwrite($stream, $contents);
            rewind($stream);
            $rows = [];
            while (($row = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
                if (count($rows) >= 1020 || count($row) > 50) {
                    $this->fail();
                }
                $rows[] = array_map(function (?string $cell): string {
                    if (mb_strlen((string) $cell) > 2000) {
                        $this->fail();
                    }

                    return trim((string) $cell);
                }, $row);
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }

    private function fail(): never
    {
        throw ValidationException::withMessages(['file' => __('championship.invalid_file')]);
    }
}
