<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use App\Models\CourseModule;
use App\Models\Quiz;
use Illuminate\Database\Seeder;

class QuizBabFourSeeder extends Seeder
{
    public function run(): void
    {
        $module = CourseModule::where('slug', 'bab-4-metode-eliminasi-gauss-jordan')->first();

        if (! $module) {
            return;
        }

        $instruction = <<<'TEXT'
Status: 10 Soal Tersedia
Estimasi Waktu Pengerjaan: 30 Menit

Instruksi:
Jawablah pertanyaan di bawah ini dengan teliti dan sistematis. Anda diuji untuk mengaplikasikan Operasi Baris Elementer dalam mengubah matriks menjadi Bentuk Eselon Baris Tereduksi dan menemukan himpunan penyelesaian menggunakan Metode Eliminasi Gauss-Jordan.

Petunjuk Pengerjaan Kuis Modul:
1. Bacalah setiap Sistem Persamaan Linear dan instruksi sebelum memulai perhitungan.
2. Tuliskan proses Operasi Baris Elementer pada area Langkah Pengerjaan hingga matriks mencapai Bentuk Eselon Baris Tereduksi.
3. Masukkan hasil matriks tereduksi dan nilai akhir setiap variabel pada kotak yang tersedia.
4. Gunakan tanda "/" atau kata "per" untuk menuliskan pecahan, misalnya 13/21 atau 13 per 21.
5. Pastikan seluruh soal sudah diperiksa sebelum kuis dikumpulkan.

Peringatan:
Jangan memuat ulang halaman atau menutup halaman saat kuis berlangsung agar progres jawaban tidak hilang.
TEXT;

        $questions = [
            [
                'question_text' => 'Selesaikan Sistem Persamaan Linear berikut hingga mencapai Bentuk Eselon Baris Tereduksi.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['x + y = 5', '2x - y = 4'],
                    'rows' => 2,
                    'columns' => 3,
                    'separator_before_column' => 3,
                    'final_fields' => ['x', 'y'],
                    'final_labels' => ['x' => 'x', 'y' => 'y'],
                ],
                'answer_key' => [
                    'rref_matrix' => [[1, 0, 3], [0, 1, 2]],
                ],
                'accepted_answers' => ['x' => ['3'], 'y' => ['2']],
                'explanation' => 'Bentuk eselon baris tereduksi yang benar adalah [1 0 | 3; 0 1 | 2], sehingga x = 3 dan y = 2.',
                'points' => 10,
                'order_number' => 1,
                'is_required' => true,
            ],
            [
                'question_text' => 'Aplikasikan algoritma Eliminasi Gauss-Jordan pada Sistem Persamaan Linear di bawah ini.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['2x + 3y = 12', 'x - 2y = -1'],
                    'rows' => 2,
                    'columns' => 3,
                    'separator_before_column' => 3,
                    'final_fields' => ['x', 'y'],
                    'final_labels' => ['x' => 'x', 'y' => 'y'],
                ],
                'answer_key' => [
                    'rref_matrix' => [[1, 0, 3], [0, 1, 2]],
                ],
                'accepted_answers' => ['x' => ['3'], 'y' => ['2']],
                'explanation' => 'Bentuk eselon baris tereduksi yang benar adalah [1 0 | 3; 0 1 | 2], sehingga x = 3 dan y = 2.',
                'points' => 10,
                'order_number' => 2,
                'is_required' => true,
            ],
            [
                'question_text' => 'Terapkan Eliminasi Gauss-Jordan untuk menemukan solusi dari Sistem Persamaan Linear 3 variabel berikut.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['x + y + z = 6', '2x - y + z = 3', 'x + 2y - z = 2'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => [
                    'rref_matrix' => [[1, 0, 0, 1], [0, 1, 0, 2], [0, 0, 1, 3]],
                ],
                'accepted_answers' => ['x' => ['1'], 'y' => ['2'], 'z' => ['3']],
                'explanation' => 'Bentuk eselon baris tereduksi yang benar adalah [1 0 0 | 1; 0 1 0 | 2; 0 0 1 | 3].',
                'points' => 10,
                'order_number' => 3,
                'is_required' => true,
            ],
            [
                'question_text' => 'Temukan matriks tereduksi dan himpunan penyelesaian dari Sistem Persamaan Linear berikut.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['x - 2y + 3z = 9', '-x + 3y = -4', '2x - 5y + 5z = 17'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => [
                    'rref_matrix' => [[1, 0, 0, 1], [0, 1, 0, -1], [0, 0, 1, 2]],
                ],
                'accepted_answers' => ['x' => ['1'], 'y' => ['-1'], 'z' => ['2']],
                'explanation' => 'Bentuk eselon baris tereduksi yang benar adalah [1 0 0 | 1; 0 1 0 | -1; 0 0 1 | 2].',
                'points' => 10,
                'order_number' => 4,
                'is_required' => true,
            ],
            [
                'question_text' => 'Selesaikan Sistem Persamaan Linear yang memuat nol di posisi diagonal awal ini menggunakan komputasi Gauss-Jordan.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['y - z = -1', 'x + y + z = 6', '2x - y + z = 3'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => [
                    'rref_matrix' => [[1, 0, 0, 1], [0, 1, 0, 2], [0, 0, 1, 3]],
                ],
                'accepted_answers' => ['x' => ['1'], 'y' => ['2'], 'z' => ['3']],
                'explanation' => 'Bentuk eselon baris tereduksi yang benar adalah [1 0 0 | 1; 0 1 0 | 2; 0 0 1 | 3].',
                'points' => 10,
                'order_number' => 5,
                'is_required' => true,
            ],
            [
                'question_text' => 'Lakukan Operasi Baris Elementer secara utuh hingga matriks berikut tereduksi sepenuhnya.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['x - 3y + z = 4', '2x - y - 3z = -2', '-x + 2y + z = 1'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => [
                    'rref_matrix' => [[1, 0, 0, 4], [0, 1, 0, 1], [0, 0, 1, 3]],
                ],
                'accepted_answers' => ['x' => ['4'], 'y' => ['1'], 'z' => ['3']],
                'explanation' => 'Bentuk eselon baris tereduksi yang benar adalah [1 0 0 | 4; 0 1 0 | 1; 0 0 1 | 3].',
                'points' => 10,
                'order_number' => 6,
                'is_required' => true,
            ],
            [
                'question_text' => 'Pecahkan Sistem Persamaan Linear ini. Pastikan Anda tidak berhenti pada pola eselon baris, tetapi melanjutkan proses iterasi hingga matriks tereduksi.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['x + y - 2z = 9', '3x + 4y - 5z = 25', '-2x + 2y + z = -12'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => [
                    'rref_matrix' => [[1, 0, 0, 5], [0, 1, 0, 0], [0, 0, 1, -2]],
                ],
                'accepted_answers' => ['x' => ['5'], 'y' => ['0'], 'z' => ['-2']],
                'explanation' => 'Bentuk eselon baris tereduksi yang benar adalah [1 0 0 | 5; 0 1 0 | 0; 0 0 1 | -2].',
                'points' => 10,
                'order_number' => 7,
                'is_required' => true,
            ],
            [
                'question_text' => 'Reduksi matriks dari Sistem Persamaan Linear berikut menggunakan Eliminasi Gauss-Jordan.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['2x + y + 3z = 11', '4x - 2y + 5z = 17', '-x + 3y + 2z = 7'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => [
                    'rref_matrix' => [[1, 0, 0, '13/21'], [0, 1, 0, '10/21'], [0, 0, 1, '65/21']],
                ],
                'accepted_answers' => ['x' => ['13/21', '13 per 21'], 'y' => ['10/21', '10 per 21'], 'z' => ['65/21', '65 per 21']],
                'explanation' => 'Bentuk eselon baris tereduksi memiliki hasil x = 13/21, y = 10/21, dan z = 65/21.',
                'points' => 10,
                'order_number' => 8,
                'is_required' => true,
            ],
            [
                'question_text' => 'Anda ditugaskan mendistribusikan poin stat sebuah karakter. Susun sistem linear berikut dan temukan alokasi poinnya menggunakan Gauss-Jordan.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['x + y + z = 20', '2x + y - z = 15', 'x + 3y - 2z = 15'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'Kekuatan', 'y' => 'Kelincahan', 'z' => 'Kecerdasan'],
                ],
                'answer_key' => [
                    'rref_matrix' => [[1, 0, 0, '65/9'], [0, 1, 0, '20/3'], [0, 0, 1, '55/9']],
                ],
                'accepted_answers' => ['x' => ['65/9', '65 per 9'], 'y' => ['20/3', '20 per 3'], 'z' => ['55/9', '55 per 9']],
                'explanation' => 'Bentuk eselon baris tereduksi memberikan Kekuatan = 65/9, Kelincahan = 20/3, dan Kecerdasan = 55/9.',
                'points' => 10,
                'order_number' => 9,
                'is_required' => true,
            ],
            [
                'question_text' => 'Seorang pedagang buah melayani tiga pelanggan yang membeli Jeruk (x), Apel (y), dan Mangga (z). Tentukan harga per kilogram masing-masing buah menggunakan Gauss-Jordan.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['x + y + z = 12', '2x + y + 3z = 25', '3x + 2y + z = 22'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'Jeruk (x)', 'y' => 'Apel (y)', 'z' => 'Mangga (z)'],
                ],
                'answer_key' => [
                    'rref_matrix' => [[1, 0, 0, 3], [0, 1, 0, 4], [0, 0, 1, 5]],
                ],
                'accepted_answers' => ['x' => ['3'], 'y' => ['4'], 'z' => ['5']],
                'explanation' => 'Bentuk eselon baris tereduksi memberikan harga Jeruk = 3, Apel = 4, dan Mangga = 5 dalam satuan puluh ribu rupiah.',
                'points' => 10,
                'order_number' => 10,
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
                        'title' => 'Kuis Bab 4 - Eliminasi Gauss-Jordan',
                        'slug' => 'kuis-bab-4',
                        'type' => 'kuis_bab',
                        'description' => 'Kuis untuk mengukur kemampuan mahasiswa menerapkan Eliminasi Gauss-Jordan.',
                        'instruction' => $instruction,
                        'duration_minutes' => 30,
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
                    'title' => 'Kuis Bab 4 - Eliminasi Gauss-Jordan',
                    'type' => 'kuis_bab',
                    'description' => 'Kuis untuk mengukur kemampuan mahasiswa menerapkan Eliminasi Gauss-Jordan.',
                    'instruction' => $instruction,
                    'duration_minutes' => 30,
                    'max_attempts' => 3,
                    'is_active' => true,
                ]);

                $quiz->questions()->createMany($questions);
            });
    }
}