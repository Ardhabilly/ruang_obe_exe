<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\ClassMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassJoinController extends Controller
{
    public function index()
    {
        $joinedClasses = Auth::user()
            ->joinedClassGroups()
            ->with('dosen')
            ->latest('class_members.joined_at')
            ->get();

        return view('mahasiswa.kelas.index', compact('joinedClasses'));
    }

    public function join(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:20'],
        ]);

        $token = strtoupper(trim($validated['token']));

        $classGroup = ClassGroup::where('token', $token)
            ->where('is_active', true)
            ->first();

        if (! $classGroup) {
            return back()
                ->withInput()
                ->withErrors([
                    'token' => 'Token kelas tidak ditemukan atau kelas sedang tidak aktif.',
                ]);
        }

        $alreadyJoined = ClassMember::where('class_group_id', $classGroup->id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($alreadyJoined) {
            return back()->with('success', 'Anda sudah tergabung dalam kelas tersebut.');
        }

        ClassMember::create([
            'class_group_id' => $classGroup->id,
            'user_id' => Auth::id(),
            'joined_at' => now(),
        ]);

        return redirect()
            ->route('mahasiswa.kelas.index')
            ->with('success', 'Berhasil bergabung ke kelas.');
    }
}