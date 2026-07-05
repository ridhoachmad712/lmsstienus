<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Course;
use App\Models\GradeComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GradeComponentController extends Controller
{
    use ChecksCourseAccess;

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseOwner($request, $course);

        $data = $this->validated($request);
        $this->assertWeightWithin100($course, $data['weight']);

        $course->gradeComponents()->create($data);

        return back()->with('status', 'Komponen nilai ditambahkan.');
    }

    public function update(Request $request, GradeComponent $component): RedirectResponse
    {
        $this->ensureCourseOwner($request, $component->course);

        $data = $this->validated($request);
        $this->assertWeightWithin100($component->course, $data['weight'], $component->id);

        $component->update($data);

        return back()->with('status', 'Komponen nilai diperbarui.');
    }

    /**
     * Validasi input komponen. Nama hanya wajib untuk tipe "lainnya";
     * tipe lain memakai nama bawaan (mis. UTS, Aktivitas/Kehadiran).
     */
    private function validated(Request $request): array
    {
        $types = implode(',', array_keys(GradeComponent::TYPES));

        $data = $request->validate([
            'type' => ['required', 'in:'.$types],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
            'name' => ['nullable', 'string', 'max:100', 'required_if:type,lainnya'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['type'] !== 'lainnya') {
            $data['name'] = GradeComponent::defaultName($data['type']);
        }

        return $data;
    }

    public function destroy(Request $request, GradeComponent $component): RedirectResponse
    {
        $this->ensureCourseOwner($request, $component->course);
        $component->delete();

        return back()->with('status', 'Komponen nilai dihapus.');
    }

    private function assertWeightWithin100(Course $course, int $weight, ?int $exceptId = null): void
    {
        $existing = $course->gradeComponents()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->sum('weight');

        if ($existing + $weight > 100) {
            throw ValidationException::withMessages([
                'weight' => "Total bobot melebihi 100% (saat ini terpakai {$existing}%).",
            ]);
        }
    }
}
