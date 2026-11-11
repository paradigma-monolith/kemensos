<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
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

        // Remove administrative noise words that may appear in one source but not another
        $s = preg_replace('/\b(KABUPATEN|KAB\.?|KOTA|PROVINSI|PROV\.?)\b/i', ' ', $s);

        // Replace punctuation with spaces
        $s = preg_replace('/[^A-Z0-9\s]/', ' ', $s);

        // Normalize leading zeros in numbers (e.g. "08" -> "8")
        $s = preg_replace('/\b0+(\d+)\b/', '$1', $s);

        // Collapse multiple spaces
        return strtolower(trim(preg_replace('/\s+/', ' ', $s)));
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
        $n = preg_replace('/^\s*(prof\.?|dr\.?|drs\.?|dra\.?|ir\.?|h\.?|hj\.?|kh\.?|ust\.?|ustadz|ustadzah|rd\.?|r\.?|ny\.?|tn\.?|ns\.?)\s+/i', '', $n);

        // Remove trailing degree tokens that might be written without a comma
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

        $minLen = min(strlen($nameA), strlen($nameB));
        if ($minLen >= 5) {
            if (str_starts_with($nameB, $nameA . ' ') || str_starts_with($nameA, $nameB . ' ')) {
                return true;
            }
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

        if ($minLen >= 8 && abs(strlen($nameA) - strlen($nameB)) <= 3) {
            $lev = levenshtein($nameA, $nameB);
            if ($lev > 0 && $lev <= 2) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build comparison dataset between GuruTendikRecord and ShoeRecipient (Guru & Tendik only).
     * When $page and $perPage are provided, only the requested slice of individual rows is materialized
     * into full display arrays so peak memory stays under 20MB even with 15,000+ rows.
     */
    public function compare(array $filters = [], ?int $page = null, int $perPage = 50): array
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        // 1. Stream Master Guru & Tendik records using lightweight DB::table cursor
        $masterCursor = DB::table('guru_tendik_records')
            ->select(['id', 'nama_lengkap', 'jenis_kelamin', 'status', 'jabatan', 'sekolah'])
            ->orderBy('sekolah')
            ->orderBy('nama_lengkap')
            ->cursor();

        $masterItems = [];
        $masterSeen = [];
        $schoolKeyCache = [];

        foreach ($masterCursor as $rec) {
            $rawSekolah = trim((string) $rec->sekolah);
            if (!isset($schoolKeyCache[$rawSekolah])) {
                $schoolKeyCache[$rawSekolah] = self::normalizeSchoolKey($rawSekolah);
            }
            $schoolKey = $schoolKeyCache[$rawSekolah];
            $normName = self::normalizePersonName($rec->nama_lengkap);
            if ($normName === '') {
                continue;
            }
            $dedupKey = $schoolKey . '||' . $normName;
            if (isset($masterSeen[$dedupKey])) {
                continue;
            }
            $masterSeen[$dedupKey] = true;

            // Compact indexed array: [0:id, 1:nama, 2:norm, 3:jk, 4:status, 5:jabatan, 6:kat, 7:sekolah, 8:skey, 9:matched]
            $masterItems[] = [
                (int) $rec->id,
                trim((string) $rec->nama_lengkap),
                $normName,
                self::normalizeGenderCode($rec->jenis_kelamin),
                trim((string) $rec->status),
                trim((string) $rec->jabatan),
                self::classifyKategori($rec->jabatan),
                $rawSekolah,
                $schoolKey,
                false,
            ];
        }
        unset($masterSeen);

        // 2. Stream Shoe Recap records for Guru & Tendik ONLY
        $shoeCursor = DB::table('shoe_recipients')
            ->leftJoin('schools', 'shoe_recipients.school_id', '=', 'schools.id')
            ->select([
                'shoe_recipients.id',
                'shoe_recipients.nama_lengkap',
                'shoe_recipients.jenis_kelamin',
                'shoe_recipients.jabatan',
                'shoe_recipients.kategori',
                'shoe_recipients.ukuran_sepatu',
                'shoe_recipients.panjang_kaki',
                'shoe_recipients.sheet_source',
                'schools.nama_sekolah',
                'schools.pic_nama',
                'schools.kota',
            ])
            ->where(function ($q) {
                $q->whereIn('shoe_recipients.kategori', ['guru', 'tendik'])
                  ->orWhere('shoe_recipients.sheet_source', 'like', '%GURU%')
                  ->orWhere('shoe_recipients.sheet_source', 'like', '%TENDIK%');
            })
            ->where(function ($q) {
                $q->whereNull('shoe_recipients.sheet_source')
                  ->orWhere(function ($sq) {
                      $sq->where('shoe_recipients.sheet_source', 'not like', '%SRD%')
                         ->where('shoe_recipients.sheet_source', 'not like', '%SRMP%')
                         ->where('shoe_recipients.sheet_source', 'not like', '%SRMA%');
                  });
            })
            ->orderBy('shoe_recipients.id', 'desc')
            ->cursor();

        $shoeItems = [];
        $shoeSeen = [];
        foreach ($shoeCursor as $rec) {
            $schoolName = trim((string) ($rec->nama_sekolah ?? 'Sekolah Tidak Diketahui'));
            if (!isset($schoolKeyCache[$schoolName])) {
                $schoolKeyCache[$schoolName] = self::normalizeSchoolKey($schoolName);
            }
            $schoolKey = $schoolKeyCache[$schoolName];
            $normName = self::normalizePersonName($rec->nama_lengkap);
            if ($normName === '') {
                continue;
            }
            $dedupKey = $schoolKey . '||' . $normName;
            if (isset($shoeSeen[$dedupKey])) {
                continue;
            }
            $shoeSeen[$dedupKey] = true;

            $ukuran = $rec->ukuran_sepatu !== null ? (int) $rec->ukuran_sepatu : null;

            // Compact indexed array: [0:id, 1:nama, 2:norm, 3:jk, 4:jabatan, 5:kat, 6:ukuran, 7:panjang, 8:anomaly, 9:sekolah, 10:pic, 11:kota, 12:skey, 13:matched]
            $shoeItems[] = [
                (int) $rec->id,
                trim((string) $rec->nama_lengkap),
                $normName,
                self::normalizeGenderCode($rec->jenis_kelamin),
                trim((string) $rec->jabatan),
                self::classifyKategori($rec->jabatan, $rec->kategori),
                $ukuran,
                $rec->panjang_kaki !== null ? (float) $rec->panjang_kaki : null,
                ($ukuran === null || $ukuran > 46 || $ukuran < 26),
                $schoolName,
                $rec->pic_nama,
                $rec->kota,
                $schoolKey,
                false,
            ];
        }
        unset($shoeSeen);

        // Index shoe items by school_key for O(1) school lookup
        $shoeBySchool = [];
        foreach ($shoeItems as $idx => $sItem) {
            $shoeBySchool[$sItem[12]][] = $idx;
        }

        // 3. Perform Multi-Tier Matching
        $matches = []; // masterIdx => [0:shoeIdx, 1:matchType]

        // Tier 1: Exact normalized name match in the same school
        foreach ($masterItems as $mIdx => $mItem) {
            $sKey = $mItem[8];
            if (empty($shoeBySchool[$sKey])) {
                continue;
            }
            foreach ($shoeBySchool[$sKey] as $sIdx) {
                if ($shoeItems[$sIdx][13]) {
                    continue;
                }
                if ($mItem[2] === $shoeItems[$sIdx][2]) {
                    $masterItems[$mIdx][9] = true;
                    $shoeItems[$sIdx][13] = true;
                    $matches[$mIdx] = [$sIdx, 'exact'];
                    break;
                }
            }
        }

        // Tier 2: Similar/Prefix name match in the same school (same gender)
        foreach ($masterItems as $mIdx => $mItem) {
            if ($masterItems[$mIdx][9]) {
                continue;
            }
            $sKey = $mItem[8];
            if (empty($shoeBySchool[$sKey])) {
                continue;
            }
            foreach ($shoeBySchool[$sKey] as $sIdx) {
                if ($shoeItems[$sIdx][13]) {
                    continue;
                }
                if ($mItem[3] !== '' && $shoeItems[$sIdx][3] !== '' && $mItem[3] !== $shoeItems[$sIdx][3]) {
                    continue;
                }
                if ($this->isSimilarName($mItem[2], $shoeItems[$sIdx][2])) {
                    $masterItems[$mIdx][9] = true;
                    $shoeItems[$sIdx][13] = true;
                    $matches[$mIdx] = [$sIdx, 'similar'];
                    break;
                }
            }
        }
        unset($shoeBySchool);

        // Tier 3: Cross-school exact name match (for multi-word names >= 10 chars)
        $unmatchedShoeByNormName = [];
        foreach ($shoeItems as $sIdx => $sItem) {
            if (!$sItem[13] && strlen($sItem[2]) >= 10 && str_contains($sItem[2], ' ')) {
                $unmatchedShoeByNormName[$sItem[2]][] = $sIdx;
            }
        }

        foreach ($masterItems as $mIdx => $mItem) {
            if ($masterItems[$mIdx][9]) {
                continue;
            }
            $nName = $mItem[2];
            if (!empty($unmatchedShoeByNormName[$nName])) {
                foreach ($unmatchedShoeByNormName[$nName] as $sIdx) {
                    if (!$shoeItems[$sIdx][13]) {
                        $masterItems[$mIdx][9] = true;
                        $shoeItems[$sIdx][13] = true;
                        $matches[$mIdx] = [$sIdx, 'cross_school'];
                        break;
                    }
                }
            }
        }
        unset($unmatchedShoeByNormName);

        // 4. Build School Map
        $schoolMap = [];
        foreach ($masterItems as $mIdx => $mItem) {
            $sKey = $mItem[8] ?: 'tanpa-sekolah';
            if (!isset($schoolMap[$sKey])) {
                $schoolMap[$sKey] = [
                    'school_key' => $sKey,
                    'nama_sekolah' => $mItem[7],
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
            if ($mItem[3] === 'L') {
                $schoolMap[$sKey]['master_l']++;
            } elseif ($mItem[3] === 'P') {
                $schoolMap[$sKey]['master_p']++;
            }
            if ($mItem[6] === 'Guru') {
                $schoolMap[$sKey]['master_guru']++;
            } else {
                $schoolMap[$sKey]['master_tendik']++;
            }

            if (isset($matches[$mIdx])) {
                $schoolMap[$sKey]['sudah_ada']++;
            } else {
                $schoolMap[$sKey]['belum_ada']++;
            }
        }

        foreach ($shoeItems as $sItem) {
            $sKey = $sItem[12] ?: 'tanpa-sekolah';
            if (!isset($schoolMap[$sKey])) {
                $schoolMap[$sKey] = [
                    'school_key' => $sKey,
                    'nama_sekolah' => $sItem[9],
                    'nama_sekolah_sepatu' => $sItem[9],
                    'pic_nama' => $sItem[10],
                    'kota' => $sItem[11],
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
                $schoolMap[$sKey]['nama_sekolah_sepatu'] = $sItem[9];
                if (!empty($sItem[10])) {
                    $schoolMap[$sKey]['pic_nama'] = $sItem[10];
                }
                if (!empty($sItem[11])) {
                    $schoolMap[$sKey]['kota'] = $sItem[11];
                }
            }
            $schoolMap[$sKey]['shoe_total']++;
            if ($sItem[3] === 'L') {
                $schoolMap[$sKey]['shoe_l']++;
            } elseif ($sItem[3] === 'P') {
                $schoolMap[$sKey]['shoe_p']++;
            }
            if ($sItem[5] === 'Guru') {
                $schoolMap[$sKey]['shoe_guru']++;
            } else {
                $schoolMap[$sKey]['shoe_tendik']++;
            }
            if (!$sItem[13]) {
                $schoolMap[$sKey]['hanya_di_sepatu']++;
            }
        }

        // Compute school statuses
        $schoolsComplete = 0;
        $schoolsPartial = 0;
        $schoolsEmpty = 0;
        $schoolsWithShoe = 0;
        $schoolsMasterCount = 0;
        $schoolStatusLookup = [];

        foreach ($schoolMap as $sKey => &$sc) {
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
            $schoolStatusLookup[$sKey] = $statusSekolah;
        }
        unset($sc);

        // Prepare active filter parameters
        $filterStatus = trim((string) ($filters['match_status'] ?? ''));
        $filterSchool = trim((string) ($filters['sekolah'] ?? ''));
        $filterSchoolKey = $filterSchool !== '' ? self::normalizeSchoolKey($filterSchool) : '';
        $filterGender = self::normalizeGenderCode($filters['jenis_kelamin'] ?? '');
        $filterKategori = strtolower(trim((string) ($filters['kategori'] ?? '')));
        $filterStatusPeg = strtoupper(trim((string) ($filters['status_kepegawaian'] ?? '')));
        $filterSchoolStatus = trim((string) ($filters['status_sekolah'] ?? ''));
        $filterSearch = strtolower(trim((string) ($filters['search'] ?? '')));

        // Counters for global summary
        $exactMatchCount = 0;
        $similarMatchCount = 0;
        $crossSchoolMatchCount = 0;
        $diffAttrCount = 0;
        $onlyInShoeCount = 0;

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

        // Store lightweight candidate descriptors: [0:isMaster(bool), 1:idx(int), 2:sortGroup(int)]
        // sortGroup:
        // 1 = school has shoe data + sudah_ada
        // 2 = school has shoe data + hanya_di_sepatu
        // 3 = school has shoe data + belum_ada
        // 4 = school has NO shoe data + sudah_ada (cross school)
        // 5 = school has NO shoe data + belum_ada
        $candidateRefs = [];

        foreach ($masterItems as $mIdx => $mItem) {
            $sKey = $mItem[8] ?: 'tanpa-sekolah';
            $jk = $mItem[3] ?: 'L';
            $kat = $mItem[6];
            $statusPeg = $mItem[4] !== '' ? strtoupper($mItem[4]) : 'LAINNYA';
            $schoolHasShoe = (($schoolMap[$sKey]['shoe_total'] ?? 0) > 0);

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
                $sIdx = $matches[$mIdx][0];
                $mType = $matches[$mIdx][1];
                $sItem = $shoeItems[$sIdx];

                if ($mType === 'exact') {
                    $exactMatchCount++;
                } elseif ($mType === 'similar') {
                    $similarMatchCount++;
                } else {
                    $crossSchoolMatchCount++;
                }

                if (isset($genderStats[$jk])) {
                    $genderStats[$jk]['sudah_ada']++;
                }
                if (isset($kategoriStats[$kat])) {
                    $kategoriStats[$kat]['sudah_ada']++;
                }
                $statusKepegawaianMap[$statusPeg]['sudah_ada']++;

                $hasDiff = (
                    ($mItem[3] !== '' && $sItem[3] !== '' && $mItem[3] !== $sItem[3]) ||
                    ($mItem[6] !== $sItem[5]) ||
                    ($mType === 'cross_school')
                );
                if ($hasDiff) {
                    $diffAttrCount++;
                    $schoolMap[$sKey]['diff_attr']++;
                }

                $size = $sItem[6];
                if ($size) {
                    if (!isset($shoeSizeMap[$size])) {
                        $shoeSizeMap[$size] = ['ukuran' => $size, 'total' => 0, 'laki_laki' => 0, 'perempuan' => 0, 'sudah_match' => 0, 'hanya_sepatu' => 0];
                    }
                    $shoeSizeMap[$size]['total']++;
                    $shoeSizeMap[$size]['sudah_match']++;
                    if ($sItem[3] === 'P') {
                        $shoeSizeMap[$size]['perempuan']++;
                    } else {
                        $shoeSizeMap[$size]['laki_laki']++;
                    }
                }

                if ($filterStatus !== '') {
                    if ($filterStatus === 'diff_attr' && !$hasDiff) {
                        continue;
                    }
                    if ($filterStatus !== 'diff_attr' && $filterStatus !== 'sudah_ada') {
                        continue;
                    }
                }
                if ($filterSchoolKey !== '' && $sKey !== $filterSchoolKey) {
                    continue;
                }
                if ($filterSchoolStatus !== '' && ($schoolStatusLookup[$sKey] ?? '') !== $filterSchoolStatus) {
                    continue;
                }
                $effJk = $mItem[3] ?: $sItem[3];
                if ($filterGender !== '' && $effJk !== $filterGender) {
                    continue;
                }
                if ($filterKategori !== '' && strtolower($mItem[6]) !== $filterKategori) {
                    continue;
                }
                if ($filterStatusPeg !== '' && $statusPeg !== $filterStatusPeg) {
                    continue;
                }
                if ($filterSearch !== '') {
                    $hay = strtolower($mItem[1] . ' ' . $sItem[1] . ' ' . $mItem[7] . ' ' . $sItem[9] . ' ' . $mItem[5] . ' ' . $sItem[4] . ' ' . $mItem[4]);
                    if (!str_contains($hay, $filterSearch)) {
                        continue;
                    }
                }

                $candidateRefs[] = [true, $mIdx, $schoolHasShoe ? 1 : 4];
            } else {
                if (isset($genderStats[$jk])) {
                    $genderStats[$jk]['belum_ada']++;
                }
                if (isset($kategoriStats[$kat])) {
                    $kategoriStats[$kat]['belum_ada']++;
                }
                $statusKepegawaianMap[$statusPeg]['belum_ada']++;

                if ($filterStatus !== '' && $filterStatus !== 'belum_ada') {
                    continue;
                }
                if ($filterSchoolKey !== '' && $sKey !== $filterSchoolKey) {
                    continue;
                }
                if ($filterSchoolStatus !== '' && ($schoolStatusLookup[$sKey] ?? '') !== $filterSchoolStatus) {
                    continue;
                }
                if ($filterGender !== '' && $mItem[3] !== $filterGender) {
                    continue;
                }
                if ($filterKategori !== '' && strtolower($mItem[6]) !== $filterKategori) {
                    continue;
                }
                if ($filterStatusPeg !== '' && $statusPeg !== $filterStatusPeg) {
                    continue;
                }
                if ($filterSearch !== '') {
                    $hay = strtolower($mItem[1] . ' ' . $mItem[7] . ' ' . $mItem[5] . ' ' . $mItem[4]);
                    if (!str_contains($hay, $filterSearch)) {
                        continue;
                    }
                }

                $candidateRefs[] = [true, $mIdx, $schoolHasShoe ? 3 : 5];
            }
        }

        // Process Unmatched Shoe Recap rows (Hanya Ada di Rekap Sepatu)
        foreach ($shoeItems as $sIdx => $sItem) {
            if ($sItem[13]) {
                continue;
            }
            $onlyInShoeCount++;
            $sKey = $sItem[12] ?: 'tanpa-sekolah';
            $jk = $sItem[3] ?: 'L';
            $kat = $sItem[5];

            if (isset($genderStats[$jk])) {
                $genderStats[$jk]['hanya_di_sepatu']++;
            }
            if (isset($kategoriStats[$kat])) {
                $kategoriStats[$kat]['hanya_di_sepatu']++;
            }

            $size = $sItem[6];
            if ($size) {
                if (!isset($shoeSizeMap[$size])) {
                    $shoeSizeMap[$size] = ['ukuran' => $size, 'total' => 0, 'laki_laki' => 0, 'perempuan' => 0, 'sudah_match' => 0, 'hanya_sepatu' => 0];
                }
                $shoeSizeMap[$size]['total']++;
                $shoeSizeMap[$size]['hanya_sepatu']++;
                if ($sItem[3] === 'P') {
                    $shoeSizeMap[$size]['perempuan']++;
                } else {
                    $shoeSizeMap[$size]['laki_laki']++;
                }
            }

            if ($filterStatus !== '' && $filterStatus !== 'hanya_di_sepatu') {
                continue;
            }
            if ($filterSchoolKey !== '' && $sKey !== $filterSchoolKey) {
                continue;
            }
            if ($filterSchoolStatus !== '' && ($schoolStatusLookup[$sKey] ?? '') !== $filterSchoolStatus) {
                continue;
            }
            if ($filterGender !== '' && $sItem[3] !== $filterGender) {
                continue;
            }
            if ($filterKategori !== '' && strtolower($sItem[5]) !== $filterKategori) {
                continue;
            }
            if ($filterStatusPeg !== '') {
                continue;
            }
            if ($filterSearch !== '') {
                $hay = strtolower($sItem[1] . ' ' . $sItem[9] . ' ' . $sItem[4]);
                if (!str_contains($hay, $filterSearch)) {
                    continue;
                }
            }

            $candidateRefs[] = [false, $sIdx, 2];
        }

        // Sort candidateRefs by sortGroup (1..5), then school name, then person name
        usort($candidateRefs, function ($a, $b) use ($masterItems, $shoeItems, $schoolMap) {
            if ($a[2] !== $b[2]) {
                return $a[2] <=> $b[2];
            }
            $schoolA = $a[0] ? $masterItems[$a[1]][7] : ($schoolMap[$shoeItems[$a[1]][12]]['nama_sekolah'] ?? $shoeItems[$a[1]][9]);
            $schoolB = $b[0] ? $masterItems[$b[1]][7] : ($schoolMap[$shoeItems[$b[1]][12]]['nama_sekolah'] ?? $shoeItems[$b[1]][9]);
            $cmpS = strcmp($schoolA, $schoolB);
            if ($cmpS !== 0) {
                return $cmpS;
            }
            $nameA = $a[0] ? $masterItems[$a[1]][1] : $shoeItems[$a[1]][1];
            $nameB = $b[0] ? $masterItems[$b[1]][1] : $shoeItems[$b[1]][1];
            return strcmp($nameA, $nameB);
        });

        $totalFilteredCount = count($candidateRefs);

        // If pagination is requested, slice candidateRefs BEFORE building heavy associative arrays!
        $refsToExpand = $page !== null
            ? array_slice($candidateRefs, max(0, ($page - 1) * $perPage), $perPage)
            : $candidateRefs;
        unset($candidateRefs);

        $filteredRows = [];
        foreach ($refsToExpand as $ref) {
            $isMaster = $ref[0];
            $idx = $ref[1];

            if ($isMaster) {
                $mItem = $masterItems[$idx];
                $sKey = $mItem[8] ?: 'tanpa-sekolah';

                if (isset($matches[$idx])) {
                    $sIdx = $matches[$idx][0];
                    $mType = $matches[$idx][1];
                    $sItem = $shoeItems[$sIdx];

                    $diffs = [];
                    if ($mItem[3] !== '' && $sItem[3] !== '' && $mItem[3] !== $sItem[3]) {
                        $diffs[] = "Jenis Kelamin ({$mItem[3]} vs {$sItem[3]})";
                    }
                    if ($mItem[6] !== $sItem[5]) {
                        $diffs[] = "Kategori ({$mItem[6]} vs {$sItem[5]})";
                    }
                    if ($mType === 'cross_school') {
                        $diffs[] = "Sekolah ({$mItem[7]} vs {$sItem[9]})";
                    }
                    $hasDiff = !empty($diffs);

                    $matchNote = match ($mType) {
                        'exact' => 'Cocok Persis',
                        'similar' => "Kemiripan Nama (Sepatu: {$sItem[1]})",
                        'cross_school' => "Cocok Lintas Sekolah ({$sItem[9]})",
                        default => 'Cocok',
                    };
                    if ($hasDiff) {
                        $matchNote .= ' • Beda ' . implode(', ', $diffs);
                    }

                    $filteredRows[] = [
                        'id' => 'M-' . $mItem[0],
                        'master_id' => $mItem[0],
                        'shoe_id' => $sItem[0],
                        'match_status' => 'sudah_ada',
                        'match_status_label' => 'Sudah Ada Data Sepatu',
                        'match_type' => $mType,
                        'match_note' => $matchNote,
                        'has_attribute_diff' => $hasDiff,
                        'attribute_diffs' => $diffs,
                        'school_key' => $sKey,
                        'sekolah' => $mItem[7],
                        'sekolah_master' => $mItem[7],
                        'sekolah_sepatu' => $sItem[9],
                        'pic_nama' => $sItem[10] ?? $schoolMap[$sKey]['pic_nama'],
                        'nama_display' => $mItem[1],
                        'nama_master' => $mItem[1],
                        'nama_sepatu' => $sItem[1],
                        'jk_code' => $mItem[3] ?: $sItem[3],
                        'jk_master' => self::formatGenderLabel($mItem[3]),
                        'jk_sepatu' => self::formatGenderLabel($sItem[3]),
                        'status_kepegawaian' => $mItem[4],
                        'jabatan_master' => $mItem[5],
                        'jabatan_sepatu' => $sItem[4],
                        'kategori' => $mItem[6],
                        'ukuran_sepatu' => $sItem[6],
                        'panjang_kaki' => $sItem[7],
                        'is_anomaly' => $sItem[8],
                    ];
                } else {
                    $schoolHasShoeData = ($schoolMap[$sKey]['shoe_total'] ?? 0) > 0;
                    $missingReason = $schoolHasShoeData
                        ? 'Sekolah sudah kirim rekap sepatu, namun nama ini belum tercantum'
                        : 'Sekolah belum mengunggah data rekapitulasi sepatu';

                    $filteredRows[] = [
                        'id' => 'M-' . $mItem[0],
                        'master_id' => $mItem[0],
                        'shoe_id' => null,
                        'match_status' => 'belum_ada',
                        'match_status_label' => 'Belum Ada Data Sepatu',
                        'match_type' => 'unmatched',
                        'match_note' => $missingReason,
                        'has_attribute_diff' => false,
                        'attribute_diffs' => [],
                        'school_key' => $sKey,
                        'sekolah' => $mItem[7],
                        'sekolah_master' => $mItem[7],
                        'sekolah_sepatu' => null,
                        'pic_nama' => $schoolMap[$sKey]['pic_nama'] ?? null,
                        'nama_display' => $mItem[1],
                        'nama_master' => $mItem[1],
                        'nama_sepatu' => null,
                        'jk_code' => $mItem[3],
                        'jk_master' => self::formatGenderLabel($mItem[3]),
                        'jk_sepatu' => '-',
                        'status_kepegawaian' => $mItem[4],
                        'jabatan_master' => $mItem[5],
                        'jabatan_sepatu' => '-',
                        'kategori' => $mItem[6],
                        'ukuran_sepatu' => null,
                        'panjang_kaki' => null,
                        'is_anomaly' => false,
                    ];
                }
            } else {
                $sItem = $shoeItems[$idx];
                $sKey = $sItem[12] ?: 'tanpa-sekolah';

                $filteredRows[] = [
                    'id' => 'S-' . $sItem[0],
                    'master_id' => null,
                    'shoe_id' => $sItem[0],
                    'match_status' => 'hanya_di_sepatu',
                    'match_status_label' => 'Hanya di Rekap Sepatu',
                    'match_type' => 'only_in_shoe',
                    'match_note' => 'Tercatat di rekap ukuran sepatu, tetapi belum terdaftar di Database Guru & Tendik',
                    'has_attribute_diff' => false,
                    'attribute_diffs' => [],
                    'school_key' => $sKey,
                    'sekolah' => $schoolMap[$sKey]['nama_sekolah'] ?? $sItem[9],
                    'sekolah_master' => null,
                    'sekolah_sepatu' => $sItem[9],
                    'pic_nama' => $sItem[10],
                    'nama_display' => $sItem[1],
                    'nama_master' => null,
                    'nama_sepatu' => $sItem[1],
                    'jk_code' => $sItem[3],
                    'jk_master' => '-',
                    'jk_sepatu' => self::formatGenderLabel($sItem[3]),
                    'status_kepegawaian' => '-',
                    'jabatan_master' => '-',
                    'jabatan_sepatu' => $sItem[4],
                    'kategori' => $sItem[5],
                    'ukuran_sepatu' => $sItem[6],
                    'panjang_kaki' => $sItem[7],
                    'is_anomaly' => $sItem[8],
                ];
            }
        }

        $totalMaster = count($masterItems);
        $totalShoeRecap = count($shoeItems);
        $sudahAdaCount = count($matches);
        unset($masterItems, $shoeItems, $matches, $refsToExpand);

        // Sort schoolComparisons: schools with shoe data first, then by completion percentage desc, then by name asc
        $schoolComparisons = array_values($schoolMap);
        unset($schoolMap);
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
        foreach ($genderStats as &$g) {
            $g['persentase'] = $g['master'] > 0 ? round(($g['sudah_ada'] / $g['master']) * 100, 1) : 0.0;
        }
        unset($g);

        foreach ($kategoriStats as &$kt) {
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
            'filtered_rows' => $filteredRows,
            'total_filtered_count' => $totalFilteredCount,
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
        @set_time_limit(300);
        @ini_set('memory_limit', '1024M');

        $data = $this->compare($filters, null);
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

        $s1Rows = [];
        foreach ($schools as $idx => $sc) {
            $s1Rows[] = [
                $idx + 1,
                $sc['nama_sekolah'],
                $sc['nama_sekolah_sepatu'] ?? '-',
                $sc['pic_nama'] ?? '-',
                "{$sc['master_total']} ({$sc['master_l']}L / {$sc['master_p']}P)",
                $sc['master_guru'],
                $sc['master_tendik'],
                $sc['shoe_total'],
                $sc['sudah_ada'],
                $sc['belum_ada'],
                $sc['hanya_di_sepatu'],
                $sc['persentase'] . '%',
            ];
        }
        if (!empty($s1Rows)) {
            $sheet1->fromArray($s1Rows, null, 'A5');
            $this->styleDataRows($sheet1, 'A5:L' . (4 + count($s1Rows)));
        }
        $s1Widths = ['A' => 6, 'B' => 30, 'C' => 35, 'D' => 18, 'E' => 20, 'F' => 14, 'G' => 14, 'H' => 18, 'I' => 20, 'J' => 20, 'K' => 20, 'L' => 16];
        foreach ($s1Widths as $col => $w) {
            $sheet1->getColumnDimension($col)->setWidth($w);
        }

        // =========================================================================
        // SHEET 2: SUDAH ADA DATA SEPATU
        // =========================================================================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Sudah Ada Data Sepatu');
        $matchedRows = array_values(array_filter($rows, fn ($item) => $item['match_status'] === 'sudah_ada'));
        $this->populatePersonSheet($sheet2, 'DAFTAR GURU & TENDIK YANG SUDAH ADA DATA SEPATU', $matchedRows, '059669');
        unset($matchedRows);

        // =========================================================================
        // SHEET 3: BELUM ADA DATA SEPATU
        // =========================================================================
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Belum Ada Data Sepatu');
        $missingRows = array_values(array_filter($rows, fn ($item) => $item['match_status'] === 'belum_ada'));
        $this->populatePersonSheet($sheet3, 'DAFTAR GURU & TENDIK YANG BELUM ADA DATA SEPATU', $missingRows, 'DC2626');
        unset($missingRows);

        // =========================================================================
        // SHEET 4: HANYA DI REKAP SEPATU
        // =========================================================================
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Hanya di Rekap Sepatu');
        $onlyShoeRows = array_values(array_filter($rows, fn ($item) => $item['match_status'] === 'hanya_di_sepatu'));
        $this->populatePersonSheet($sheet4, 'DAFTAR GURU & TENDIK HANYA DI REKAP SEPATU (BELUM DI MASTER)', $onlyShoeRows, 'D97706');
        unset($onlyShoeRows, $rows);

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

        $arrayData = [];
        foreach ($rows as $idx => $item) {
            $arrayData[] = [
                $idx + 1,
                $item['match_status_label'],
                $item['sekolah'],
                $item['nama_master'] ?? '-',
                $item['nama_sepatu'] ?? '-',
                $item['jk_master'],
                $item['jk_sepatu'],
                $item['status_kepegawaian'],
                $item['jabatan_master'],
                $item['jabatan_sepatu'],
                $item['ukuran_sepatu'] ?? '-',
                $item['panjang_kaki'] ?? '-',
                $item['match_note'],
            ];
        }

        if (!empty($arrayData)) {
            $sheet->fromArray($arrayData, null, 'A4');
            $this->styleDataRows($sheet, 'A4:M' . (3 + count($arrayData)));
        }

        $widths = [
            'A' => 6,
            'B' => 24,
            'C' => 28,
            'D' => 32,
            'E' => 32,
            'F' => 14,
            'G' => 14,
            'H' => 18,
            'I' => 22,
            'J' => 22,
            'K' => 14,
            'L' => 16,
            'M' => 42,
        ];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
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
