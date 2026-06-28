<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::updateOrCreate(
            ['slug' => 'aljabar-linear-spl-obe'],
            [
                'title' => 'Aljabar Linear: Sistem Persamaan Linear dan OBE',
                'description' => 'Media pembelajaran interaktif untuk memahami Sistem Persamaan Linear, Operasi Baris Elementer, Eliminasi Gauss, dan Gauss-Jordan.',
                'level' => 'Dasar',
                'is_active' => true,
            ]
        );

        $modules = [
            [
                'title' => 'Bab 1 - Sistem Persamaan Linear',
                'slug' => 'bab-1-sistem-persamaan-linear',
                'description' => 'Membahas pengertian, bentuk umum, kemungkinan solusi, dan representasi SPL ke dalam matriks.',
                'order_number' => 1,
                'lessons' => [
                    [
                        'title' => '1.1 Pengertian Sistem Persamaan Linear',
                        'slug' => 'pengertian-sistem-persamaan-linear',
                        'estimated_minutes' => 15,
                        'order_number' => 1,
                    ],
                    [
                        'title' => '1.2 Bentuk Umum Sistem Persamaan Linear',
                        'slug' => 'bentuk-umum-sistem-persamaan-linear',
                        'estimated_minutes' => 15,
                        'order_number' => 2,
                    ],
                    [
                        'title' => '1.3 Kemungkinan Solusi Sistem Persamaan Linear',
                        'slug' => 'kemungkinan-solusi-sistem-persamaan-linear',
                        'estimated_minutes' => 15,
                        'order_number' => 3,
                    ],
                    [
                        'title' => '1.4 Metode Penyelesaian SPL menuju Representasi Matriks',
                        'slug' => 'metode-penyelesaian-spl-menuju-representasi-matriks',
                        'estimated_minutes' => 20,
                        'order_number' => 4,
                    ],
                ],
            ],
            [
                'title' => 'Bab 2 - Operasi Baris Elementer',
                'slug' => 'bab-2-operasi-baris-elementer',
                'description' => 'Membahas pengertian dan jenis-jenis Operasi Baris Elementer.',
                'order_number' => 2,
                'lessons' => [
                    [
                        'title' => '2.1 Pengertian Operasi Baris Elementer',
                        'slug' => 'pengertian-operasi-baris-elementer',
                        'estimated_minutes' => 15,
                        'order_number' => 1,
                    ],
                    [
                        'title' => '2.2 Jenis-Jenis Operasi Baris Elementer',
                        'slug' => 'jenis-jenis-operasi-baris-elementer',
                        'estimated_minutes' => 20,
                        'order_number' => 2,
                    ],
                ],
            ],
            [
                'title' => 'Bab 3 - Metode Eliminasi Gauss',
                'slug' => 'bab-3-metode-eliminasi-gauss',
                'description' => 'Membahas matriks eselon baris dan penyelesaian SPL dengan Eliminasi Gauss.',
                'order_number' => 3,
                'lessons' => [
                    [
                        'title' => '3.1 Algoritma Matriks Eselon Baris',
                        'slug' => 'algoritma-syarat-matriks-eselon-baris',
                        'estimated_minutes' => 20,
                        'order_number' => 1,
                    ],
                    [
                        'title' => '3.2 Simulasi Mengubah Matriks menjadi Eselon Baris',
                        'slug' => 'simulasi-mengubah-matriks-menjadi-eselon-baris',
                        'estimated_minutes' => 25,
                        'order_number' => 2,
                    ],
                    [
                        'title' => '3.3 Menyelesaikan SPL dengan Metode Eliminasi Gauss',
                        'slug' => 'menyelesaikan-spl-dengan-metode-eliminasi-gauss',
                        'estimated_minutes' => 25,
                        'order_number' => 3,
                    ],
                ],
            ],
            [
                'title' => 'Bab 4 - Metode Eliminasi Gauss-Jordan',
                'slug' => 'bab-4-metode-eliminasi-gauss-jordan',
                'description' => 'Membahas matriks eselon baris tereduksi dan penyelesaian SPL dengan Gauss-Jordan.',
                'order_number' => 4,
                'lessons' => [
                    [
                        'title' => '4.1 Algoritma Matriks Eselon Baris Tereduksi',
                        'slug' => 'algoritma-syarat-matriks-eselon-baris-tereduksi',
                        'estimated_minutes' => 20,
                        'order_number' => 1,
                    ],
                    [
                        'title' => '4.2 Simulasi Mengubah Matriks menjadi Eselon Baris Tereduksi',
                        'slug' => 'simulasi-mengubah-matriks-menjadi-eselon-baris-tereduksi',
                        'estimated_minutes' => 25,
                        'order_number' => 2,
                    ],
                    [
                        'title' => '4.3 Menyelesaikan SPL dengan Metode Eliminasi Gauss-Jordan',
                        'slug' => 'menyelesaikan-spl-dengan-metode-eliminasi-gauss-jordan',
                        'estimated_minutes' => 25,
                        'order_number' => 3,
                    ],
                ],
            ],
        ];

        foreach ($modules as $moduleData) {
            $module = CourseModule::updateOrCreate(
                ['slug' => $moduleData['slug']],
                [
                    'course_id' => $course->id,
                    'title' => $moduleData['title'],
                    'description' => $moduleData['description'],
                    'order_number' => $moduleData['order_number'],
                ]
            );

            foreach ($moduleData['lessons'] as $lessonData) {
                CourseLesson::updateOrCreate(
                    ['slug' => $lessonData['slug']],
                    [
                        'course_module_id' => $module->id,
                        'title' => $lessonData['title'],
                        'estimated_minutes' => $lessonData['estimated_minutes'],
                        'order_number' => $lessonData['order_number'],
                    ]
                );
            }
        }
    }
}