<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\ShoeRecipient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShoeDashboardController extends Controller
{
    /**
     * Display the interactive analytics and comparison dashboard.
     */
    public function index(Request $request): Response
    {
        $schoolId = $request->query('school_id');
        $compareSchoolId = $request->query('compare_school_id');
        $kategori = $request->query('kategori');
        $gender = $request->query('gender');
        $pic = $request->query('pic');

        $data = $this->getDashboardData($schoolId, $compareSchoolId, $kategori, $gender, $pic);

        return Inertia::render('Shoes/Dashboard', $data);
    }

    /**
     * JSON API endpoint for interactive dynamic fetching if needed.
     */
    public function apiData(Request $request)
    {
        $schoolId = $request->query('school_id');
        $compareSchoolId = $request->query('compare_school_id');
        $kategori = $request->query('kategori');
        $gender = $request->query('gender');
        $pic = $request->query('pic');

        return response()->json($this->getDashboardData($schoolId, $compareSchoolId, $kategori, $gender, $pic));
    }

    /**
     * Compile comprehensive dashboard & comparative metrics.
     */
    protected function getDashboardData($schoolId = null, $compareSchoolId = null, $kategori = null, $gender = null, $pic = null): array
    {
        $baseQuery = ShoeRecipient::query();

        if (!empty($schoolId)) {
            $baseQuery->where('school_id', $schoolId);
        }
        if (!empty($kategori)) {
            $baseQuery->where('kategori', $kategori);
        }
        if (!empty($gender)) {
            $baseQuery->where('jenis_kelamin', $gender);
        }
        if (!empty($pic)) {
            $normPic = School::normalizePicName($pic);
            $baseQuery->whereHas('school', fn($q) => $q->where('pic_nama', $normPic)->orWhere('pic_nama', 'PIC ' . $normPic));
        }

        // 1. Executive Summary KPIs
        $totalSepatu = (clone $baseQuery)->count();
        $totalPria = (clone $baseQuery)->where('jenis_kelamin', 'L')->count();
        $totalWanita = (clone $baseQuery)->where('jenis_kelamin', 'P')->count();
        $totalSiswa = (clone $baseQuery)->where('kategori', 'siswa')->count();
        $totalGuru = (clone $baseQuery)->where('kategori', 'guru')->count();
        $totalTendik = (clone $baseQuery)->where('kategori', 'tendik')->count();
        $avgFootLength = round((float)(clone $baseQuery)->whereNotNull('panjang_kaki')->where('panjang_kaki', '>', 0)->avg('panjang_kaki'), 1);

        // Top Demanded Size
        $topSizeRow = (clone $baseQuery)
            ->selectRaw('ukuran_sepatu, COUNT(*) as count')
            ->groupBy('ukuran_sepatu')
            ->orderByDesc('count')
            ->first();
        $topSize = $topSizeRow ? (int)$topSizeRow->ukuran_sepatu : null;
        $topSizeCount = $topSizeRow ? (int)$topSizeRow->count : 0;
        $topSizePct = $totalSepatu > 0 ? round(($topSizeCount / $totalSepatu) * 100, 1) : 0;

        // Size Range
        $minSize = (clone $baseQuery)->min('ukuran_sepatu') ?: 36;
        $maxSize = (clone $baseQuery)->max('ukuran_sepatu') ?: 45;
        $totalAnomalies = (clone $baseQuery)->anomaly()->count();
        $extremeAnomalies = (clone $baseQuery)->where('ukuran_sepatu', '>', 100)->count();

        // Total Schools registered (matching active filters)
        $totalSekolahQuery = School::has('recipients');
        if ($schoolId) {
            $totalSekolahQuery->where('id', $schoolId);
        }
        if ($pic) {
            $normPic = School::normalizePicName($pic);
            $totalSekolahQuery->where(fn($q) => $q->where('pic_nama', $normPic)->orWhere('pic_nama', 'PIC ' . $normPic));
        }
        $totalSekolah = $totalSekolahQuery->count();

        // 2. Comprehensive Size Breakdown by Category & Gender (Sizes 36–47)
        $matrixQuery = (clone $baseQuery)->selectRaw('
            ukuran_sepatu,
            COUNT(*) as total,
            SUM(CASE WHEN kategori = "siswa" AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as siswa_l,
            SUM(CASE WHEN kategori = "siswa" AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as siswa_p,
            SUM(CASE WHEN kategori IN ("guru", "tendik") AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as staff_l,
            SUM(CASE WHEN kategori IN ("guru", "tendik") AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as staff_p,
            SUM(CASE WHEN jenis_kelamin = "L" THEN 1 ELSE 0 END) as total_pria,
            SUM(CASE WHEN jenis_kelamin = "P" THEN 1 ELSE 0 END) as total_wanita
        ')
        ->groupBy('ukuran_sepatu')
        ->orderBy('ukuran_sepatu')
        ->get();

        $sizeMap = [];
        for ($s = 36; $s <= 46; $s++) {
            $sizeMap[$s] = [
                'size' => $s,
                'total' => 0,
                'siswa_l' => 0,
                'siswa_p' => 0,
                'staff_l' => 0,
                'staff_p' => 0,
                'total_pria' => 0,
                'total_wanita' => 0,
                'pct' => 0,
            ];
        }

        foreach ($matrixQuery as $row) {
            $sz = (int)$row->ukuran_sepatu;
            $sizeMap[$sz] = [
                'size' => $sz,
                'total' => (int)$row->total,
                'siswa_l' => (int)$row->siswa_l,
                'siswa_p' => (int)$row->siswa_p,
                'staff_l' => (int)$row->staff_l,
                'staff_p' => (int)$row->staff_p,
                'total_pria' => (int)$row->total_pria,
                'total_wanita' => (int)$row->total_wanita,
                'pct' => $totalSepatu > 0 ? round(((int)$row->total / $totalSepatu) * 100, 1) : 0,
            ];
        }
        ksort($sizeMap);
        $sizeDistribution = array_values($sizeMap);

        // 3. Vendor Size Grouping
        $groupKecil = 0; // 36 - 38
        $groupSedang = 0; // 39 - 41
        $groupBesar = 0; // 42 - 44
        $groupEkstra = 0; // 45+

        foreach ($sizeMap as $item) {
            $sz = $item['size'];
            $cnt = $item['total'];
            if ($sz <= 38) {
                $groupKecil += $cnt;
            } elseif ($sz <= 41) {
                $groupSedang += $cnt;
            } elseif ($sz <= 44) {
                $groupBesar += $cnt;
            } else {
                $groupEkstra += $cnt;
            }
        }

        $sizeGroups = [
            'kecil' => [
                'label' => 'Kecil (Size 36–38)',
                'count' => $groupKecil,
                'pct' => $totalSepatu > 0 ? round(($groupKecil / $totalSepatu) * 100, 1) : 0,
                'color' => '#06b6d4', // cyan
            ],
            'sedang' => [
                'label' => 'Sedang (Size 39–41)',
                'count' => $groupSedang,
                'pct' => $totalSepatu > 0 ? round(($groupSedang / $totalSepatu) * 100, 1) : 0,
                'color' => '#10b981', // emerald
            ],
            'besar' => [
                'label' => 'Besar (Size 42–44)',
                'count' => $groupBesar,
                'pct' => $totalSepatu > 0 ? round(($groupBesar / $totalSepatu) * 100, 1) : 0,
                'color' => '#6366f1', // indigo
            ],
            'ekstra' => [
                'label' => 'Ekstra (Size 45+)',
                'count' => $groupEkstra,
                'pct' => $totalSepatu > 0 ? round(($groupEkstra / $totalSepatu) * 100, 1) : 0,
                'color' => '#f59e0b', // amber
            ],
        ];

        // 4. School-by-School Summary & Comparative Dataset
        $schools = School::withCount('recipients')->get()->map(function ($sch) {
            $total = $sch->recipients_count;
            $siswa = ShoeRecipient::where('school_id', $sch->id)->where('kategori', 'siswa')->count();
            $staff = ShoeRecipient::where('school_id', $sch->id)->whereIn('kategori', ['guru', 'tendik'])->count();
            $pria = ShoeRecipient::where('school_id', $sch->id)->where('jenis_kelamin', 'L')->count();
            $wanita = ShoeRecipient::where('school_id', $sch->id)->where('jenis_kelamin', 'P')->count();

            // Top size for this school
            $topSz = ShoeRecipient::where('school_id', $sch->id)
                ->selectRaw('ukuran_sepatu, COUNT(*) as c')
                ->groupBy('ukuran_sepatu')
                ->orderByDesc('c')
                ->first();

            // Size counts map for side-by-side comparison chart
            $sizesRaw = ShoeRecipient::where('school_id', $sch->id)
                ->selectRaw('ukuran_sepatu, COUNT(*) as c')
                ->groupBy('ukuran_sepatu')
                ->pluck('c', 'ukuran_sepatu')
                ->toArray();

            $fullSizes = [];
            for ($s = 36; $s <= 46; $s++) {
                $fullSizes[$s] = isset($sizesRaw[$s]) ? (int)$sizesRaw[$s] : 0;
            }

            return [
                'id' => $sch->id,
                'nama_sekolah' => $sch->nama_sekolah,
                'kota' => $sch->kota ?? 'Indonesia',
                'pic_nama' => $sch->pic_nama,
                'kepala_sekolah_nama' => $sch->kepala_sekolah_nama,
                'total_recipients' => $total,
                'total_siswa' => $siswa,
                'total_staff' => $staff,
                'total_pria' => $pria,
                'total_wanita' => $wanita,
                'ratio_pria' => $total > 0 ? round(($pria / $total) * 100, 1) : 0,
                'ratio_wanita' => $total > 0 ? round(($wanita / $total) * 100, 1) : 0,
                'top_size' => $topSz ? (int)$topSz->ukuran_sepatu : null,
                'top_size_count' => $topSz ? (int)$topSz->c : 0,
                'sizes' => $fullSizes,
            ];
        })->sortByDesc('total_recipients')->values()->all();

        // 5. Comparison between School A and School B
        $schoolA = null;
        $schoolB = null;
        if (!empty($schoolId)) {
            $schoolA = collect($schools)->firstWhere('id', (int)$schoolId);
        } elseif (!empty($schools)) {
            $schoolA = $schools[0];
        }

        if (!empty($compareSchoolId)) {
            $schoolB = collect($schools)->firstWhere('id', (int)$compareSchoolId);
        } elseif (count($schools) > 1) {
            $schoolB = $schools[1];
        }

        $existingPics = School::whereNotNull('pic_nama')
            ->where('pic_nama', '!=', '')
            ->distinct()
            ->pluck('pic_nama')
            ->map(fn($p) => School::normalizePicName($p))
            ->filter()
            ->toArray();
        $allPics = array_values(array_unique(array_merge(School::DEFAULT_PICS, $existingPics)));
        sort($allPics);

        return [
            'summary' => [
                    'total_sepatu' => $totalSepatu,
                    'total_sekolah' => $totalSekolah,
                    'total_siswa' => $totalSiswa,
                    'total_guru' => $totalGuru,
                    'total_tendik' => $totalTendik,
                    'total_staff' => $totalGuru + $totalTendik,
                    'total_pria' => $totalPria,
                    'total_wanita' => $totalWanita,
                    'top_size' => $topSize,
                    'top_size_count' => $topSizeCount,
                    'top_size_pct' => $topSizePct,
                    'avg_foot_length' => $avgFootLength,
                    'min_size' => $minSize,
                    'max_size' => $maxSize,
                    'total_anomalies' => $totalAnomalies,
                    'extreme_anomalies' => $extremeAnomalies,
                ],
                'size_distribution' => $sizeDistribution,
                'size_groups' => $sizeGroups,
                'category_distribution' => [
                    ['name' => 'Siswa', 'value' => $totalSiswa, 'pct' => $totalSepatu > 0 ? round(($totalSiswa / $totalSepatu) * 100, 1) : 0],
                    ['name' => 'Guru', 'value' => $totalGuru, 'pct' => $totalSepatu > 0 ? round(($totalGuru / $totalSepatu) * 100, 1) : 0],
                    ['name' => 'Tenaga Kependidikan', 'value' => $totalTendik, 'pct' => $totalSepatu > 0 ? round(($totalTendik / $totalSepatu) * 100, 1) : 0],
                ],
                'gender_distribution' => [
                    ['name' => 'Laki-Laki', 'value' => $totalPria, 'pct' => $totalSepatu > 0 ? round(($totalPria / $totalSepatu) * 100, 1) : 0],
                    ['name' => 'Perempuan', 'value' => $totalWanita, 'pct' => $totalSepatu > 0 ? round(($totalWanita / $totalSepatu) * 100, 1) : 0],
                ],
                'schools' => $schools,
                'comparator' => [
                    'school_a' => $schoolA,
                    'school_b' => $schoolB,
                ],
                'pic_list' => $allPics,
                'filters' => [
                    'school_id' => $schoolId ? (int)$schoolId : null,
                    'compare_school_id' => $compareSchoolId ? (int)$compareSchoolId : null,
                    'kategori' => $kategori ?: '',
                    'gender' => $gender ?: '',
                    'pic' => $pic ?: '',
                ],
            ];
        }
}
