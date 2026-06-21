<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ClassGroupController extends Controller
{
    public function index()
    {
        $classGroups = ClassGroup::where('dosen_id', Auth::id())
            ->withCount('members')
            ->latest()
            ->get();

        return view('dosen.kelas.index', compact('classGroups'));
    }

    public function create()
    {
        return view('dosen.kelas.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ClassGroup::create([
            'dosen_id' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'kkm' => $validated['kkm'],
            'is_active' => $request->boolean('is_active'),
            'token' => $this->generateUniqueToken(),
        ]);

        return redirect()
            ->route('dosen.kelas.index')
            ->with('success', 'Kelas berhasil dibuat.');
    }

    public function show(ClassGroup $classGroup)
    {
        $this->ensureOwner($classGroup);

        $classGroup->load(['members.user']);

        return view('dosen.kelas.show', compact('classGroup'));
    }

    public function edit(ClassGroup $classGroup)
    {
        $this->ensureOwner($classGroup);

        return view('dosen.kelas.edit', compact('classGroup'));
    }

    public function update(Request $request, ClassGroup $classGroup)
    {
        $this->ensureOwner($classGroup);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $classGroup->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'kkm' => $validated['kkm'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('dosen.kelas.index')
            ->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(ClassGroup $classGroup)
    {
        $this->ensureOwner($classGroup);

        $classGroup->delete();

        return redirect()
            ->route('dosen.kelas.index')
            ->with('success', 'Kelas berhasil dihapus.');
    }

    public function regenerateToken(ClassGroup $classGroup)
    {
        $this->ensureOwner($classGroup);

        $classGroup->update([
            'token' => $this->generateUniqueToken(),
        ]);

        return back()->with('success', 'Token kelas berhasil diperbarui.');
    }

    private function ensureOwner(ClassGroup $classGroup): void
    {
        abort_if($classGroup->dosen_id !== Auth::id(), 403, 'Anda tidak memiliki akses ke kelas ini.');
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = strtoupper(Str::random(8));
        } while (ClassGroup::where('token', $token)->exists());

        return $token;
    }
}