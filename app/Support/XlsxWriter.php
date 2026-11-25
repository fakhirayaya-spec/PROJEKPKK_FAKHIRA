<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Penulis file Excel (.xlsx) sederhana tanpa library tambahan (hanya butuh ekstensi PHP "zip",
 * yang sudah aktif di XAMPP). Mendukung banyak sheet, angka asli (bukan teks), format Rupiah,
 * header berwarna, dan lebar kolom otomatis.
 *
 * Isi sel: nilai biasa (string / angka) atau [nilai, 'gaya'].
 * Gaya: judul, sub, header, rp, angka, persen, tebal, tebal_rp, catatan, hijau_rp, merah_rp
 */
class XlsxWriter
{
    private const GAYA = [
        'judul' => 1, 'sub' => 2, 'header' => 3, 'rp' => 4, 'angka' => 5,
        'tebal' => 6, 'tebal_rp' => 7, 'catatan' => 8, 'hijau_rp' => 9, 'merah_rp' => 10, 'persen' => 11,
    ];

    /** @var array<int, array{nama: string, baris: array}> */
    private array $sheets = [];

    public static function tersedia(): bool
    {
        return class_exists(ZipArchive::class);
    }

    /** Tambah sheet. $baris = array of row, row = array of cell. */
    public function sheet(string $nama, array $baris): self
    {
        $nama = trim(preg_replace('/[\[\]\*\?\/\\\\:]/', ' ', $nama));
        $this->sheets[] = ['nama' => mb_substr($nama !== '' ? $nama : 'Sheet', 0, 31), 'baris' => $baris];

        return $this;
    }

    /** Simpan ke file. */
    public function simpan(string $path): void
    {
        if (! self::tersedia()) {
            throw new RuntimeException('Ekstensi PHP "zip" belum aktif.');
        }
        if (! $this->sheets) {
            $this->sheet('Laporan', []);
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat file Excel.');
        }

        $n = count($this->sheets);

        $tipe = '';
        $rel = '';
        $daftar = '';
        foreach ($this->sheets as $i => $s) {
            $no = $i + 1;
            $tipe .= '<Override PartName="/xl/worksheets/sheet' . $no . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $rel .= '<Relationship Id="rId' . $no . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $no . '.xml"/>';
            $daftar .= '<sheet name="' . $this->esc($s['nama']) . '" sheetId="' . $no . '" r:id="rId' . $no . '"/>';
            $zip->addFromString('xl/worksheets/sheet' . $no . '.xml', $this->xmlSheet($s['baris']));
        }

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $tipe . '</Types>');

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $daftar . '</sheets></workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rel
            . '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/styles.xml', $this->xmlStyles());
        $zip->close();
    }

    // ------------------------------------------------------------------

    private function xmlSheet(array $baris): string
    {
        $lebar = [];
        $xml = '';

        foreach (array_values($baris) as $r => $row) {
            $nomorBaris = $r + 1;
            $xml .= '<row r="' . $nomorBaris . '">';

            foreach (array_values((array) $row) as $c => $cell) {
                $gaya = null;
                if (is_array($cell)) {
                    $gaya = $cell[1] ?? null;
                    $cell = $cell[0] ?? '';
                }
                if ($cell === null || $cell === '') {
                    continue;
                }

                $ref = $this->kolom($c) . $nomorBaris;
                $s = $gaya !== null && isset(self::GAYA[$gaya]) ? ' s="' . self::GAYA[$gaya] . '"' : '';

                if (is_int($cell) || is_float($cell)) {
                    $xml .= '<c r="' . $ref . '"' . $s . '><v>' . $cell . '</v></c>';
                    $panjang = strlen((string) number_format((float) $cell, 0, ',', '.')) + 4;
                } else {
                    $teks = $this->bersih((string) $cell);
                    $xml .= '<c r="' . $ref . '"' . $s . ' t="inlineStr"><is><t xml:space="preserve">' . $this->esc($teks) . '</t></is></c>';
                    // judul/catatan panjang tidak ikut menentukan lebar kolom
                    $panjang = in_array($gaya, ['judul', 'sub', 'catatan'], true) ? 0 : mb_strlen($teks) + 2;
                }

                $lebar[$c] = max($lebar[$c] ?? 8, min($panjang, 60));
            }

            $xml .= '</row>';
        }

        $cols = '';
        ksort($lebar);
        foreach ($lebar as $c => $w) {
            $cols .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . $w . '" customWidth="1"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews>'
            . ($cols !== '' ? '<cols>' . $cols . '</cols>' : '')
            . '<sheetData>' . $xml . '</sheetData></worksheet>';
    }

    private function xmlStyles(): string
    {
        // fonts: 0 normal, 1 judul, 2 abu, 3 tebal, 4 abu miring, 5 hijau tebal, 6 merah tebal, 7 putih? (tidak dipakai)
        $fonts = '<fonts count="7">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="16"/><color rgb="FF3D2314"/><name val="Calibri"/></font>'
            . '<font><sz val="11"/><color rgb="FF7A6455"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF3D2314"/><name val="Calibri"/></font>'
            . '<font><i/><sz val="10"/><color rgb="FF7A6455"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF15803D"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFB91C1C"/><name val="Calibri"/></font>'
            . '</fonts>';

        $fills = '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFCAE1E"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>';

        $borders = '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFE5D8C8"/></left><right style="thin"><color rgb="FFE5D8C8"/></right>'
            . '<top style="thin"><color rgb="FFE5D8C8"/></top><bottom style="thin"><color rgb="FFE5D8C8"/></bottom><diagonal/></border>'
            . '</borders>';

        $numFmts = '<numFmts count="2"><numFmt numFmtId="164" formatCode="&quot;Rp &quot;#,##0;[Red]-&quot;Rp &quot;#,##0"/><numFmt numFmtId="165" formatCode="0.0&quot;%&quot;"/></numFmts>';

        // urutan harus sama dengan self::GAYA (index 0 = bawaan)
        $xfs = '<cellXfs count="12">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                                                                                  // 0 bawaan
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                                                    // 1 judul
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                                                    // 2 sub
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' // 3 header
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'                                          // 4 rp
            . '<xf numFmtId="3" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'                                            // 5 angka
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>'                                                    // 6 tebal
            . '<xf numFmtId="164" fontId="3" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1"/>'                            // 7 tebal_rp
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                                                    // 8 catatan
            . '<xf numFmtId="164" fontId="5" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1"/>'                            // 9 hijau_rp
            . '<xf numFmtId="164" fontId="6" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1"/>'                            // 10 merah_rp
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'                                          // 11 persen
            . '</cellXfs>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $numFmts . $fonts . $fills . $borders
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . $xfs
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    /** 0 => A, 25 => Z, 26 => AA */
    private function kolom(int $i): string
    {
        $h = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $h = chr(65 + ($i - 1) % 26) . $h;
        }

        return $h;
    }

    private function bersih(string $s): string
    {
        // buang karakter kontrol yang tidak boleh ada di XML
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? '';
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
