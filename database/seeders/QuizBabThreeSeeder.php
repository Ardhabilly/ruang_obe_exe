<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use App\Models\CourseModule;
use App\Models\Quiz;
use Illuminate\Database\Seeder;

class QuizBabThreeSeeder extends Seeder
{
    public function run(): void
    {
        $module = CourseModule::where('slug', 'bab-3-metode-eliminasi-gauss')->first();

        if (! $module) {
            return;
        }

        $instruction = <<<'TEXT'
Status: 10 Soal Tersedia
Estimasi Waktu Pengerjaan: 30 Menit

Instruksi:
Jawablah pertanyaan di bawah ini dengan teliti dan sistematis. Anda diuji untuk mengaplikasikan Operasi Baris Elementer dalam mengubah matriks menjadi Bentuk Eselon Baris dan menemukan himpunan penyelesaian menggunakan Metode Eliminasi Gauss.

Petunjuk Pengerjaan Kuis Modul:
1. Bacalah setiap Sistem Persamaan Linear dan instruksi sebelum memulai perhitungan.
2. Tuliskan proses Operasi Baris Elementer serta substitusi balik pada area Langkah Pengerjaan.
3. Masukkan matriks yang telah mencapai Bentuk Eselon Baris dan nilai akhir setiap variabel pada kotak yang tersedia.
4. Anda dapat berpindah soal melalui panel navigasi. Pastikan seluruh jawaban telah diperiksa sebelum kuis dikumpulkan.

Peringatan:
Jangan memuat ulang halaman atau menutup halaman saat kuis berlangsung agar progres jawaban tidak hilang.
TEXT;

        $questions = [
            [
                'question_text' => 'Selesaikan Sistem Persamaan Linear berikut menggunakan Metode Eliminasi Gauss.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['x + 2y = 7', '3x + 5y = 17'],
                    'rows' => 2,
                    'columns' => 3,
                    'separator_before_column' => 3,
                    'final_fields' => ['x', 'y'],
                    'final_labels' => ['x' => 'x', 'y' => 'y'],
                ],
                'answer_key' => [
                    'initial_matrix' => [[1, 2, 7], [3, 5, 17]],
                ],
                'accepted_answers' => ['x' => ['-1'], 'y' => ['4']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [1 2 | 7; 0 -1 | -4], sehingga x = -1 dan y = 4.',
                'points' => 10,
                'order_number' => 1,
                'is_required' => true,
            ],
            [
                'question_text' => 'Selesaikan Sistem Persamaan Linear berikut menggunakan Metode Eliminasi Gauss.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['2x - y = 4', 'x + y = 5'],
                    'rows' => 2,
                    'columns' => 3,
                    'separator_before_column' => 3,
                    'final_fields' => ['x', 'y'],
                    'final_labels' => ['x' => 'x', 'y' => 'y'],
                ],
                'answer_key' => [
                    'initial_matrix' => [[2, -1, 4], [1, 1, 5]],
                ],
                'accepted_answers' => ['x' => ['3'], 'y' => ['2']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [2 -1 | 4; 0 3 | 6], sehingga x = 3 dan y = 2.',
                'points' => 10,
                'order_number' => 2,
                'is_required' => true,
            ],
            [
                'question_text' => 'Selesaikan Sistem Persamaan Linear berikut menggunakan Metode Eliminasi Gauss. Pastikan matriks mencapai Bentuk Eselon Baris sebelum melakukan substitusi balik.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['x + 2y - z = 2', '2x + 5y - 3z = 3', '-x + y + 5z = 9'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => [
                    'initial_matrix' => [[1, 2, -1, 2], [2, 5, -3, 3], [-1, 1, 5, 9]],
                ],
                'accepted_answers' => ['x' => ['2'], 'y' => ['1'], 'z' => ['2']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [1 2 -1 | 2; 0 1 -1 | -1; 0 0 7 | 14], sehingga x = 2, y = 1, dan z = 2.',
                'points' => 10,
                'order_number' => 3,
                'is_required' => true,
            ],
            [
                'question_text' => 'Carilah nilai variabel x, y, dan z pada Sistem Persamaan Linear berikut menggunakan algoritma Eliminasi Gauss.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['x - y + 2z = 8', '3x - y + 5z = 19', '2x + y + z = 4'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => [
                    'initial_matrix' => [[1, -1, 2, 8], [3, -1, 5, 19], [2, 1, 1, 4]],
                ],
                'accepted_answers' => ['x' => ['1'], 'y' => ['-1'], 'z' => ['3']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [1 -1 2 | 8; 0 2 -1 | -5; 0 0 -3 | -9], sehingga x = 1, y = -1, dan z = 3.',
                'points' => 10,
                'order_number' => 4,
                'is_required' => true,
            ],
            [
                'question_text' => 'Selesaikan Sistem Persamaan Linear berikut dengan mereduksinya menjadi matriks eselon baris.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['y - z = -1', 'x + y + z = 6', '2x - y + z = 3'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => [
                    'initial_matrix' => [[0, 1, -1, -1], [1, 1, 1, 6], [2, -1, 1, 3]],
                ],
                'accepted_answers' => ['x' => ['1'], 'y' => ['2'], 'z' => ['3']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [1 1 1 | 6; 0 1 -1 | -1; 0 0 -4 | -12], sehingga x = 1, y = 2, dan z = 3.',
                'points' => 10,
                'order_number' => 5,
                'is_required' => true,
            ],
            [
                'question_text' => 'Gunakan Metode Eliminasi Gauss untuk menemukan himpunan penyelesaian dari Sistem Persamaan Linear berikut.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['x + 3y + z = 5', '2x + 7y + z = 7', '-x - 2y + 2z = 8'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => [
                    'initial_matrix' => [[1, 3, 1, 5], [2, 7, 1, 7], [-1, -2, 2, 8]],
                ],
                'accepted_answers' => ['x' => ['-2'], 'y' => ['1'], 'z' => ['4']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [1 3 1 | 5; 0 1 -1 | -3; 0 0 4 | 16], sehingga x = -2, y = 1, dan z = 4.',
                'points' => 10,
                'order_number' => 6,
                'is_required' => true,
            ],
            [
                'question_text' => 'Tentukan solusi eksak dari Sistem Persamaan Linear berikut melalui pendekatan Eliminasi Gauss.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['x - 2y - 3z = 11', '-x + 3y + 2z = -12', '2x - y - 4z = 14'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => [
                    'initial_matrix' => [[1, -2, -3, 11], [-1, 3, 2, -12], [2, -1, -4, 14]],
                ],
                'accepted_answers' => ['x' => ['4'], 'y' => ['-2'], 'z' => ['-1']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [1 -2 -3 | 11; 0 1 -1 | -1; 0 0 5 | -5], sehingga x = 4, y = -2, dan z = -1.',
                'points' => 10,
                'order_number' => 7,
                'is_required' => true,
            ],
            [
                'question_text' => 'Terapkan langkah-langkah Eliminasi Gauss untuk memecahkan Sistem Persamaan Linear berikut.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['x + y - 2z = 9', '3x + 4y - 5z = 25', '-2x + 2y + z = -12'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => [
                    'initial_matrix' => [[1, 1, -2, 9], [3, 4, -5, 25], [-2, 2, 1, -12]],
                ],
                'accepted_answers' => ['x' => ['5'], 'y' => ['0'], 'z' => ['-2']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [1 1 -2 | 9; 0 1 1 | -2; 0 0 -7 | 14], sehingga x = 5, y = 0, dan z = -2.',
                'points' => 10,
                'order_number' => 8,
                'is_required' => true,
            ],
            [
                'question_text' => 'Sebuah perusahaan IT memesan 3 jenis kabel: HDMI (x), VGA (y), dan USB (z). Tentukan jumlah masing-masing pesanan kabel menggunakan Metode Eliminasi Gauss.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['x + y + z = 10', '2x + y + 3z = 22', 'x + 2y + z = 13'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'HDMI (x)', 'y' => 'VGA (y)', 'z' => 'USB (z)'],
                ],
                'answer_key' => [
                    'initial_matrix' => [[1, 1, 1, 10], [2, 1, 3, 22], [1, 2, 1, 13]],
                ],
                'accepted_answers' => ['x' => ['2'], 'y' => ['3'], 'z' => ['5']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [1 1 1 | 10; 0 -1 1 | 2; 0 0 -1 | -5], sehingga jumlah HDMI = 2, VGA = 3, dan USB = 5.',
                'points' => 10,
                'order_number' => 9,
                'is_required' => true,
            ],
            [
                'question_text' => 'Sebuah Data Center mengalokasikan sumber daya CPU (x), RAM (y), dan Storage (z) untuk klien. Gunakan Eliminasi Gauss untuk menemukan konfigurasi pasti sumber daya tersebut.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['x + y + z = 12', '3x + 2y + 2z = 28', '2x + 4y + 3z = 38'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'CPU (x)', 'y' => 'RAM (y)', 'z' => 'Storage (z)'],
                ],
                'answer_key' => [
                    'initial_matrix' => [[1, 1, 1, 12], [3, 2, 2, 28], [2, 4, 3, 38]],
                ],
                'accepted_answers' => ['x' => ['4'], 'y' => ['6'], 'z' => ['2']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [1 1 1 | 12; 0 -1 -1 | -8; 0 0 1 | 2], sehingga CPU = 4, RAM = 6, dan Storage = 2.',
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
                        'title' => 'Kuis Bab 3 - Eliminasi Gauss',
                        'slug' => 'kuis-bab-3',
                        'type' => 'kuis_bab',
                        'description' => 'Kuis untuk mengukur kemampuan mahasiswa menerapkan Eliminasi Gauss.',
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
                    'title' => 'Kuis Bab 3 - Eliminasi Gauss',
                    'type' => 'kuis_bab',
                    'description' => 'Kuis untuk mengukur kemampuan mahasiswa menerapkan Eliminasi Gauss.',
                    'instruction' => $instruction,
                    'duration_minutes' => 30,
                    'max_attempts' => 3,
                    'is_active' => true,
                ]);

                $quiz->questions()->createMany($questions);
            });
    }
}