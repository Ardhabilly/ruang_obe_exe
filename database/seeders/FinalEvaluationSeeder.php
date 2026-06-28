<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use App\Models\Quiz;
use Illuminate\Database\Seeder;

class FinalEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $instruction = <<<'TEXT'
Status: 20 Soal Tersedia
Estimasi Waktu Pengerjaan: 60 Menit

Instruksi:
Evaluasi akhir ini mencakup materi Sistem Persamaan Linear, Operasi Baris Elementer, Eliminasi Gauss, dan Eliminasi Gauss-Jordan. Kerjakan setiap soal secara teliti dan sistematis.

Petunjuk Pengerjaan:
1. Baca bentuk persamaan, matriks, serta instruksi setiap soal sebelum menjawab.
2. Untuk soal yang memerlukan proses perhitungan, tuliskan langkah penyelesaian secara sistematis pada area yang tersedia.
3. Masukkan seluruh elemen matriks dan hasil akhir variabel dengan lengkap.
4. Gunakan tanda "/" atau kata "per" untuk menuliskan pecahan, misalnya 13/21 atau 13 per 21.
5. Pastikan seluruh soal sudah terjawab sebelum menekan tombol Kumpulkan.

Peringatan:
Jangan memuat ulang halaman atau menutup halaman saat evaluasi berlangsung agar progres jawaban tidak hilang.
TEXT;

        $questions = [
            [
                'question_text' => 'Identifikasilah semua bentuk persamaan berikut yang memenuhi karakteristik persamaan linear. Pilih semua jawaban yang benar.',
                'question_type' => 'checkbox',
                'question_data' => [
                    'options' => [
                        'A' => '3x - 2y + z = 7',
                        'B' => 'x² + y = 5',
                        'C' => '4a - b + 6c = 0',
                        'D' => 'mn + p = 9s',
                    ],
                ],
                'answer_key' => ['selected' => ['A', 'C']],
                'accepted_answers' => [],
                'explanation' => 'Persamaan linear tidak memuat pangkat variabel lebih dari satu maupun perkalian antarvariabel.',
                'points' => 5,
                'order_number' => 1,
                'is_required' => true,
            ],
            [
                'question_text' => 'Perhatikan sistem persamaan berikut. Klasifikasikan sistem tersebut berdasarkan nilai konstanta pada ruas kanannya.',
                'question_type' => 'short_text',
                'question_data' => [
                    'equations' => [
                        'x + 2y - z = 0',
                        '3x - y + 4z = 0',
                        '-2x + 5y + z = 0',
                    ],
                ],
                'answer_key' => ['answer' => 'Sistem homogen'],
                'accepted_answers' => ['homogen', 'sistem homogen', 'spl homogen', 'sistem persamaan linear homogen'],
                'explanation' => 'Sistem homogen memiliki seluruh konstanta pada ruas kanan bernilai nol.',
                'points' => 5,
                'order_number' => 2,
                'is_required' => true,
            ],
            [
                'question_text' => 'Representasikan sistem persamaan linear berikut ke dalam bentuk matriks teraugmentasi.',
                'question_type' => 'augmented_matrix',
                'question_data' => [
                    'equations' => [
                        'x + 2y - z = 4',
                        '2x - y + 3z = 5',
                        '-x + 2z = -1',
                    ],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                ],
                'answer_key' => [
                    'matrix' => [[1, 2, -1, 4], [2, -1, 3, 5], [-1, 0, 2, -1]],
                ],
                'accepted_answers' => [],
                'explanation' => 'Koefisien x, y, z ditempatkan sebelum garis pemisah dan konstanta berada sesudah garis pemisah.',
                'points' => 5,
                'order_number' => 3,
                'is_required' => true,
            ],
            [
                'question_text' => 'Interpretasikan setiap baris pada matriks teraugmentasi berikut ke dalam bentuk sistem persamaan linear.',
                'question_type' => 'multi_short_text',
                'question_data' => [
                    'matrix' => [[1, -2, 0, 3], [0, 4, -6, -6], [2, 0, -1, 7]],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'fields' => ['baris_1', 'baris_2', 'baris_3'],
                    'labels' => [
                        'baris_1' => 'Persamaan Baris 1',
                        'baris_2' => 'Persamaan Baris 2',
                        'baris_3' => 'Persamaan Baris 3',
                    ],
                ],
                'answer_key' => [
                    'baris_1' => 'x - 2y = 3',
                    'baris_2' => '4y - 6z = -6',
                    'baris_3' => '2x - z = 7',
                ],
                'accepted_answers' => [
                    'baris_1' => ['x-2y=3', 'x - 2y = 3'],
                    'baris_2' => ['4y-6z=-6', '4y - 6z = -6'],
                    'baris_3' => ['2x-z=7', '2x - z = 7'],
                ],
                'explanation' => 'Setiap baris matriks teraugmentasi dapat diterjemahkan menjadi satu persamaan linear.',
                'points' => 5,
                'order_number' => 4,
                'is_required' => true,
            ],
            [
                'question_text' => 'Terapkan metode eliminasi dan substitusi untuk menentukan nilai x, y, dan z dari sistem persamaan linear berikut.',
                'question_type' => 'canvas_final_answer',
                'question_data' => [
                    'equations' => [
                        'x + y + z = 6',
                        '2x + y - z = 1',
                        'x + 2y + z = 8',
                    ],
                    'final_fields' => ['x', 'y', 'z'],
                ],
                'answer_key' => [],
                'accepted_answers' => ['x' => ['1'], 'y' => ['2'], 'z' => ['3']],
                'explanation' => 'Dengan eliminasi kemudian substitusi diperoleh x = 1, y = 2, dan z = 3.',
                'points' => 5,
                'order_number' => 5,
                'is_required' => true,
            ],
            [
                'question_text' => 'Terapkan Operasi Baris Elementer untuk mengubah elemen pertama pada baris berikut menjadi 1 utama.',
                'question_type' => 'obe_matrix_operation',
                'question_data' => [
                    'initial_matrix' => [[2, -4, 6, 8]],
                    'rows' => 1,
                    'columns' => 4,
                    'separator_before_column' => 4,
                ],
                'answer_key' => [
                    'operation' => 'B1 ← 1/2 B1',
                    'matrix' => [[1, -2, 3, 4]],
                ],
                'accepted_answers' => [
                    'B1 ← 1/2 B1',
                    'B1 <- 1/2 B1',
                    'B1 ← B1/2',
                    'B1 <- B1/2',
                ],
                'explanation' => 'Kalikan Baris-1 dengan 1/2 agar elemen pertama berubah dari 2 menjadi 1.',
                'points' => 5,
                'order_number' => 6,
                'is_required' => true,
            ],
            [
                'question_text' => 'Terapkan operasi pertukaran dua baris pada matriks berikut agar angka 1 utama berada pada Baris-1.',
                'question_type' => 'obe_matrix_operation',
                'question_data' => [
                    'initial_matrix' => [[0, 2, -1, 4], [1, 3, 2, 5], [0, 0, 1, 2]],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                ],
                'answer_key' => [
                    'operation' => 'B1 ↔ B2',
                    'matrix' => [[1, 3, 2, 5], [0, 2, -1, 4], [0, 0, 1, 2]],
                ],
                'accepted_answers' => ['B1 ↔ B2', 'B2 ↔ B1', 'B1 <-> B2', 'B2 <-> B1'],
                'explanation' => 'Tukar Baris-1 dan Baris-2 agar angka 1 utama berada pada Baris-1.',
                'points' => 5,
                'order_number' => 7,
                'is_required' => true,
            ],
            [
                'question_text' => 'Terapkan Operasi Baris Elementer yang tepat untuk mengubah elemen −1 pada Baris-2 Kolom-2 menjadi 1 utama.',
                'question_type' => 'obe_matrix_operation',
                'question_data' => [
                    'initial_matrix' => [[1, 2, -1, 4], [3, -1, 2, 5], [0, 1, 1, 3]],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                ],
                'answer_key' => [
                    'operation' => 'B2 ← -B2',
                    'matrix' => [[1, 2, -1, 4], [-3, 1, -2, -5], [0, 1, 1, 3]],
                ],
                'accepted_answers' => ['B2 ← -B2', 'B2 <- -B2', 'B2 ← -1B2', 'B2 <- -1B2'],
                'explanation' => 'Kalikan seluruh Baris-2 dengan −1 agar elemen −1 pada Kolom-2 berubah menjadi 1.',
                'points' => 5,
                'order_number' => 8,
                'is_required' => true,
            ],
            [
                'question_text' => 'Terapkan Operasi Baris Elementer untuk mengenolkan elemen pada Baris-2 Kolom-1 dari matriks berikut.',
                'question_type' => 'obe_matrix_operation',
                'question_data' => [
                    'initial_matrix' => [[1, 2, -1, 4], [3, 1, 2, 5], [0, 1, 1, 3]],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                ],
                'answer_key' => [
                    'operation' => 'B2 ← -3B1 + B2',
                    'matrix' => [[1, 2, -1, 4], [0, -5, 5, -7], [0, 1, 1, 3]],
                ],
                'accepted_answers' => [
                    'B2 ← -3B1 + B2',
                    'B2 <- -3B1 + B2',
                    'B2 ← B2 - 3B1',
                    'B2 <- B2 - 3B1',
                ],
                'explanation' => 'Kalikan Baris-1 dengan −3 lalu jumlahkan ke Baris-2.',
                'points' => 5,
                'order_number' => 9,
                'is_required' => true,
            ],
            [
                'question_text' => 'Identifikasilah semua operasi berikut yang termasuk Operasi Baris Elementer yang sah. Pilih semua jawaban yang benar.',
                'question_type' => 'checkbox',
                'question_data' => [
                    'options' => [
                        'A' => 'B1 ↔ B2',
                        'B' => 'B2 ← 0B2',
                        'C' => 'B3 ← −2B3',
                        'D' => 'B2 ← 4B1 + B2',
                    ],
                ],
                'answer_key' => ['selected' => ['A', 'C', 'D']],
                'accepted_answers' => [],
                'explanation' => 'OBE yang sah mencakup pertukaran baris, perkalian dengan skalar tak nol, dan penjumlahan kelipatan suatu baris ke baris lain.',
                'points' => 5,
                'order_number' => 10,
                'is_required' => true,
            ],
            [
                'question_text' => 'Analisislah setiap matriks berikut berdasarkan syarat bentuk eselon baris. Tentukan semua matriks yang sudah memenuhi seluruh syarat bentuk eselon baris.',
                'question_type' => 'checkbox',
                'question_data' => [
                    'options' => [
                        'A' => 'A = [1  2  −1  4; 0  1  3  2; 0  0  1  −1]',
                        'B' => 'B = [1  −2  5; 0  1  3; 0  0  0]',
                        'C' => 'C = [1  0  2; 0  0  0; 0  1  4]',
                        'D' => 'D = [1  2  3; 0  2  4]',
                    ],
                ],
                'answer_key' => ['selected' => ['A', 'B']],
                'accepted_answers' => [],
                'explanation' => 'Pada bentuk eselon baris, setiap 1 utama berikutnya berada lebih kanan, elemen di bawahnya nol, dan baris nol berada di bagian bawah.',
                'points' => 5,
                'order_number' => 11,
                'is_required' => true,
            ],
            [
                'question_text' => 'Terapkan Operasi Baris Elementer menggunakan Baris-1 sebagai acuan untuk mengenolkan elemen pada Baris-3 Kolom-1.',
                'question_type' => 'obe_matrix_operation',
                'question_data' => [
                    'initial_matrix' => [[1, 2, -1, 4], [2, 5, 1, 11], [-1, 1, 2, 1]],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                ],
                'answer_key' => [
                    'operation' => 'B3 ← B1 + B3',
                    'matrix' => [[1, 2, -1, 4], [2, 5, 1, 11], [0, 3, 1, 5]],
                ],
                'accepted_answers' => [
                    'B3 ← B1 + B3',
                    'B3 <- B1 + B3',
                    'B3 ← B3 + B1',
                    'B3 <- B3 + B1',
                ],
                'explanation' => 'Tambahkan Baris-1 ke Baris-3 agar −1 + 1 menjadi nol pada Kolom-1.',
                'points' => 5,
                'order_number' => 12,
                'is_required' => true,
            ],
            [
                'question_text' => 'Selesaikan sistem persamaan berikut menggunakan metode Eliminasi Gauss hingga diperoleh bentuk eselon baris dan nilai setiap variabel.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['x + y = 5', '2x + y = 8'],
                    'rows' => 2,
                    'columns' => 3,
                    'separator_before_column' => 3,
                    'final_fields' => ['x', 'y'],
                    'final_labels' => ['x' => 'x', 'y' => 'y'],
                ],
                'answer_key' => ['initial_matrix' => [[1, 1, 5], [2, 1, 8]]],
                'accepted_answers' => ['x' => ['3'], 'y' => ['2']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [1 1 | 5; 0 −1 | −2], sehingga x = 3 dan y = 2.',
                'points' => 5,
                'order_number' => 13,
                'is_required' => true,
            ],
            [
                'question_text' => 'Terapkan metode Eliminasi Gauss untuk menyelesaikan sistem persamaan linear berikut.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['x + y + z = 6', '2x + 3y + 3z = 17', 'x + 2y + 3z = 14'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => ['initial_matrix' => [[1, 1, 1, 6], [2, 3, 3, 17], [1, 2, 3, 14]]],
                'accepted_answers' => ['x' => ['1'], 'y' => ['2'], 'z' => ['3']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [1 1 1 | 6; 0 1 1 | 5; 0 0 1 | 3].',
                'points' => 5,
                'order_number' => 14,
                'is_required' => true,
            ],
            [
                'question_text' => 'Harga air mineral (x), es teh (y), dan jus buah (z) dinyatakan dalam satuan puluh ribu rupiah. Terapkan metode Eliminasi Gauss untuk menentukan harga setiap minuman.',
                'question_type' => 'gauss_elimination',
                'question_data' => [
                    'equations' => ['x + y + z = 9', '2x + 3y + 3z = 25', 'x + 2y + 3z = 20'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'Air mineral (x)', 'y' => 'Es teh (y)', 'z' => 'Jus buah (z)'],
                ],
                'answer_key' => ['initial_matrix' => [[1, 1, 1, 9], [2, 3, 3, 25], [1, 2, 3, 20]]],
                'accepted_answers' => ['x' => ['2'], 'y' => ['3'], 'z' => ['4']],
                'explanation' => 'Salah satu bentuk eselon baris yang benar adalah [1 1 1 | 9; 0 1 1 | 7; 0 0 1 | 4].',
                'points' => 5,
                'order_number' => 15,
                'is_required' => true,
            ],
            [
                'question_text' => 'Analisislah setiap matriks berikut berdasarkan seluruh syarat bentuk eselon baris tereduksi. Tentukan semua matriks yang telah memenuhi bentuk eselon baris tereduksi.',
                'question_type' => 'checkbox',
                'question_data' => [
                    'options' => [
                        'A' => 'A = [1  0  −2  3; 0  1  4  −1; 0  0  0  0]',
                        'B' => 'B = [1  2  0  4; 0  1  3  1; 0  0  1  2]',
                        'C' => 'C = [1  0  2; 0  1  3; 0  0  0]',
                        'D' => 'D = [1  0  3; 0  0  0; 0  1  2]',
                    ],
                ],
                'answer_key' => ['selected' => ['A', 'C']],
                'accepted_answers' => [],
                'explanation' => 'Pada bentuk eselon baris tereduksi, setiap 1 utama menjadi satu-satunya elemen tak nol pada kolomnya dan seluruh baris nol berada di bagian bawah.',
                'points' => 5,
                'order_number' => 16,
                'is_required' => true,
            ],
            [
                'question_text' => 'Terapkan Operasi Baris Elementer menggunakan Baris-3 sebagai acuan untuk mengenolkan elemen pada Baris-2 Kolom-3.',
                'question_type' => 'obe_matrix_operation',
                'question_data' => [
                    'initial_matrix' => [[1, 2, -1, 4], [0, 1, 3, 5], [0, 0, 1, 2]],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                ],
                'answer_key' => [
                    'operation' => 'B2 ← -3B3 + B2',
                    'matrix' => [[1, 2, -1, 4], [0, 1, 0, -1], [0, 0, 1, 2]],
                ],
                'accepted_answers' => [
                    'B2 ← -3B3 + B2',
                    'B2 <- -3B3 + B2',
                    'B2 ← B2 - 3B3',
                    'B2 <- B2 - 3B3',
                ],
                'explanation' => 'Kalikan Baris-3 dengan −3 lalu jumlahkan ke Baris-2.',
                'points' => 5,
                'order_number' => 17,
                'is_required' => true,
            ],
            [
                'question_text' => 'Selesaikan sistem persamaan berikut menggunakan metode Eliminasi Gauss-Jordan hingga mencapai bentuk eselon baris tereduksi.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['x + 2y = 7', '2x + 3y = 11'],
                    'rows' => 2,
                    'columns' => 3,
                    'separator_before_column' => 3,
                    'final_fields' => ['x', 'y'],
                    'final_labels' => ['x' => 'x', 'y' => 'y'],
                ],
                'answer_key' => ['rref_matrix' => [[1, 0, 1], [0, 1, 3]]],
                'accepted_answers' => ['x' => ['1'], 'y' => ['3']],
                'explanation' => 'Bentuk eselon baris tereduksi yang benar adalah [1 0 | 1; 0 1 | 3].',
                'points' => 5,
                'order_number' => 18,
                'is_required' => true,
            ],
            [
                'question_text' => 'Selesaikan sistem persamaan berikut menggunakan metode Gauss-Jordan. Perhatikan bahwa elemen pada Baris-1 Kolom-1 bernilai nol sehingga Anda perlu menentukan operasi awal yang tepat.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['y + z = 5', 'x - y + z = 2', '2x + y + 6z = 22'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'x', 'y' => 'y', 'z' => 'z'],
                ],
                'answer_key' => ['rref_matrix' => [[1, 0, 0, 1], [0, 1, 0, 2], [0, 0, 1, 3]]],
                'accepted_answers' => ['x' => ['1'], 'y' => ['2'], 'z' => ['3']],
                'explanation' => 'Bentuk eselon baris tereduksi yang benar adalah [1 0 0 | 1; 0 1 0 | 2; 0 0 1 | 3].',
                'points' => 5,
                'order_number' => 19,
                'is_required' => true,
            ],
            [
                'question_text' => 'Harga Jeruk (x), Apel (y), dan Mangga (z) dinyatakan dalam satuan puluh ribu rupiah. Terapkan metode Eliminasi Gauss-Jordan untuk menentukan harga setiap jenis buah.',
                'question_type' => 'gauss_jordan',
                'question_data' => [
                    'equations' => ['x + y + z = 12', '2x + 3y + 2z = 28', 'x + 2y + 2z = 21'],
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                    'final_fields' => ['x', 'y', 'z'],
                    'final_labels' => ['x' => 'Jeruk (x)', 'y' => 'Apel (y)', 'z' => 'Mangga (z)'],
                ],
                'answer_key' => ['rref_matrix' => [[1, 0, 0, 3], [0, 1, 0, 4], [0, 0, 1, 5]]],
                'accepted_answers' => ['x' => ['3'], 'y' => ['4'], 'z' => ['5']],
                'explanation' => 'Bentuk eselon baris tereduksi memberikan harga Jeruk = 3, Apel = 4, dan Mangga = 5.',
                'points' => 5,
                'order_number' => 20,
                'is_required' => true,
            ],
        ];

        ClassGroup::query()
            ->orderBy('id')
            ->each(function (ClassGroup $classGroup) use ($instruction, $questions) {
                $quiz = Quiz::query()
                    ->where('class_group_id', $classGroup->id)
                    ->where('type', 'evaluasi_akhir')
                    ->first();

                if (! $quiz) {
                    $quiz = Quiz::create([
                        'class_group_id' => $classGroup->id,
                        'course_module_id' => null,
                        'title' => 'Evaluasi Akhir - Sistem Persamaan Linear dan OBE',
                        'slug' => 'evaluasi-akhir',
                        'type' => 'evaluasi_akhir',
                        'description' => 'Evaluasi akhir untuk mengukur penguasaan materi Bab 1 sampai Bab 4.',
                        'instruction' => $instruction,
                        'duration_minutes' => 60,
                        'max_attempts' => 3,
                        'is_active' => true,
                    ]);
                }

                /*
                | Jangan menimpa evaluasi yang telah memiliki percobaan atau soal.
                | Ini menjaga hasil mahasiswa dan draf soal manual tetap aman.
                */
                if ($quiz->attempts()->exists() || $quiz->questions()->exists()) {
                    return;
                }

                $quiz->update([
                    'course_module_id' => null,
                    'title' => 'Evaluasi Akhir - Sistem Persamaan Linear dan OBE',
                    'type' => 'evaluasi_akhir',
                    'description' => 'Evaluasi akhir untuk mengukur penguasaan materi Bab 1 sampai Bab 4.',
                    'instruction' => $instruction,
                    'duration_minutes' => 60,
                    'max_attempts' => 3,
                    'is_active' => true,
                ]);

                $quiz->questions()->createMany($questions);
            });
    }
}