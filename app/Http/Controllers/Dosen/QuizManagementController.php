<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\CourseModule;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QuizManagementController extends Controller
{
    public function index()
    {
        $classGroups = ClassGroup::query()
            ->where('dosen_id', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $classGroupIds = $classGroups->pluck('id');

        $quizzes = Quiz::query()
            ->with([
                'classGroup:id,name,kkm',
                'module:id,title,order_number',
            ])
            ->withCount(['questions', 'attempts'])
            ->whereIn('class_group_id', $classGroupIds)
            ->latest()
            ->get();

        $quizSummary = [
            'total' => $quizzes->count(),
            'active' => $quizzes->where('is_active', true)->count(),
            'draft' => $quizzes->where('is_active', false)->count(),
        ];

        return view('dosen.kuis.index', compact(
            'classGroups',
            'quizzes',
            'quizSummary'
        ));
    }

    public function create()
    {
        $classGroups = ClassGroup::query()
            ->where('dosen_id', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $modules = CourseModule::query()
            ->orderBy('order_number')
            ->get(['id', 'title', 'order_number']);

        return view('dosen.kuis.create', compact('classGroups', 'modules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_group_id' => ['required', 'integer'],
            'type' => ['required', Rule::in(['kuis_bab', 'evaluasi_akhir'])],
            'course_module_id' => [
                'nullable',
                'required_if:type,kuis_bab',
                'integer',
                Rule::exists('course_modules', 'id'),
            ],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'instruction' => ['nullable', 'string', 'max:3000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:180'],
        ]);

        $classGroup = ClassGroup::query()
            ->where('dosen_id', Auth::id())
            ->find($validated['class_group_id']);

        abort_if(! $classGroup, 403, 'Anda tidak memiliki akses ke kelas tersebut.');

        $type = $validated['type'];
        $courseModuleId = $type === 'kuis_bab'
            ? (int) $validated['course_module_id']
            : null;

        $duplicateQuery = Quiz::query()
            ->where('class_group_id', $classGroup->id)
            ->where('type', $type);

        if ($type === 'kuis_bab') {
            $duplicateQuery->where('course_module_id', $courseModuleId);
        } else {
            $duplicateQuery->whereNull('course_module_id');
        }

        if ($duplicateQuery->exists()) {
            $message = $type === 'kuis_bab'
                ? 'Kuis untuk bab yang dipilih pada kelas ini sudah tersedia.'
                : 'Evaluasi akhir untuk kelas ini sudah tersedia.';

            return back()
                ->withInput()
                ->withErrors(['type' => $message]);
        }

        Quiz::create([
            'class_group_id' => $classGroup->id,
            'course_module_id' => $courseModuleId,
            'title' => $validated['title'],
            'slug' => $this->makeUniqueSlug($validated['title'], $classGroup->id),
            'type' => $type,
            'description' => $validated['description'] ?? null,
            'instruction' => $validated['instruction'] ?? null,
            'duration_minutes' => (int) $validated['duration_minutes'],
            'max_attempts' => 3,
            'is_active' => false,
        ]);

        return redirect()
            ->route('dosen.kuis.index')
            ->with('success', 'Kuis berhasil dibuat sebagai draf. Tambahkan soal sebelum kuis diaktifkan.');
    }

    private function makeUniqueSlug(string $title, int $classGroupId): string
    {
        $baseSlug = Str::slug($title);

        if ($baseSlug === '') {
            $baseSlug = 'kuis';
        }

        $baseSlug = Str::limit($baseSlug, 180, '');

        do {
            $slug = $baseSlug
                . '-kelas-'
                . $classGroupId
                . '-'
                . Str::lower(Str::random(8));
        } while (Quiz::where('slug', $slug)->exists());

        return $slug;
    }
}