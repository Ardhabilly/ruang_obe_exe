<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\CourseLesson;
use App\Models\PracticeSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PracticeController extends Controller
{
    public function submit(Request $request, CourseLesson $lesson, string $practiceKey)
    {
        $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $practice = $this->getPracticeDefinition($practiceKey);

        abort_if(! $practice, 404, 'Latihan tidak ditemukan.');

        $answers = $request->input('answers', []);
        $errors = [];

        foreach ($practice['questions'] as $key => $question) {
            if (! isset($answers[$key]) || trim((string) $answers[$key]) === '') {
                $errors["answers.$key"] = 'Bagian ini wajib diisi.';
            }
        }

        if (! empty($errors)) {
            return back()
                ->withInput()
                ->withErrors($errors)
                ->with('warning', 'Lengkapi semua jawaban latihan terlebih dahulu.');
        }

        $feedback = [];
        $correct = 0;
        $total = count($practice['questions']);

        foreach ($practice['questions'] as $key => $question) {
            $studentAnswer = $this->normalize($answers[$key] ?? '');

            $acceptedAnswers = collect($question['accepted_answers'])
                ->map(fn ($answer) => $this->normalize($answer))
                ->toArray();

            $isCorrect = in_array($studentAnswer, $acceptedAnswers, true);

            if ($isCorrect) {
                $correct++;
            }

            $feedback[$key] = [
                'is_correct' => $isCorrect,
                'answer' => $answers[$key] ?? '',
                'message' => $isCorrect
                    ? $question['feedback_correct']
                    : $question['feedback_wrong'],
            ];
        }

        $score = $total > 0
            ? round(($correct / $total) * $practice['max_score'])
            : 0;

        PracticeSubmission::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'course_lesson_id' => $lesson->id,
                'practice_key' => $practiceKey,
            ],
            [
                'title' => $practice['title'],
                'type' => $practice['type'],
                'answers' => $answers,
                'feedback' => $feedback,
                'score' => $score,
                'max_score' => $practice['max_score'],
                'is_completed' => true,
                'submitted_at' => now(),
            ]
        );

        return back()->with('success', 'Jawaban latihan berhasil diperiksa dan disimpan.');
    }

    private function getPracticeDefinition(string $practiceKey): ?array
    {
        return match ($practiceKey) {
            'aktivitas-1-1' => [
                'title' => 'Aktivitas 1.1 - Laboratorium Validasi Aljabar',
                'type' => 'aktivitas',
                'max_score' => 100,
                'questions' => [
                    'q1_suku_bermasalah' => [
                        'accepted_answers' => ['tidak ada', 'tidakada'],
                        'feedback_correct' => 'Benar. Persamaan ini sudah linear, sehingga tidak ada suku bermasalah.',
                        'feedback_wrong' => 'Belum tepat. Pada persamaan ini semua variabel berpangkat satu dan tidak memuat akar, hasil kali antarvariabel, atau fungsi khusus.',
                    ],
                    'q1_pangkat' => [
                        'accepted_answers' => ['1', 'satu', 'pangkat satu'],
                        'feedback_correct' => 'Benar. Pangkat tertinggi semua variabel pada persamaan tersebut adalah 1.',
                        'feedback_wrong' => 'Belum tepat. Perhatikan x₁, x₂, dan x₃. Semua variabel pada persamaan tersebut berpangkat satu.',
                    ],
                    'q2_pelanggar' => [
                        'accepted_answers' => [
                            '3√y',
                            '√y',
                            '3sqrt y',
                            'sqrt y',
                            '3sqrt(y)',
                            'sqrt(y)',
                            '3 akar y',
                            'akar y',
                        ],
                        'feedback_correct' => 'Benar. Suku 3√y atau √y melanggar aturan linearitas karena variabel berada di dalam akar.',
                        'feedback_wrong' => 'Belum tepat. Cari suku yang memuat variabel di dalam tanda akar.',
                    ],
                    'q3_pelanggar' => [
                        'accepted_answers' => [
                            'x₁x₂',
                            'x1x2',
                            'x1 x2',
                            'x1*x2',
                            'x_1x_2',
                            'x_1 x_2',
                            'x_1*x_2',
                        ],
                        'feedback_correct' => 'Benar. Suku x₁x₂ melanggar aturan linearitas karena terjadi perkalian antarvariabel.',
                        'feedback_wrong' => 'Belum tepat. Cari suku yang menunjukkan dua variabel dikalikan secara langsung.',
                    ],
                ],
            ],

            default => null,
        };
    }

    private function normalize(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        $value = str_replace(['√', '−', '–'], ['sqrt', '-', '-'], $value);
        $value = str_replace(['₁', '₂', '₃'], ['1', '2', '3'], $value);
        $value = str_replace(['_', '*', '(', ')'], '', $value);
        $value = preg_replace('/\s+/', '', $value);

        return $value;
    }
}