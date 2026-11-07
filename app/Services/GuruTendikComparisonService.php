<?php

namespace App\Services;

use App\Models\GuruTendikRecord;
use App\Models\School;
use App\Models\ShoeRecipient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GuruTendikComparisonService
{
    /**
     * Normalize school name into a canonical key for cross-module matching.
     * Example:
     * - "SEKOLAH RAKYAT MENENGAH PERTAMA 8 CIMAHI" -> "srmp 8 cimahi"
     * - "SRMP 8 CIMAHI" -> "srmp 8 cimahi"
     * - "SEMA 36 Bojonegoro" -> "srma 36 bojonegoro"
     */
    public static function normalizeSchoolKey(?string $schoolName): string
    {
        if ($schoolName === null || trim($schoolName) === '') {
            return '';
        }

        $s = strtoupper(trim($schoolName));

        // Strip common prefixes
        $s = preg_replace('/^(NAMA\s+SEKOLAH|SEKOLAH\s+RAKYAT\s*[:\-]|SATUAN\s+PENDIDIKAN)\s*/i', '', $s);

        // Unify full phrases to standard abbreviations
        $replacements = [
            '/\bSEKOLAH\s+RAKYAT\s+MENENGAH\s+PERTAMA\b/i' => 'SRMP',
            '/\bSR\s+MENENGAH\s+PERTAMA\b/i' => 'SRMP',
            '/\bSEKOLAH\s+RAKYAT\s+MENENGAH\s+ATAS\b/i' => 'SRMA',
            '/\bSR\s+MENENGAH\s+ATAS\b/i' => 'SRMA',
            '/\bSEMA\b/i' => 'SRMA',
            '/\bSEKOLAH\s+RAKYAT\s+DASAR\b/i' => 'SRD',
            '/\bSR\s+DASAR\b/i' => 'SRD',
            '/\bSEKOLAH\s+RAKYAT\s+TERPADU\b/i' => 'SRT',
            '/\bSR\s+TERPADU\b/i' => 'SRT',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $s = preg_replace($pattern, $replacement, $s);
        }

        // Remove administrative administrative noise words that may appear in one source but not another
        $s = preg_replace('/\b(KABUPATEN|KAB\.?|KOTA|PROVINSI|PROV\.?)\b/i', ' ', $s);

        // Replace punctuation with spaces
        $s = preg_replace('/[^A-Z0-9\s]/', ' ', $s);

        // Normalize leading zeros in numbers (e.g. "08" -> "8")
        $s = preg_replace('/\b0+(\d+)\b/', '$1', $s);

        // Collapse multiple spaces
        $s = strtolower(trim(preg_replace('/\s+/', ' ', $s)));

        return $s;
    }

    /**
     * Normalize person name by stripping academic degrees, religious/honorific titles, and punctuation.
     */
    public static function normalizePersonName(?string $name): string
    {
        if ($name === null || trim($name) === '') {
            return '';
        }

        $n = trim($name);

        // Remove everything after comma (academic suffixes like ", S.Pd., Gr., M.Pd.")
        if (str_contains($n, ',')) {
            $parts = explode(',', $n);
            $n = $parts[0];
        }

        // Remove common prefix titles
        $n = preg_replace('/^\s*(prof\.?|dr\.?|drs\.?|dra\.?|ir\.?|h\.?|hj\.?|kh\.?|ust\.?|ustadz|ustadzah|rd\.?|r\.?|ny\.?|tn\.?|ns\.?)\s+/i', '', $n);
        // Run twice in case of stacked prefixes like "Dr. H."
        $n = preg_replace('/^\s*(prof\.?|dr\.?|drs\.?|dra\.?|ir\.?|h\.?|hj\.?|kh\.?|ust\.?|ustadz|ustadzah|rd\.?|r\.?|ny\.?|tn\.?|ns\.?)\s+/i', '', $n);

        // Remove trailing degree tokens that might be written without a comma (e.g. "S.Pd", "M.Pd", "Gr", "S.Kom")
        $n = preg_replace('/\b(s\.?\s*pd\.?\s*(i|sd|gr)?|m\.?\s*pd\.?|s\.?\s*kom\.?|s\.?\s*ag\.?|s\.?\s*si\.?|s\.?\s*hum\.?|s\.?\s*sos\.?|s\.?\s*psi\.?|s\.?\s*e\.?|s\.?\s*h\.?|s\.?\s*t\.?|s\.?\s*kep\.?|ners|a\.?\s*md\.?|gr\.?|m\.?\s*si\.?|m\.?\s*m\.?|m\.?\s*ag\.?|s\.?\s*or\.?|s\.?\s*sn\.?|s\.?\s*ip\.?|s\.?\s*ap\.?|s\.?\s*ak\.?)\s*$/i', '', $n);

        // Lowercase and strip non-letters/numbers
        $n = strtolower($n);
        $n = str_replace(["'", '`', '’', '.', '-', '_'], ' ', $n);
        $n = preg_replace('/[^a-z0-9\s]/', ' ', $n);

        // Unify common Indonesian spelling variations in names for matching
        $n = preg_replace('/\b(muhammad|muhamad|mohammad|mohamad|muchamad|muchammad|mochamad|mochammad|moh\.?|muh\.?)\b/', 'muhamad', $n);

        return trim(preg_replace('/\s+/', ' ', $n));
    }

    /**
     * Normalize gender to 'L' or 'P'.
     */
    public static function normalizeGenderCode(?string $jk): string
    {
        $val = strtoupper(trim((string) $jk));
        if ($val === 'P' || str_contains($val, 'PEREMPUAN') || str_contains($val, 'WANITA')) {
            return 'P';
        }
        if ($val === 'L' || str_contains($val, 'LAKI') || str_contains($val, 'PRIA')) {
            return 'L';
        }
        return '';
    }

    /**
     * Format gender code to full Indonesian label.
     */
    public static function formatGenderLabel(string $code): string
    {
        return match ($code) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }

    /**
     * Determine canonical category ('Guru' or 'Tendik') from jabatan/kategori text.
     */
    public static function classifyKategori(?string $jabatan, ?string $fallbackKategori = null): string
    {
        $j = strtolower(trim((string) $jabatan));
        $k = strtolower(trim((string) $fallbackKategori));

        if (str_contains($j, 'guru') || str_contains($j, 'pengajar') || str_contains($j, 'wali kelas')) {
            return 'Guru';
        }
        if (
            str_contains($j, 'tendik') ||
            str_contains($j, 'tenaga kependidikan') ||
            str_contains($j, 'kepala sekolah') ||
            str_contains($j, 'kepsek') ||
            str_contains($j, 'tata usaha') ||
            str_contains($j, 'tu') ||
            str_contains($j, 'operator') ||
            str_contains($j, 'administrasi') ||
            str_contains($j, 'bendahara') ||
            str_contains($j, 'pustakawan') ||
            str_contains($j, 'laboran') ||
            str_contains($j, 'wali asuh') ||
            str_contains($j, 'wali asrama') ||
            str_contains($j, 'pengasuh') ||
            str_contains($j, 'keamanan') ||
            str_contains($j, 'satpam') ||
            str_contains($j, 'kebersihan') ||
            str_contains($j, 'dapur') ||
            str_contains($j, 'staf') ||
            str_contains($j, 'staff')
        ) {
            return 'Tendik';
        }

        if ($k === 'guru') {
            return 'Guru';
        }
        if ($k === 'tendik') {
            return 'Tendik';
        }

        return 'Guru';
    }

    /**
     * Check if two normalized names are a fuzzy / prefix / token match.
     * E.g. "sopyan" <-> "sopyan iskandar" or slight typo "chendikia jingga utara" <-> "chendykia jingga utara".
     */
    protected function isSimilarName(string $nameA, string $nameB): bool
    {
        if ($nameA === '' || $nameB === '') {
            return false;
        }
        if ($nameA === $nameB) {
            return true;
        }

        $tokensA = explode(' ', $nameA);
        $tokensB = explode(' ', $nameB);

        // Case 1: One name is a prefix token subset of the other (e.g. "sopyan" and "sopyan iskandar")
        $minLen = min(strlen($nameA), strlen($nameB));
        if ($minLen >= 5) {
            if (str_starts_with($nameB, $nameA . ' ') || str_starts_with($nameA, $nameB . ' ')) {
                return true;
            }
            // All tokens of shorter name exist in longer name in order
            $shorter = count($tokensA) <= count($tokensB) ? $tokensA : $tokensB;
            $longer = count($tokensA) <= count($tokensB) ? $tokensB : $tokensA;
            if (count($shorter) >= 1 && strlen($shorter[0]) >= 5) {
                $allFound = true;
                foreach ($shorter as $tok) {
                    if (!in_array($tok, $longer, true)) {
                        $allFound = false;
                        break;
                    }
                }
                if ($allFound) {
                    return true;
                }
            }
        }

        // Case 2: High character similarity for multi-word names (typo tolerance)
        if ($minLen >= 8 && abs(strlen($nameA) - strlen($nameB)) <= 3) {
            $lev = levenshtein($nameA, $nameB);
            if ($lev > 0 && $lev <= 2) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build full comparison dataset between GuruTendikRecord and ShoeRecipient (Guru & Tendik only).
     */
    public function compare(array $filters = []): array
    {
        // 1. Load all Master Guru & Tendik records
        $masterRecordsRaw = GuruTendikRecord::orderBy('sekolah')
            ->orderBy('nama_lengkap')
            ->get();

        // Deduplicate master records by (school_key + normalized_name) in case same file was imported multiple times
        $masterItems = [];
        $masterSeen = [];
        foreach ($masterRecordsRaw as $rec) {
            $schoolKey = self::normalizeSchoolKey($rec->sekolah);
            $normName = self::normalizePersonName($rec->nama_lengkap);
            if ($normName === '') {
                continue;
            }
            $dedupKey = $schoolKey . '||' . $normName;
            if (isset($masterSeen[$dedupKey])) {
                continue;
            }
            $masterSeen[$dedupKey] = true;

            $jkCode = self::normalizeGenderCode($rec->jenis_kelamin);
            $kategori = self::classifyKategori($rec->jabatan);

            $masterItems[] = [
                'id' => $rec->id,
                'no' => $rec->no,
                'nama_lengkap' => trim($rec->nama_lengkap),
                'norm_name' => $normName,
                'jenis_kelamin' => $rec->jenis_kelamin,
                'jk_code' => $jkCode,
                'status' => trim((string) $rec->status),
                'jabatan' => trim((string) $rec->jabatan),
                'kategori' => $kategori,
                'sekolah' => trim((string) $rec->sekolah),
                'school_key' => $schoolKey,
                'sheet_name' => $rec->sheet_name,
                'matched' => false,
            ];
        }

        // 2. Load all Shoe Recap records for Guru & Tendik ONLY (exclude Siswa)
        $shoeRecipientsRaw = ShoeRecipient::with('school')
            ->where(function ($q) {
                $q->whereIn('kategori', ['guru', 'tendik'])
                  ->orWhere('sheet_source', 'like', '%GURU%')
                  ->orWhere('sheet_source', 'like', '%TENDIK%');
            })
            ->where(function ($q) {
                $q->whereNull('sheet_source')
                  ->orWhere(function ($sq) {
                      $sq->where('sheet_source', 'not like', '%SRD%')
                         ->where('sheet_source', 'not like', '%SRMP%')
                         ->where('sheet_source', 'not like', '%SRMA%');
                  });
            })
            ->orderBy('id', 'desc')
            ->get();

        // Deduplicate shoe recipients per school + normalized name (keep latest record)
        $shoeItems = [];
        $shoeSeen = [];
        foreach ($shoeRecipientsRaw as $rec) {
            $schoolName = $rec->school?->nama_sekolah ?? 'Sekolah Tidak Diketahui';
            $schoolKey = self::normalizeSchoolKey($schoolName);
            $normName = self::normalizePersonName($rec->nama_lengkap);
            if ($normName === '') {
                continue;
            }
            $dedupKey = $schoolKey . '||' . $normName;
            if (isset($shoeSeen[$dedupKey])) {
                continue;
            }
            $shoeSeen[$dedupKey] = true;

            $jkCode = self::normalizeGenderCode($rec->jenis_kelamin);
            $kategori = self::classifyKategori($rec->jabatan, $rec->kategori);

            $shoeItems[] = [
                'id' => $rec->id,
                'school_id' => $rec->school_id,
                'nama_lengkap' => trim($rec->nama_lengkap),
                'norm_name' => $normName,
                'jenis_kelamin' => self::formatGenderLabel($jkCode),
                'jk_code' => $jkCode,
                'jabatan' => trim((string) $rec->jabatan),
                'kategori' => $kategori,
                'ukuran_sepatu' => $rec->ukuran_sepatu,
                'panjang_kaki' => $rec->panjang_kaki,
                'is_anomaly' => $rec->is_anomaly,
                'anomaly_reason' => $rec->anomaly_reason,
                'sekolah' => trim($schoolName),
                'pic_nama' => $rec->school?->pic_nama,
                'kota' => $rec->school?->kota,
                'school_key' => $schoolKey,
                'sheet_source' => $rec->sheet_source,
                'matched' => false,
            ];
        }

        // Index shoe items by school_key for fast lookup
        $shoeBySchool = [];
        foreach ($shoeItems as $idx => $sItem) {
            $shoeBySchool[$sItem['school_key']][] = $idx;
        }

        // 3. Perform Multi-Tier Matching
        $matches = []; // masterIdx => ['shoe_idx' => int, 'match_type' => string]

        // Tier 1: Exact normalized name match in the same school
        foreach ($masterItems as $mIdx => $mItem) {
            $sKey = $mItem['school_key'];
            if (empty($shoeBySchool[$sKey])) {
                continue;
            }
            foreach ($shoeBySchool[$sKey] as $sIdx) {
                if ($shoeItems[$sIdx]['matched']) {
                    continue;
                }
                if ($mItem['norm_name'] === $shoeItems[$sIdx]['norm_name']) {
                    $masterItems[$mIdx]['matched'] = true;
                    $shoeItems[$sIdx]['matched'] = true;
                    $matches[$mIdx] = [
                        'shoe_idx' => $sIdx,
                        'match_type' => 'exact',
                    ];
                    break;
                }
            }
        }

        // Tier 2: Similar/Prefix name match in the same school (prefer same gender)
        foreach ($masterItems as $mIdx => $mItem) {
            if ($masterItems[$mIdx]['matched']) {
                continue;
            }
            $sKey = $mItem['school_key'];
            if (empty($shoeBySchool[$sKey])) {
                continue;
            }
            foreach ($shoeBySchool[$sKey] as $sIdx) {
                if ($shoeItems[$sIdx]['matched']) {
                    continue;
                }
                // Check gender compatibility if both have gender
                if (
                    $mItem['jk_code'] !== '' &&
                    $shoeItems[$sIdx]['jk_code'] !== '' &&
                    $mItem['jk_code'] !== $shoeItems[$sIdx]['jk_code']
                ) {
                    continue;
                }
                if ($this->isSimilarName($mItem['norm_name'], $shoeItems[$sIdx]['norm_name'])) {
                    $masterItems[$mIdx]['matched'] = true;
                    $shoeItems[$sIdx]['matched'] = true;
                    $matches[$mIdx] = [
                        'shoe_idx' => $sIdx,
                        'match_type' => 'similar',
                    ];
                    break;
                }
            }
        }

        // Tier 3: Cross-school exact name match (for multi-word names >= 10 chars)
        $unmatchedShoeByNormName = [];
        foreach ($shoeItems as $sIdx => $sItem) {
            if (!$sItem['matched'] && strlen($sItem['norm_name']) >= 10 && str_contains($sItem['norm_name'], ' ')) {
                $unmatchedShoeByNormName[$sItem['norm_name']][] = $sIdx;
            }
        }

        foreach ($masterItems as $mIdx => $mItem) {
            if ($masterItems[$mIdx]['matched']) {
                continue;
            }
            $nName = $mItem['norm_name'];
            if (!empty($unmatchedShoeByNormName[$nName])) {
                foreach ($unmatchedShoeByNormName[$nName] as $sIdx) {
                    if (!$shoeItems[$sIdx]['matched']) {
                        $masterItems[$mIdx]['matched'] = true;
                        $shoeItems[$sIdx]['matched'] = true;
                        $matches[$mIdx] = [
                            'shoe_idx' => $sIdx,
                            'match_type' => 'cross_school',
                        ];
                        break;
                    }
                }
            }
        }

        // 4. Construct Unified Comparison Rows & Aggregate Analytics
        $allRows = [];
        $schoolMap = [];

        // Initialize schools from Master
        foreach ($masterItems as $mItem) {
            $sKey = $mItem['school_key'] ?: 'tanpa-sekolah';
            if (!isset($schoolMap[$sKey])) {
                $schoolMap[$sKey] = [
                    'school_key' => $sKey,
                    'nama_sekolah' => $mItem['sekolah'],
                    'nama_sekolah_sepatu' => null,
                    'pic_nama' => null,
                    'kota' => null,
                    'master_total' => 0,
                    'master_l' => 0,
                    'master_p' => 0,
                    'master_guru' => 0,
                    'master_tendik' => 0,
                    'shoe_total' => 0,
                    'shoe_l' => 0,
                    'shoe_p' => 0,
                    'shoe_guru' => 0,
                    'shoe_tendik' => 0,
                    'sudah_ada' => 0,
                    'belum_ada' => 0,
                    'hanya_di_sepatu' => 0,
                    'diff_attr' => 0,
                ];
            }
            $schoolMap[$sKey]['master_total']++;
            if ($mItem['jk_code'] === 'L') {
                $schoolMap[$sKey]['master_l']++;
            } elseif ($mItem['jk_code'] === 'P') {
                $schoolMap[$sKey]['master_p']++;
            }
            if ($mItem['kategori'] === 'Guru') {
                $schoolMap[$sKey]['master_guru']++;
            } else {
                $schoolMap[$sKey]['master_tendik']++;
            }
        }

        // Also register schools from Shoe Recap
        foreach ($shoeItems as $sItem) {
            $sKey = $sItem['school_key'] ?: 'tanpa-sekolah';
            if (!isset($schoolMap[$sKey])) {
                $schoolMap[$sKey] = [
                    'school_key' => $sKey,
                    'nama_sekolah' => $sItem['sekolah'],
                    'nama_sekolah_sepatu' => $sItem['sekolah'],
                    'pic_nama' => $sItem['pic_nama'],
                    'kota' => $sItem['kota'],
                    'master_total' => 0,
                    'master_l' => 0,
                    'master_p' => 0,
                    'master_guru' => 0,
                    'master_tendik' => 0,
                    'shoe_total' => 0,
                    'shoe_l' => 0,
                    'shoe_p' => 0,
                    'shoe_guru' => 0,
                    'shoe_tendik' => 0,
                    'sudah_ada' => 0,
                    'belum_ada' => 0,
                    'hanya_di_sepatu' => 0,
                    'diff_attr' => 0,
                ];
            } else {
                $schoolMap[$sKey]['nama_sekolah_sepatu'] = $sItem['sekolah'];
                if (!empty($sItem['pic_nama'])) {
                    $schoolMap[$sKey]['pic_nama'] = $sItem['pic_nama'];
                }
                if (!empty($sItem['kota'])) {
                    $schoolMap[$sKey]['kota'] = $sItem['kota'];
                }
            }
            $schoolMap[$sKey]['shoe_total']++;
            if ($sItem['jk_code'] === 'L') {
                $schoolMap[$sKey]['shoe_l']++;
            } elseif ($sItem['jk_code'] === 'P') {
                $schoolMap[$sKey]['shoe_p']++;
            }
            if ($sItem['kategori'] === 'Guru') {
                $schoolMap[$sKey]['shoe_guru']++;
            } else {
                $schoolMap[$sKey]['shoe_tendik']++;
            }
        }

        // Counters for global summary
        $exactMatchCount = 0;
        $similarMatchCount = 0;
        $crossSchoolMatchCount = 0;
        $diffAttrCount = 0;

        $genderStats = [
            'L' => ['label' => 'Laki-laki', 'master' => 0, 'sudah_ada' => 0, 'belum_ada' => 0, 'hanya_di_sepatu' => 0],
            'P' => ['label' => 'Perempuan', 'master' => 0, 'sudah_ada' => 0, 'belum_ada' => 0, 'hanya_di_sepatu' => 0],
        ];
        $kategoriStats = [
            'Guru' => ['label' => 'Guru / Pengajar', 'master' => 0, 'sudah_ada' => 0, 'belum_ada' => 0, 'hanya_di_sepatu' => 0],
            'Tendik' => ['label' => 'Tenaga Kependidikan (Tendik)', 'master' => 0, 'sudah_ada' => 0, 'belum_ada' => 0, 'hanya_di_sepatu' => 0],
        ];
        $statusKepegawaianMap = [];
        $shoeSizeMap = [];

        // Process Master rows (Matched + Missing Shoe)
        foreach ($masterItems as $mIdx => $mItem) {
            $sKey = $mItem['school_key'] ?: 'tanpa-sekolah';
            $jk = $mItem['jk_code'] ?: 'L';
            $kat = $mItem['kategori'];
            $statusPeg = $mItem['status'] !== '' ? strtoupper($mItem['status']) : 'LAINNYA';

            if (isset($genderStats[$jk])) {
                $genderStats[$jk]['master']++;
            }
            if (isset($kategoriStats[$kat])) {
                $kategoriStats[$kat]['master']++;
            }
            if (!isset($statusKepegawaianMap[$statusPeg])) {
                $statusKepegawaianMap[$statusPeg] = [
                    'status' => $statusPeg,
                    'master' => 0,
                    'sudah_ada' => 0,
                    'belum_ada' => 0,
                ];
            }
            $statusKepegawaianMap[$statusPeg]['master']++;

            if (isset($matches[$mIdx])) {
                $sIdx = $matches[$mIdx]['shoe_idx'];
                $mType = $matches[$mIdx]['match_type'];
                $sItem = $shoeItems[$sIdx];

                if ($mType === 'exact') {
                    $exactMatchCount++;
                } elseif ($mType === 'similar') {
                    $similarMatchCount++;
                } else {
                    $crossSchoolMatchCount++;
                }

                $schoolMap[$sKey]['sudah_ada']++;
                if (isset($genderStats[$jk])) {
                    $genderStats[$jk]['sudah_ada']++;
                }
                if (isset($kategoriStats[$kat])) {
                    $kategoriStats[$kat]['sudah_ada']++;
                }
                $statusKepegawaianMap[$statusPeg]['sudah_ada']++;

                // Check attribute differences
                $diffs = [];
                if ($mItem['jk_code'] !== '' && $sItem['jk_code'] !== '' && $mItem['jk_code'] !== $sItem['jk_code']) {
                    $diffs[] = "Jenis Kelamin ({$mItem['jk_code']} vs {$sItem['jk_code']})";
                }
                if ($mItem['kategori'] !== $sItem['kategori']) {
                    $diffs[] = "Kategori ({$mItem['kategori']} vs {$sItem['kategori']})";
                }
                if ($mType === 'cross_school') {
                    $diffs[] = "Sekolah ({$mItem['sekolah']} vs {$sItem['sekolah']})";
                }

                $hasDiff = !empty($diffs);
                if ($hasDiff) {
                    $diffAttrCount++;
                    $schoolMap[$sKey]['diff_attr']++;
                }

                $matchNote = match ($mType) {
                    'exact' => 'Cocok Persis',
                    'similar' => "Kemiripan Nama (Sepatu: {$sItem['nama_lengkap']})",
                    'cross_school' => "Cocok Lintas Sekolah ({$sItem['sekolah']})",
                    default => 'Cocok',
                };
                if ($hasDiff) {
                    $matchNote .= ' • Beda ' . implode(', ', $diffs);
                }

                // Track shoe size distribution
                $size = $sItem['ukuran_sepatu'];
                if ($size) {
                    if (!isset($shoeSizeMap[$size])) {
                        $shoeSizeMap[$size] = ['ukuran' => $size, 'total' => 0, 'laki_laki' => 0, 'perempuan' => 0, 'sudah_match' => 0, 'hanya_sepatu' => 0];
                    }
                    $shoeSizeMap[$size]['total']++;
                    $shoeSizeMap[$size]['sudah_match']++;
                    if ($sItem['jk_code'] === 'P') {
                        $shoeSizeMap[$size]['perempuan']++;
                    } else {
                        $shoeSizeMap[$size]['laki_laki']++;
                    }
                }

                $allRows[] = [
                    'id' => 'M-' . $mItem['id'],
                    'master_id' => $mItem['id'],
                    'shoe_id' => $sItem['id'],
                    'match_status' => 'sudah_ada',
                    'match_status_label' => 'Sudah Ada Data Sepatu',
                    'match_type' => $mType,
                    'match_note' => $matchNote,
                    'has_attribute_diff' => $hasDiff,
                    'attribute_diffs' => $diffs,
                    'school_key' => $sKey,
                    'sekolah' => $mItem['sekolah'],
                    'sekolah_master' => $mItem['sekolah'],
                    'sekolah_sepatu' => $sItem['sekolah'],
                    'pic_nama' => $sItem['pic_nama'] ?? $schoolMap[$sKey]['pic_nama'],
                    'nama_display' => $mItem['nama_lengkap'],
                    'nama_master' => $mItem['nama_lengkap'],
                    'nama_sepatu' => $sItem['nama_lengkap'],
                    'jk_code' => $mItem['jk_code'] ?: $sItem['jk_code'],
                    'jk_master' => self::formatGenderLabel($mItem['jk_code']),
                    'jk_sepatu' => $sItem['jenis_kelamin'],
                    'status_kepegawaian' => $mItem['status'],
                    'jabatan_master' => $mItem['jabatan'],
                    'jabatan_sepatu' => $sItem['jabatan'],
                    'kategori' => $mItem['kategori'],
                    'ukuran_sepatu' => $sItem['ukuran_sepatu'],
                    'panjang_kaki' => $sItem['panjang_kaki'],
                    'is_anomaly' => $sItem['is_anomaly'],
                    'anomaly_reason' => $sItem['anomaly_reason'],
                ];
            } else {
                // Missing in Shoe Recap (Belum Ada Data Sepatu)
                $schoolMap[$sKey]['belum_ada']++;
                if (isset($genderStats[$jk])) {
                    $genderStats[$jk]['belum_ada']++;
                }
                if (isset($kategoriStats[$kat])) {
                    $kategoriStats[$kat]['belum_ada']++;
                }
                $statusKepegawaianMap[$statusPeg]['belum_ada']++;

                $schoolHasShoeData = ($schoolMap[$sKey]['shoe_total'] ?? 0) > 0;
                $missingReason = $schoolHasShoeData
                    ? 'Sekolah sudah kirim rekap sepatu, namun nama ini belum tercantum'
                    : 'Sekolah belum mengunggah data rekapitulasi sepatu';

                $allRows[] = [
                    'id' => 'M-' . $mItem['id'],
                    'master_id' => $mItem['id'],
                    'shoe_id' => null,
                    'match_status' => 'belum_ada',
                    'match_status_label' => 'Belum Ada Data Sepatu',
                    'match_type' => 'unmatched',
                    'match_note' => $missingReason,
                    'has_attribute_diff' => false,
                    'attribute_diffs' => [],
                    'school_key' => $sKey,
                    'sekolah' => $mItem['sekolah'],
                    'sekolah_master' => $mItem['sekolah'],
                    'sekolah_sepatu' => null,
                    'pic_nama' => $schoolMap[$sKey]['pic_nama'] ?? null,
                    'nama_display' => $mItem['nama_lengkap'],
                    'nama_master' => $mItem['nama_lengkap'],
                    'nama_sepatu' => null,
                    'jk_code' => $mItem['jk_code'],
                    'jk_master' => self::formatGenderLabel($mItem['jk_code']),
                    'jk_sepatu' => '-',
                    'status_kepegawaian' => $mItem['status'],
                    'jabatan_master' => $mItem['jabatan'],
                    'jabatan_sepatu' => '-',
                    'kategori' => $mItem['kategori'],
                    'ukuran_sepatu' => null,
                    'panjang_kaki' => null,
                    'is_anomaly' => false,
                    'anomaly_reason' => null,
                ];
            }
        }

        // Process Unmatched Shoe Recap rows (Hanya Ada di Rekap Sepatu)
        $onlyInShoeCount = 0;
        foreach ($shoeItems as $sItem) {
            if ($sItem['matched']) {
                continue;
            }
            $onlyInShoeCount++;
            $sKey = $sItem['school_key'] ?: 'tanpa-sekolah';
            $jk = $sItem['jk_code'] ?: 'L';
            $kat = $sItem['kategori'];

            $schoolMap[$sKey]['hanya_di_sepatu']++;
            if (isset($genderStats[$jk])) {
                $genderStats[$jk]['hanya_di_sepatu']++;
            }
            if (isset($kategoriStats[$kat])) {
                $kategoriStats[$kat]['hanya_di_sepatu']++;
            }

            $size = $sItem['ukuran_sepatu'];
            if ($size) {
                if (!isset($shoeSizeMap[$size])) {
                    $shoeSizeMap[$size] = ['ukuran' => $size, 'total' => 0, 'laki_laki' => 0, 'perempuan' => 0, 'sudah_match' => 0, 'hanya_sepatu' => 0];
                }
                $shoeSizeMap[$size]['total']++;
                $shoeSizeMap[$size]['hanya_sepatu']++;
                if ($sItem['jk_code'] === 'P') {
                    $shoeSizeMap[$size]['perempuan']++;
                } else {
                    $shoeSizeMap[$size]['laki_laki']++;
                }
            }

            $allRows[] = [
                'id' => 'S-' . $sItem['id'],
                'master_id' => null,
                'shoe_id' => $sItem['id'],
                'match_status' => 'hanya_di_sepatu',
                'match_status_label' => 'Hanya di Rekap Sepatu',
                'match_type' => 'only_in_shoe',
                'match_note' => 'Tercatat di rekap ukuran sepatu, tetapi belum terdaftar di Database Guru & Tendik',
                'has_attribute_diff' => false,
                'attribute_diffs' => [],
                'school_key' => $sKey,
                'sekolah' => $schoolMap[$sKey]['nama_sekolah'] ?? $sItem['sekolah'],
                'sekolah_master' => null,
                'sekolah_sepatu' => $sItem['sekolah'],
                'pic_nama' => $sItem['pic_nama'],
                'nama_display' => $sItem['nama_lengkap'],
                'nama_master' => null,
                'nama_sepatu' => $sItem['nama_lengkap'],
                'jk_code' => $sItem['jk_code'],
                'jk_master' => '-',
                'jk_sepatu' => $sItem['jenis_kelamin'],
                'status_kepegawaian' => '-',
                'jabatan_master' => '-',
                'jabatan_sepatu' => $sItem['jabatan'],
                'kategori' => $sItem['kategori'],
                'ukuran_sepatu' => $sItem['ukuran_sepatu'],
                'panjang_kaki' => $sItem['panjang_kaki'],
                'is_anomaly' => $sItem['is_anomaly'],
                'anomaly_reason' => $sItem['anomaly_reason'],
            ];
        }

        // 5. Finalize School Comparison Matrix
        $schoolsComplete = 0;
        $schoolsPartial = 0;
        $schoolsEmpty = 0;
        $schoolsWithShoe = 0;
        $schoolsMasterCount = 0;

        $schoolComparisons = [];
        foreach ($schoolMap as $sKey => $sc) {
            $mTotal = $sc['master_total'];
            $sTotal = $sc['shoe_total'];
            $matched = $sc['sudah_ada'];

            if ($mTotal > 0) {
                $schoolsMasterCount++;
            }
            if ($sTotal > 0) {
                $schoolsWithShoe++;
            }

            $pct = $mTotal > 0 ? round(($matched / $mTotal) * 100, 1) : ($sTotal > 0 ? 100.0 : 0.0);

            if ($mTotal > 0 && $matched >= $mTotal) {
                $statusSekolah = 'lengkap';
                $statusLabel = 'Lengkap (100%)';
                $schoolsComplete++;
            } elseif ($matched > 0 || $sTotal > 0) {
                if ($mTotal === 0) {
                    $statusSekolah = 'tambahan';
                    $statusLabel = 'Hanya di Rekap Sepatu';
                } else {
                    $statusSekolah = 'sebagian';
                    $statusLabel = 'Sebagian Terdata';
                    $schoolsPartial++;
                }
            } else {
                $statusSekolah = 'belum_ada';
                $statusLabel = 'Belum Ada Data Sepatu';
                $schoolsEmpty++;
            }

            $sc['persentase'] = $pct;
            $sc['selisih'] = $sTotal - $mTotal;
            $sc['status_sekolah'] = $statusSekolah;
            $sc['status_label'] = $statusLabel;
            $schoolComparisons[] = $sc;
        }

        // Sort schools: schools with shoe data first, then by completion percentage desc, then by name asc
        usort($schoolComparisons, function ($a, $b) {
            $aHas = ($a['shoe_total'] > 0) ? 1 : 0;
            $bHas = ($b['shoe_total'] > 0) ? 1 : 0;
            if ($aHas !== $bHas) {
                return $bHas <=> $aHas;
            }
            if ($a['persentase'] !== $b['persentase']) {
                return $b['persentase'] <=> $a['persentase'];
            }
            return strcmp($a['nama_sekolah'], $b['nama_sekolah']);
        });

        // Finalize breakdowns
        foreach ($genderStats as $k => &$g) {
            $g['persentase'] = $g['master'] > 0 ? round(($g['sudah_ada'] / $g['master']) * 100, 1) : 0.0;
        }
        unset($g);

        foreach ($kategoriStats as $k => &$kt) {
            $kt['persentase'] = $kt['master'] > 0 ? round(($kt['sudah_ada'] / $kt['master']) * 100, 1) : 0.0;
        }
        unset($kt);

        $statusBreakdown = array_values($statusKepegawaianMap);
        foreach ($statusBreakdown as &$st) {
            $st['persentase'] = $st['master'] > 0 ? round(($st['sudah_ada'] / $st['master']) * 100, 1) : 0.0;
        }
        unset($st);
        usort($statusBreakdown, fn ($a, $b) => $b['master'] <=> $a['master']);

        ksort($shoeSizeMap);
        $shoeSizeDistribution = array_values($shoeSizeMap);

        // 6. Apply Filters to Individual Rows & School Comparisons
        $filterStatus = trim((string) ($filters['match_status'] ?? ''));
        $filterSchool = trim((string) ($filters['sekolah'] ?? ''));
        $filterSchoolKey = $filterSchool !== '' ? self::normalizeSchoolKey($filterSchool) : '';
        $filterGender = trim((string) ($filters['jenis_kelamin'] ?? ''));
        $filterKategori = trim((string) ($filters['kategori'] ?? ''));
        $filterStatusPeg = trim((string) ($filters['status_kepegawaian'] ?? ''));
        $filterSchoolStatus = trim((string) ($filters['status_sekolah'] ?? ''));
        $filterSearch = strtolower(trim((string) ($filters['search'] ?? '')));

        // Build map of school_key => status_sekolah for filtering
        $schoolStatusLookup = [];
        foreach ($schoolComparisons as $sc) {
            $schoolStatusLookup[$sc['school_key']] = $sc['status_sekolah'];
        }

        $filteredRows = array_values(array_filter($allRows, function ($row) use (
            $filterStatus,
            $filterSchoolKey,
            $filterGender,
            $filterKategori,
            $filterStatusPeg,
            $filterSchoolStatus,
            $filterSearch,
            $schoolStatusLookup
        ) {
            if ($filterStatus !== '') {
                if ($filterStatus === 'diff_attr') {
                    if (!$row['has_attribute_diff']) {
                        return false;
                    }
                } elseif ($FilterStatusCheck = ($row['match_status'] !== $filterStatus)) {
                    return false;
                }
            }

            if ($filterSchoolKey !== '' && $row['school_key'] !== $filterSchoolKey) {
                return false;
            }

            if ($filterSchoolStatus !== '') {
                $scStat = $schoolStatusLookup[$row['school_key']] ?? '';
                if ($scStat !== $filterSchoolStatus) {
                    return false;
                }
            }

            if ($filterGender !== '') {
                $targetJk = self::normalizeGenderCode($filterGender);
                if ($targetJk !== '' && $row['jk_code'] !== $targetJk) {
                    return false;
                }
            }

            if ($filterKategori !== '' && strtolower($row['kategori']) !== strtolower($filterKategori)) {
                return false;
            }

            if ($filterStatusPeg !== '' && strtoupper($row['status_kepegawaian']) !== strtoupper($filterStatusPeg)) {
                return false;
            }

            if ($filterSearch !== '') {
                $haystack = strtolower(
                    ($row['nama_display'] ?? '') . ' ' .
                    ($row['nama_master'] ?? '') . ' ' .
                    ($row['nama_sepatu'] ?? '') . ' ' .
                    ($row['sekolah'] ?? '') . ' ' .
                    ($row['sekolah_sepatu'] ?? '') . ' ' .
                    ($row['jabatan_master'] ?? '') . ' ' .
                    ($row['jabatan_sepatu'] ?? '') . ' ' .
                    ($row['status_kepegawaian'] ?? '')
                );
                if (!str_contains($haystack, $filterSearch)) {
                    return false;
                }
            }

            return true;
        }));

        // Sort filtered rows: matched first (so user immediately sees comparisons), then missing from schools that have shoe data, then others
        usort($filteredRows, function ($a, $b) use ($schoolMap) {
            $order = ['sudah_ada' => 1, 'hanya_di_sepatu' => 2, 'belum_ada' => 3];
            $oa = $order[$a['match_status']] ?? 9;
            $ob = $order[$b['match_status']] ?? 9;

            // Prioritize schools that have shoe data
            $aSchoolHasShoe = (($schoolMap[$a['school_key']]['shoe_total'] ?? 0) > 0) ? 1 : 0;
            $bSchoolHasShoe = (($schoolMap[$b['school_key']]['shoe_total'] ?? 0) > 0) ? 1 : 0;
            if ($aSchoolHasShoe !== $bSchoolHasShoe) {
                return $bSchoolHasShoe <=> $aSchoolHasShoe;
            }
            if ($oa !== $ob) {
                return $oa <=> $ob;
            }
            $cmpSchool = strcmp($a['sekolah'], $b['sekolah']);
            if ($cmpSchool !== 0) {
                return $cmpSchool;
            }
            return strcmp($a['nama_display'], $b['nama_display']);
        });

        // Filter schoolComparisons if search or status_sekolah or sekolah filter is active
        $filteredSchools = array_values(array_filter($schoolComparisons, function ($sc) use ($filterSchoolKey, $filterSchoolStatus, $filterSearch) {
            if ($filterSchoolKey !== '' && $sc['school_key'] !== $filterSchoolKey) {
                return false;
            }
            if ($filterSchoolStatus !== '' && $sc['status_sekolah'] !== $filterSchoolStatus) {
                return false;
            }
            if ($filterSearch !== '') {
                $hay = strtolower($sc['nama_sekolah'] . ' ' . ($sc['nama_sekolah_sepatu'] ?? '') . ' ' . ($sc['pic_nama'] ?? ''));
                if (!str_contains($hay, $filterSearch)) {
                    return false;
                }
            }
            return true;
        }));

        $totalMaster = count($masterItems);
        $totalShoeRecap = count($shoeItems);
        $sudahAdaCount = count($matches);
        $belumAdaCount = max(0, $totalMaster - $sudahAdaCount);

        // Unique school options for dropdown
        $schoolOptions = array_map(fn ($sc) => [
            'nama_sekolah' => $sc['nama_sekolah'],
            'school_key' => $sc['school_key'],
            'master_total' => $sc['master_total'],
            'shoe_total' => $sc['shoe_total'],
            'sudah_ada' => $sc['sudah_ada'],
            'status_sekolah' => $sc['status_sekolah'],
        ], $schoolComparisons);

        $statusPegOptions = array_column($statusBreakdown, 'status');

        return [
            'summary' => [
                'total_master' => $totalMaster,
                'total_shoe_recap' => $totalShoeRecap,
                'sudah_ada_data' => $sudahAdaCount,
                'belum_ada_data' => $belumAdaCount,
                'hanya_di_sepatu' => $onlyInShoeCount,
                'persentase_lengkap' => $totalMaster > 0 ? round(($sudahAdaCount / $totalMaster) * 100, 1) : 0.0,
                'persentase_belum' => $totalMaster > 0 ? round(($belumAdaCount / $totalMaster) * 100, 1) : 0.0,
                'exact_match_count' => $exactMatchCount,
                'similar_match_count' => $similarMatchCount,
                'cross_school_match_count' => $crossSchoolMatchCount,
                'diff_attribute_count' => $diffAttrCount,
                'school_stats' => [
                    'total_schools_union' => count($schoolComparisons),
                    'total_schools_master' => $schoolsMasterCount,
                    'total_schools_shoe' => $schoolsWithShoe,
                    'schools_complete' => $schoolsComplete,
                    'schools_partial' => $schoolsPartial,
                    'schools_empty' => $schoolsEmpty,
                    'persentase_sekolah_masuk' => $schoolsMasterCount > 0 ? round(($schoolsWithShoe / $schoolsMasterCount) * 100, 1) : 0.0,
                ],
                'breakdowns' => [
                    'gender' => array_values($genderStats),
                    'kategori' => array_values($kategoriStats),
                    'status_kepegawaian' => $statusBreakdown,
                    'shoe_sizes' => $shoeSizeDistribution,
                ],
            ],
            'school_comparisons' => $filteredSchools,
            'all_school_comparisons' => $schoolComparisons,
            'filtered_rows' => $filteredRows,
            'all_rows' => $allRows,
            'filter_options' => [
                'schools' => $schoolOptions,
                'status_kepegawaian' => $statusPegOptions,
            ],
        ];
    }

    /**
     * Generate comprehensive multi-sheet Excel report for Guru & Tendik Comparison.
     */
    public function generateComparisonExcel(array $filters = []): Spreadsheet
    {
        $data = $this->compare($filters);
        $summary = $data['summary'];
        $schools = $data['school_comparisons'];
        $rows = $data['filtered_rows'];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Sistem Rekapitulasi Sepatu SR')
            ->setTitle('Laporan Perbandingan Data Sepatu vs Database Guru & Tendik');

        // =========================================================================
        // SHEET 1: RINGKASAN PER SEKOLAH
        // =========================================================================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ringkasan Per Sekolah');

        $sheet1->setCellValue('A1', 'LAPORAN PERBANDINGAN REKAPITULASI SEPATU (GURU & TENDIK) VS DATABASE GURU & TENDIK');
        $sheet1->mergeCells('A1:L1');
        $sheet1->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet1->setCellValue(
            'A2',
            sprintf(
                'Total Master: %d | Sudah Ada Sepatu: %d (%s%%) | Belum Ada Sepatu: %d | Hanya di Rekap Sepatu: %d | Dicetak: %s',
                $summary['total_master'],
                $summary['sudah_ada_data'],
                $summary['persentase_lengkap'],
                $summary['belum_ada_data'],
                $summary['hanya_di_sepatu'],
                now()->format('d/m/Y H:i')
            )
        );
        $sheet1->mergeCells('A2:L2');
        $sheet1->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '475569']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $s1Headers = [
            'A4' => 'No',
            'B4' => 'Nama Sekolah (Master)',
            'C4' => 'Nama Sekolah (Rekap Sepatu)',
            'D4' => 'PIC',
            'E4' => 'Total Master (L/P)',
            'F4' => 'Master Guru',
            'G4' => 'Master Tendik',
            'H4' => 'Total Sepatu Masuk',
            'I4' => 'Sudah Ada Data Sepatu',
            'J4' => 'Belum Ada Data Sepatu',
            'K4' => 'Hanya di Rekap Sepatu',
            'L4' => 'Kelengkapan (%)',
        ];
        foreach ($s1Headers as $cell => $label) {
            $sheet1->setCellValue($cell, $label);
        }
        $this->styleHeaderRow($sheet1, 'A4:L4', '0F766E');

        $r = 5;
        foreach ($schools as $idx => $sc) {
            $sheet1->setCellValue("A{$r}", $idx + 1);
            $sheet1->setCellValue("B{$r}", $sc['nama_sekolah']);
            $sheet1->setCellValue("C{$r}", $sc['nama_sekolah_sepatu'] ?? '-');
            $sheet1->setCellValue("D{$r}", $sc['pic_nama'] ?? '-');
            $sheet1->setCellValue("E{$r}", "{$sc['master_total']} ({$sc['master_l']}L / {$sc['master_p']}P)");
            $sheet1->setCellValue("F{$r}", $sc['master_guru']);
            $sheet1->setCellValue("G{$r}", $sc['master_tendik']);
            $sheet1->setCellValue("H{$r}", $sc['shoe_total']);
            $sheet1->setCellValue("I{$r}", $sc['sudah_ada']);
            $sheet1->setCellValue("J{$r}", $sc['belum_ada']);
            $sheet1->setCellValue("K{$r}", $sc['hanya_di_sepatu']);
            $sheet1->setCellValue("L{$r}", $sc['persentase'] . '%');
            $r++;
        }
        if ($r > 5) {
            $this->styleDataRows($sheet1, 'A5:L' . ($r - 1));
        }
        foreach (range('A', 'L') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        // =========================================================================
        // SHEET 2: SUDAH ADA DATA SEPATU
        // =========================================================================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Sudah Ada Data Sepatu');
        $matchedRows = array_values(array_filter($rows, fn ($item) => $item['match_status'] === 'sudah_ada'));
        $this->populatePersonSheet($sheet2, 'DAFTAR GURU & TENDIK YANG SUDAH ADA DATA SEPATU', $matchedRows, '059669');

        // =========================================================================
        // SHEET 3: BELUM ADA DATA SEPATU
        // =========================================================================
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Belum Ada Data Sepatu');
        $missingRows = array_values(array_filter($rows, fn ($item) => $item['match_status'] === 'belum_ada'));
        $this->populatePersonSheet($sheet3, 'DAFTAR GURU & TENDIK YANG BELUM ADA DATA SEPATU', $missingRows, 'DC2626');

        // =========================================================================
        // SHEET 4: HANYA DI REKAP SEPATU
        // =========================================================================
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Hanya di Rekap Sepatu');
        $onlyShoeRows = array_values(array_filter($rows, fn ($item) => $item['match_status'] === 'hanya_di_sepatu'));
        $this->populatePersonSheet($sheet4, 'DAFTAR GURU & TENDIK HANYA DI REKAP SEPATU (BELUM DI MASTER)', $onlyShoeRows, 'D97706');

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected function populatePersonSheet($sheet, string $title, array $rows, string $headerColorHex): void
    {
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $headers = [
            'A3' => 'No',
            'B3' => 'Status Perbandingan',
            'C3' => 'Sekolah',
            'D3' => 'Nama Lengkap (Master)',
            'E3' => 'Nama Lengkap (Rekap Sepatu)',
            'F3' => 'JK (Master)',
            'G3' => 'JK (Sepatu)',
            'H3' => 'Status Kepegawaian',
            'I3' => 'Jabatan (Master)',
            'J3' => 'Jabatan (Sepatu)',
            'K3' => 'Ukuran Sepatu',
            'L3' => 'Panjang Kaki (cm)',
            'M3' => 'Keterangan Pencocokan',
        ];
        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }
        $this->styleHeaderRow($sheet, 'A3:M3', $headerColorHex);

        $r = 4;
        foreach ($rows as $idx => $item) {
            $sheet->setCellValue("A{$r}", $idx + 1);
            $sheet->setCellValue("B{$r}", $item['match_status_label']);
            $sheet->setCellValue("C{$r}", $item['sekolah']);
            $sheet->setCellValue("D{$r}", $item['nama_master'] ?? '-');
            $sheet->setCellValue("E{$r}", $item['nama_sepatu'] ?? '-');
            $sheet->setCellValue("F{$r}", $item['jk_master']);
            $sheet->setCellValue("G{$r}", $item['jk_sepatu']);
            $sheet->setCellValue("H{$r}", $item['status_kepegawaian']);
            $sheet->setCellValue("I{$r}", $item['jabatan_master']);
            $sheet->setCellValue("J{$r}", $item['jabatan_sepatu']);
            $sheet->setCellValue("K{$r}", $item['ukuran_sepatu'] ?? '-');
            $sheet->setCellValue("L{$r}", $item['panjang_kaki'] ?? '-');
            $sheet->setCellValue("M{$r}", $item['match_note']);
            $r++;
        }

        if ($r > 4) {
            $this->styleDataRows($sheet, 'A4:M' . ($r - 1));
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function styleHeaderRow($sheet, string $range, string $bgHex): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $bgHex],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
        ]);
        $sheet->getRowDimension((int) preg_replace('/[^0-9]/', '', explode(':', $range)[0]))->setRowHeight(24);
    }

    protected function styleDataRows($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }
}

