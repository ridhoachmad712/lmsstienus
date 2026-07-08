<?php

namespace App\Services;

use App\Models\Semester;
use App\Models\User;
use App\Support\Grades;

class Transcript
{
    /**
     * Rekam akademik mahasiswa: mata kuliah per semester + IPS + IPK.
     * IPS/IPK hanya menghitung kelas yang SUDAH SELESAI (nilai final) & ber-SKS.
     *
     * @return array{periods: array, ipk: float, total_sks: int}
     */
    public function forStudent(User $student): array
    {
        $courses = $student->enrolledCourses()->with('mataKuliah')->get();
        $calc = new GradeCalculator();

        $periods = [];
        foreach ($courses as $course) {
            $row = $calc->forStudent($course, $student)['row'] ?? null;
            $final = $row['final'] ?? null;
            $letter = $row['letter'] ?? '-';
            $sks = (int) ($course->mataKuliah->sks ?? 0);
            $counted = $course->isCompleted() && $sks > 0;
            $point = Grades::point($letter);

            $key = $course->year.'-'.$course->semester;
            $periods[$key] ??= [
                'label' => Semester::keyLabel($key),
                'sort' => Semester::sortValue($key),
                'items' => [],
                'sks' => 0,
                'mutu' => 0.0,
            ];

            $periods[$key]['items'][] = [
                'course' => $course,
                'name' => $course->mataKuliah->name ?? $course->name,
                'code' => $course->mataKuliah->code ?? $course->code,
                'sks' => $sks,
                'final' => $final,
                'letter' => $counted ? $letter : null, // null = belum final (berjalan)
                'point' => $point,
                'counted' => $counted,
                'running' => ! $course->isCompleted(),
            ];

            if ($counted) {
                $periods[$key]['sks'] += $sks;
                $periods[$key]['mutu'] += $sks * $point;
            }
        }

        // Urut kronologis + hitung IPS per periode.
        uasort($periods, fn ($a, $b) => $a['sort'] <=> $b['sort']);
        foreach ($periods as &$p) {
            $p['ips'] = $p['sks'] > 0 ? round($p['mutu'] / $p['sks'], 2) : null;
        }
        unset($p);

        $totalSks = array_sum(array_column($periods, 'sks'));
        $totalMutu = array_sum(array_column($periods, 'mutu'));

        return [
            'periods' => $periods,
            'ipk' => $totalSks > 0 ? round($totalMutu / $totalSks, 2) : 0.0,
            'total_sks' => $totalSks,
        ];
    }
}
