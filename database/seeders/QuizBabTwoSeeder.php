<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use App\Models\CourseModule;
use App\Models\Quiz;
use Illuminate\Database\Seeder;

class QuizBabTwoSeeder extends Seeder
{
    public function run(): void
    {
        $module = CourseModule::where('slug', 'bab-2-operasi-baris-elementer')->first();

        if (! $module) {
            return;
        }

        $instruction = <<<'TEXT'
Status: 6 Soal Tersedia
Estimasi Waktu Pengerjaan: 20 Menit

Instruksi:
Jawablah pertanyaan di bawah ini dengan cepat dan tepat. Anda diuji untuk memahami teknik operasional serta logika manipulasi baris pada matriks menggunakan Operasi Baris Elementer (OBE).

Petunjuk Pengerjaan Kuis Modul:
1. Bacalah setiap perubahan bentuk matriks, notasi operasi, dan target perhitungan dengan sangat teliti sebelum menentukan jawaban.
2. Perhatikan format jawaban yang diminta. Masukkan notasi OBE yang tepat, tuliskan langkah pengerjaan pada area yang tersedia, lalu lengkapi seluruh elemen matriks hasil operasi.
3. Anda dapat berpindah soal menggunakan panel navigasi angka. Pastikan setiap jawaban telah diperiksa kembali sebelum dikumpulkan.
4. Pastikan tidak ada soal yang terlewat sebelum menekan tombol Kumpulkan.

Peringatan:
Jangan memuat ulang halaman atau menutup halaman saat kuis berlangsung agar progres jawaban tidak hilang.
TEXT;

        $questions = [
            [
                'question_text' => 'Perhatikan matriks teraugmentasi berikut. Gunakan Operasi Baris Elementer untuk memindahkan angka 1 utama menuju posisi Baris-1.',
                'question_type' => 'obe_matrix_operation',
                'question_data' => [
                    'initial_matrix' => [
                        [0, 5, 10],
                        [1, 2, -3],
                    ],
                    'rows' => 2,
                    'columns' => 3,
                    'separator_before_column' => 3,
                    'canvas_required' => true,
                ],
                'answer_key' => [
                    'operation' => 'B1 ↔ B2',
                    'matrix' => [
                        [1, 2, -3],
                        [0, 5, 10],
                    ],
                ],
                'accepted_answers' => [
                    'B1 ↔ B2',
                    'B2 ↔ B1',
                    'B1 <-> B2',
                    'B2 <-> B1',
                ],
                'explanation' => 'Tukar Baris-1 dengan Baris-2 sehingga elemen 1 utama berada pada Baris-1.',
                'points' => 17,
                'order_number' => 1,
                'is_required' => true,
            ],
            [
                'question_text' => 'Perhatikan matriks teraugmentasi berikut. Gunakan Operasi Baris Elementer untuk menukar Baris-1 dengan baris di bawahnya yang sudah diawali oleh angka 1 utama.',
                'question_type' => 'obe_matrix_operation',
                'question_data' => [
                    'initial_matrix' => [
                        [0, 3, -1, 6],
                        [2, -4, 5, 1],
                        [1, 2, 3, 4],
                    ],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'canvas_required' => true,
                ],
                'answer_key' => [
                    'operation' => 'B1 ↔ B3',
                    'matrix' => [
                        [1, 2, 3, 4],
                        [2, -4, 5, 1],
                        [0, 3, -1, 6],
                    ],
                ],
                'accepted_answers' => [
                    'B1 ↔ B3',
                    'B3 ↔ B1',
                    'B1 <-> B3',
                    'B3 <-> B1',
                ],
                'explanation' => 'Tukar Baris-1 dengan Baris-3 karena Baris-3 sudah memiliki 1 utama pada kolom pertama.',
                'points' => 17,
                'order_number' => 2,
                'is_required' => true,
            ],
            [
                'question_text' => 'Perhatikan matriks teraugmentasi berikut. Gunakan Operasi Baris Elementer agar elemen bernilai tak nol pada Baris-2 Kolom-2 berubah menjadi 1 utama.',
                'question_type' => 'obe_matrix_operation',
                'question_data' => [
                    'initial_matrix' => [
                        [1, -2, 5],
                        [0, 4, 12],
                    ],
                    'rows' => 2,
                    'columns' => 3,
                    'separator_before_column' => 3,
                    'canvas_required' => true,
                ],
                'answer_key' => [
                    'operation' => 'B2 ← 1/4 B2',
                    'matrix' => [
                        [1, -2, 5],
                        [0, 1, 3],
                    ],
                ],
                'accepted_answers' => [
                    'B2 ← 1/4 B2',
                    'B2 <- 1/4 B2',
                    'B2 ← B2/4',
                    'B2 <- B2/4',
                ],
                'explanation' => 'Kalikan seluruh Baris-2 dengan 1/4 sehingga elemen 4 berubah menjadi 1 utama.',
                'points' => 17,
                'order_number' => 3,
                'is_required' => true,
            ],
            [
                'question_text' => 'Perhatikan matriks teraugmentasi berikut. Gunakan Operasi Baris Elementer agar elemen pertama bernilai tak nol pada Baris-2 Kolom-2 berubah menjadi 1 utama.',
                'question_type' => 'obe_matrix_operation',
                'question_data' => [
                    'initial_matrix' => [
                        [1, 3, -2, 4],
                        [0, '-3/5', 6, -9],
                        [0, 2, 5, 7],
                    ],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'canvas_required' => true,
                ],
                'answer_key' => [
                    'operation' => 'B2 ← -5/3 B2',
                    'matrix' => [
                        [1, 3, -2, 4],
                        [0, 1, -10, 15],
                        [0, 2, 5, 7],
                    ],
                ],
                'accepted_answers' => [
                    'B2 ← -5/3 B2',
                    'B2 <- -5/3 B2',
                    'B2 ← (-5/3)B2',
                    'B2 <- (-5/3)B2',
                ],
                'explanation' => 'Kalikan seluruh Baris-2 dengan -5/3 agar elemen -3/5 berubah menjadi 1 utama.',
                'points' => 17,
                'order_number' => 4,
                'is_required' => true,
            ],
            [
                'question_text' => 'Perhatikan matriks teraugmentasi berikut. Gunakan Operasi Baris Elementer untuk mengenolkan Baris-2 Kolom-1.',
                'question_type' => 'obe_matrix_operation',
                'question_data' => [
                    'initial_matrix' => [
                        [1, 4, 2],
                        [3, 10, 4],
                    ],
                    'rows' => 2,
                    'columns' => 3,
                    'separator_before_column' => 3,
                    'canvas_required' => true,
                ],
                'answer_key' => [
                    'operation' => 'B2 ← -3B1 + B2',
                    'matrix' => [
                        [1, 4, 2],
                        [0, -2, -2],
                    ],
                ],
                'accepted_answers' => [
                    'B2 ← -3B1 + B2',
                    'B2 <- -3B1 + B2',
                    'B2 ← B2 - 3B1',
                    'B2 <- B2 - 3B1',
                ],
                'explanation' => 'Kalikan Baris-1 dengan -3, lalu jumlahkan hasilnya ke Baris-2 sehingga elemen pertama Baris-2 menjadi nol.',
                'points' => 16,
                'order_number' => 5,
                'is_required' => true,
            ],
            [
                'question_text' => 'Perhatikan matriks teraugmentasi berikut. Gunakan Operasi Baris Elementer untuk mengenolkan elemen pada Baris-3 Kolom-2.',
                'question_type' => 'obe_matrix_operation',
                'question_data' => [
                    'initial_matrix' => [
                        [1, 2, -1, 3],
                        [0, 1, 4, 5],
                        [0, -2, 3, 6],
                    ],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'canvas_required' => true,
                ],
                'answer_key' => [
                    'operation' => 'B3 ← 2B2 + B3',
                    'matrix' => [
                        [1, 2, -1, 3],
                        [0, 1, 4, 5],
                        [0, 0, 11, 16],
                    ],
                ],
                'accepted_answers' => [
                    'B3 ← 2B2 + B3',
                    'B3 <- 2B2 + B3',
                    'B3 ← B3 + 2B2',
                    'B3 <- B3 + 2B2',
                ],
                'explanation' => 'Kalikan Baris-2 dengan 2, lalu jumlahkan hasilnya ke Baris-3 sehingga elemen Baris-3 Kolom-2 menjadi nol.',
                'points' => 16,
                'order_number' => 6,
                'is_required' => true,
            ],
        ];

        ClassGroup::query()
            ->orderBy('id')
            ->each(function (ClassGroup $classGroup) use ($module, $instruction, $questions) {
                $quiz = Quiz::query()
                    ->where('class_group_id', $classGroup->id)
                    ->where('course_module_id', $module->id)
                    ->where('type', 'kuis_bab')
                    ->first();

                if (! $quiz) {
                    $quiz = Quiz::create([
                        'class_group_id' => $classGroup->id,
                        'course_module_id' => $module->id,
                        'title' => 'Kuis Bab 2 - Operasi Baris Elementer',
                        'slug' => 'kuis-bab-2',
                        'type' => 'kuis_bab',
                        'description' => 'Kuis untuk mengukur pemahaman mahasiswa terhadap Operasi Baris Elementer.',
                        'instruction' => $instruction,
                        'duration_minutes' => 20,
                        'max_attempts' => 3,
                        'is_active' => true,
                    ]);
                }

                /*
                | Jangan menimpa kuis yang telah memiliki percobaan atau soal.
                | Ini menjaga hasil mahasiswa dan draf soal manual tetap aman.
                */
                if ($quiz->attempts()->exists() || $quiz->questions()->exists()) {
                    return;
                }

                $quiz->update([
                    'title' => 'Kuis Bab 2 - Operasi Baris Elementer',
                    'type' => 'kuis_bab',
                    'description' => 'Kuis untuk mengukur pemahaman mahasiswa terhadap Operasi Baris Elementer.',
                    'instruction' => $instruction,
                    'duration_minutes' => 20,
                    'max_attempts' => 3,
                    'is_active' => true,
                ]);

                $quiz->questions()->createMany($questions);
            });
    }
}