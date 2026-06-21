<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use App\Models\CourseModule;
use App\Models\Quiz;
use Illuminate\Database\Seeder;

class QuizBabOneSeeder extends Seeder
{
    public function run(): void
    {
        $module = CourseModule::where('slug', 'bab-1-sistem-persamaan-linear')->first();

        if (! $module) {
            return;
        }

        $classGroups = ClassGroup::all();

        if ($classGroups->isEmpty()) {
            return;
        }

        $instruction = <<<'TEXT'
Status: 10 Soal Tersedia
Estimasi Waktu Pengerjaan: 20 Menit

Instruksi:
Jawablah pertanyaan di bawah ini dengan cepat dan tepat. Anda diuji untuk memahami konsep teoretis dan logika komputasi di balik Sistem Persamaan Linear.

Petunjuk Pengerjaan Kuis Modul:
1. Bacalah setiap studi kasus, bentuk persamaan, susunan matriks, dan instruksi dengan sangat teliti sebelum menentukan jawaban.
2. Perhatikan dengan saksama format jawaban yang diminta.
3. Kuis ini memiliki beberapa tipe soal, seperti isian singkat, isian notasi matematika, input matriks, dan checkbox kompleks.
4. Anda dapat melompat ke soal lain menggunakan panel navigasi angka.
5. Pastikan tidak ada soal yang terlewat sebelum menekan tombol Kumpulkan.

Peringatan:
Dilarang me-refresh halaman atau menutup halaman saat kuis sedang berlangsung karena dapat menyebabkan progres jawaban hilang.
TEXT;

        $questions = [
            [
                'question_text' => 'Sebagai analis data, Anda harus memfilter persamaan mana saja yang valid sebagai persamaan linear. Di antara persamaan berikut, pilihlah SEMUA bentuk yang benar dan diakui sebagai persamaan linear murni!',
                'question_type' => 'checkbox',
                'question_data' => [
                    'options' => [
                        'A' => 'x₁ − 2x₂ − 3x₃ + x₄ = 7',
                        'B' => 'x + 3√y = 5',
                        'C' => '3x + 2y + z = 14',
                        'D' => '2x + 3y − z + xz = 4',
                    ],
                ],
                'answer_key' => [
                    'selected' => ['A', 'C'],
                ],
                'accepted_answers' => null,
                'explanation' => 'Persamaan A dan C linear karena semua variabel berpangkat satu dan tidak memuat akar, hasil kali antarvariabel, logaritma, trigonometri, atau eksponensial.',
                'points' => 10,
                'order_number' => 1,
            ],
            [
                'question_text' => 'Berdasarkan nilai konstantanya, sebuah sistem yang seluruh konstanta di ruas kanannya bernilai mutlak nol (b₁ = 0, b₂ = 0, ..., bₙ = 0) dijamin pasti memiliki minimal satu solusi trivial. Apakah istilah spesifik untuk klasifikasi Sistem Persamaan Linear jenis ini?',
                'question_type' => 'short_text',
                'question_data' => null,
                'answer_key' => [
                    'answer' => 'sistem homogen',
                ],
                'accepted_answers' => [
                    'sistem homogen',
                    'homogen',
                    'sistem persamaan linear homogen',
                    'spl homogen',
                    'sistem linear homogen',
                ],
                'explanation' => 'SPL yang seluruh konstanta ruas kanannya bernilai nol disebut sistem homogen.',
                'points' => 10,
                'order_number' => 2,
            ],
            [
                'question_text' => 'Sebuah program deteksi navigasi drone mendapati bahwa dua aturan rute terbang membentuk dua garis lurus yang saling sejajar di dalam grafik pemantau. Berdasarkan karakteristik solusi dari Sistem Persamaan Linear, pilih SEMUA pernyataan yang bernilai BENAR untuk kasus ini!',
                'question_type' => 'checkbox',
                'question_data' => [
                    'options' => [
                        'A' => 'Sistem tersebut memiliki solusi tunggal di koordinat nol.',
                        'B' => 'Sistem tersebut diklasifikasikan sebagai Sistem Inkonsisten.',
                        'C' => 'Terdapat jumlah solusi tak terhingga di sepanjang garis tersebut.',
                        'D' => 'Sistem tersebut mustahil dicari solusinya (memiliki 0 solusi).',
                    ],
                ],
                'answer_key' => [
                    'selected' => ['B', 'D'],
                ],
                'accepted_answers' => null,
                'explanation' => 'Dua garis sejajar tidak memiliki titik potong, sehingga sistem tidak memiliki solusi dan diklasifikasikan sebagai sistem inkonsisten.',
                'points' => 10,
                'order_number' => 3,
            ],
            [
                'question_text' => 'Untuk mengoptimalkan memori komputer saat menyelesaikan algoritma persamaan, Matriks Koefisien (A) dan Matriks Konstanta (b) digabungkan ke dalam satu struktur matriks tunggal yang dipisahkan oleh garis vertikal. Apakah istilah bahasa Inggris standar untuk matriks gabungan ini?',
                'question_type' => 'short_text',
                'question_data' => null,
                'answer_key' => [
                    'answer' => 'augmented matrix',
                ],
                'accepted_answers' => [
                    'augmented matrix',
                    'augmented',
                    'augmented matriks',
                    'matriks augmented',
                    'matriks teraugmentasi',
                ],
                'explanation' => 'Matriks gabungan antara matriks koefisien dan matriks konstanta disebut augmented matrix.',
                'points' => 10,
                'order_number' => 4,
            ],
            [
                'question_text' => 'Diberikan baris kedua dari sebuah Augmented Matrix yang memuat variabel x, y, dan z: [ 0   3   −1 | 8 ]. Tuliskan bentuk persamaan linear utuh dari array di atas!',
                'question_type' => 'math_notation',
                'question_data' => [
                    'row' => [0, 3, -1, 8],
                    'variables' => ['x', 'y', 'z'],
                ],
                'answer_key' => [
                    'answer' => '3y - z = 8',
                ],
                'accepted_answers' => [
                    '3y-z=8',
                    '3y - z = 8',
                    '0x+3y-z=8',
                    '0x + 3y - z = 8',
                    '3y + -z = 8',
                ],
                'explanation' => 'Baris [0 3 −1 | 8] merepresentasikan 0x + 3y − z = 8, atau dapat disederhanakan menjadi 3y − z = 8.',
                'points' => 10,
                'order_number' => 5,
            ],
            [
                'question_text' => 'Perhatikan visualisasi grafik Sistem Persamaan Linear yang ditampilkan pada gambar. Berdasarkan plot koordinat tersebut, apakah istilah geometris yang tepat untuk mendeskripsikan kedudukan atau posisi antargaris dari persamaan-persamaan tersebut?',
                'question_type' => 'short_text',
                'question_data' => [
                    'image' => 'images/quiz/bab1/grafik-soal-6.png',
                ],
                'answer_key' => [
                    'answer' => 'berimpit',
                ],
                'accepted_answers' => [
                    'berimpit',
                    'garis berimpit',
                    'koinsiden',
                    'coincident',
                    'berimpitan',
                ],
                'explanation' => 'Grafik menunjukkan garis yang berada pada posisi yang sama, sehingga kedudukan garis disebut berimpit atau koinsiden.',
                'points' => 10,
                'order_number' => 6,
            ],
            [
                'question_text' => 'Selesaikan Sistem Persamaan Linear berikut: x + y + z = 6, 2x − y + z = 3, x + 2y − z = 2. Tuliskan langkahnya pada canvas, lalu masukkan jawaban akhir.',
                'question_type' => 'canvas_final_answer',
                'question_data' => [
                    'equations' => [
                        'x + y + z = 6',
                        '2x − y + z = 3',
                        'x + 2y − z = 2',
                    ],
                    'final_fields' => ['x', 'y', 'z'],
                    'canvas_required' => true,
                ],
                'answer_key' => [
                    'final_answer' => [
                        'x' => '1',
                        'y' => '2',
                        'z' => '3',
                    ],
                ],
                'accepted_answers' => [
                    'x' => ['1'],
                    'y' => ['2'],
                    'z' => ['3'],
                ],
                'explanation' => 'Penyelesaian sistem menghasilkan x = 1, y = 2, dan z = 3.',
                'points' => 10,
                'order_number' => 7,
            ],
            [
                'question_text' => 'Perhatikan Sistem Persamaan Linear berikut: 2x + 3y − z = 5, x + 2z = 4, −x + y + z = 0. Ekstraksilah koefisien dari ketiga persamaan ke dalam struktur Matriks Koefisien (A) berukuran 3 × 3.',
                'question_type' => 'matrix',
                'question_data' => [
                    'rows' => 3,
                    'columns' => 3,
                    'label' => 'A',
                ],
                'answer_key' => [
                    'matrix' => [
                        [2, 3, -1],
                        [1, 0, 2],
                        [-1, 1, 1],
                    ],
                ],
                'accepted_answers' => null,
                'explanation' => 'Koefisien x, y, dan z dari setiap persamaan membentuk matriks A = [[2,3,-1],[1,0,2],[-1,1,1]].',
                'points' => 10,
                'order_number' => 8,
            ],
            [
                'question_text' => 'Diberikan Sistem Persamaan Linear: 4x − y + 2z = 7, −x + 3y − z = −2, 2x + y + 5z = 10. Lengkapi struktur notasi standar Ax = b berdasarkan komponen variabel dan nilai hasil dari sistem tersebut.',
                'question_type' => 'matrix_equation',
                'question_data' => [
                    'matrix_rows' => 3,
                    'matrix_columns' => 3,
                    'vector_x' => ['x', 'y', 'z'],
                    'vector_b_rows' => 3,
                ],
                'answer_key' => [
                    'A' => [
                        [4, -1, 2],
                        [-1, 3, -1],
                        [2, 1, 5],
                    ],
                    'x' => ['x', 'y', 'z'],
                    'b' => [7, -2, 10],
                ],
                'accepted_answers' => null,
                'explanation' => 'Bentuk Ax = b terdiri atas matriks koefisien A, vektor variabel x, dan vektor konstanta b.',
                'points' => 10,
                'order_number' => 9,
            ],
            [
                'question_text' => 'Sebagai tantangan terakhir, ubahlah Sistem Persamaan Linear berikut ke dalam format komputasi tunggal (Augmented Matrix): x − 2y + 3z = 9, −x + 3y = −4, 2x − 5y + 5z = 17.',
                'question_type' => 'augmented_matrix',
                'question_data' => [
                    'rows' => 3,
                    'columns' => 4,
                    'separator_before_column' => 4,
                ],
                'answer_key' => [
                    'matrix' => [
                        [1, -2, 3, 9],
                        [-1, 3, 0, -4],
                        [2, -5, 5, 17],
                    ],
                ],
                'accepted_answers' => null,
                'explanation' => 'Augmented matrix menggabungkan koefisien x, y, z dan konstanta ruas kanan dalam satu matriks.',
                'points' => 10,
                'order_number' => 10,
            ],
        ];

        foreach ($classGroups as $classGroup) {
            $quiz = Quiz::updateOrCreate(
                [
                    'class_group_id' => $classGroup->id,
                    'slug' => 'kuis-bab-1',
                ],
                [
                    'course_module_id' => $module->id,
                    'title' => 'Kuis Bab 1 - Sistem Persamaan Linear',
                    'type' => 'kuis_bab',
                    'description' => 'Kuis untuk mengukur pemahaman mahasiswa terhadap konsep Sistem Persamaan Linear.',
                    'instruction' => $instruction,
                    'duration_minutes' => 20,
                    'max_attempts' => 3,
                    'is_active' => true,
                ]
            );

            $quiz->questions()->delete();

            foreach ($questions as $question) {
                $quiz->questions()->create($question);
            }
        }
    }
}