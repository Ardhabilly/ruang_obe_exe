<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\CourseLesson;
use App\Models\PracticeSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PracticeController extends Controller
{
    private const MAX_ATTEMPTS_PER_COMPONENT = 3;

    public function submit(Request $request, CourseLesson $lesson, string $practiceKey)
    {
        $request->validate([
            'answers' => ['nullable', 'array'],
        ]);

        $practice = $this->getPracticeDefinition($practiceKey);

        abort_if(! $practice, 404, 'Latihan tidak ditemukan.');

        $submission = PracticeSubmission::query()
            ->where('user_id', Auth::id())
            ->where('course_lesson_id', $lesson->id)
            ->where('practice_key', $practiceKey)
            ->first();

        $submittedAnswers = $request->input('answers', []);
        $submittedAnswers = is_array($submittedAnswers)
            ? $submittedAnswers
            : [];

        $storedAnswers = is_array($submission?->answers) ? $submission->answers : [];
        $storedFeedback = is_array($submission?->feedback) ? $submission->feedback : [];

        $previousFields = is_array($storedFeedback['fields'] ?? null)
            ? $storedFeedback['fields']
            : collect($storedFeedback)
                ->except(['_meta', 'groups', 'fields'])
                ->filter(fn ($item) => is_array($item))
                ->all();

        $previousMeta = is_array($storedFeedback['_meta'] ?? null)
            ? $storedFeedback['_meta']
            : [];

        $previousGroups = is_array($previousMeta['groups'] ?? null)
            ? $previousMeta['groups']
            : [];

        /*
        | Definisi latihan tertentu dapat diperbarui. Apabila versi definisi
        | berubah, jawaban dan status lama untuk latihan tersebut direset
        | agar seluruh kolom sesuai versi baru dapat dikerjakan kembali.
        */
        $expectedDefinitionVersion = $practice['definition_version'] ?? null;

        if (
            $expectedDefinitionVersion !== null
            && ($previousMeta['definition_version'] ?? null) !== $expectedDefinitionVersion
        ) {
            $storedAnswers = [];
            $previousFields = [];
            $previousGroups = [];
            $previousMeta = [];
        }
        /*
        | Revisi ini mengubah aturan kesempatan dari per nomor soal menjadi
        | per komponen pembelajaran. Riwayat dengan format lama diabaikan
        | saat mahasiswa melakukan pemeriksaan berikutnya agar perhitungan
        | dimulai dari aturan yang baru.
        */
        $usesComponentAttemptScope = ($previousMeta['attempt_scope'] ?? null) === 'component';

        /* PRACTICE_DEFINITION_VERSION_GUARD */
        $practiceDefinitionVersion = (string) ($practice['definition_version'] ?? '');
        $storedDefinitionVersion = (string) ($previousMeta['definition_version'] ?? '');

        if ($practiceDefinitionVersion !== '' && $storedDefinitionVersion !== $practiceDefinitionVersion) {
            $usesComponentAttemptScope = false;
        }

        if (! $usesComponentAttemptScope) {
            $storedAnswers = [];
            $previousFields = [];
            $previousGroups = [];
            $previousMeta = [];
        }

        $previousAttempts = max(
            0,
            min(
                self::MAX_ATTEMPTS_PER_COMPONENT,
                (int) ($previousMeta['attempts'] ?? 0)
            )
        );

        $previousComponentStatus = $previousMeta['status'] ?? null;

        if (in_array($previousComponentStatus, ['passed', 'assisted'], true)) {
            return back()
                ->withInput()
                ->with(
                    'practice_modal',
                    $this->buildModalPayload(
                        $practiceKey,
                        $practice,
                        $previousGroups,
                        $previousAttempts,
                        true,
                        $previousComponentStatus,
                    )
                );
        }

        $answers = $storedAnswers;
        $fields = [];
        $groups = [];
        $hasEmptyAnswer = false;

        foreach ($practice['groups'] as $groupKey => $groupDefinition) {
            $fieldKeys = $groupDefinition['fields'];
            $groupAllCorrect = true;

            foreach ($fieldKeys as $fieldKey) {
                $question = $practice['questions'][$fieldKey];
                $previousField = is_array($previousFields[$fieldKey] ?? null)
                    ? $previousFields[$fieldKey]
                    : [];

                $fieldWasCorrect = ! empty($previousField['is_correct'])
                    && empty($previousField['is_revealed']);

                $answer = $fieldWasCorrect
                    ? ($answers[$fieldKey] ?? $previousField['answer'] ?? '')
                    : ($submittedAnswers[$fieldKey] ?? '');

                $answers[$fieldKey] = $answer;

                $isEmpty = $this->isAnswerEmpty(
                    $answer,
                    $question['input_type'] ?? 'text'
                );

                if ($isEmpty) {
                    $hasEmptyAnswer = true;
                }

                $evaluated = $this->evaluateField($question, $answer);

                if (! $evaluated['is_correct']) {
                    $groupAllCorrect = false;
                }

                $fields[$fieldKey] = $this->buildFieldFeedback(
                    $question,
                    $answer,
                    $isEmpty
                        ? 'empty'
                        : ($evaluated['is_correct'] ? 'correct' : 'wrong'),
                    false,
                    $evaluated['option_statuses']
                );
            }

            $groups[$groupKey] = [
                'number' => $groupDefinition['number'],
                'status' => $groupAllCorrect ? 'passed' : 'in_progress',
                'points' => (int) ($groupDefinition['points'] ?? 0),
                'is_assisted' => false,
            ];
        }

        $allGroupsPassed = collect($groups)
            ->every(fn (array $group) => ($group['status'] ?? null) === 'passed');

        $attempts = $previousAttempts;
        $componentStatus = 'in_progress';
        $isCompleted = false;

        if ($allGroupsPassed) {
            $componentStatus = 'passed';
            $isCompleted = true;
        } elseif ($hasEmptyAnswer) {
            $componentStatus = 'incomplete';
        } else {
            $attempts++;

            if ($attempts >= self::MAX_ATTEMPTS_PER_COMPONENT) {
                $componentStatus = 'assisted';
                $isCompleted = true;

                foreach ($practice['groups'] as $groupKey => $groupDefinition) {
                    if (($groups[$groupKey]['status'] ?? null) === 'passed') {
                        continue;
                    }

                    $groups[$groupKey]['status'] = 'assisted';
                    $groups[$groupKey]['is_assisted'] = true;

                    foreach ($groupDefinition['fields'] as $fieldKey) {
                        $question = $practice['questions'][$fieldKey];
                        $field = $fields[$fieldKey] ?? [];

                        if (! empty($field['is_correct']) && empty($field['is_revealed'])) {
                            continue;
                        }

                        $revealedAnswer = $this->correctAnswerValue($question);

                        $answers[$fieldKey] = $revealedAnswer;
                        $fields[$fieldKey] = $this->buildFieldFeedback(
                            $question,
                            $revealedAnswer,
                            'revealed',
                            true,
                            $this->revealedOptionStatuses($question)
                        );
                    }
                }
            }
        }

        $score = collect($groups)
            ->filter(fn (array $group) => ($group['status'] ?? null) === 'passed')
            ->sum(fn (array $group) => (int) ($group['points'] ?? 0));

        if ((int) ($practice['max_score'] ?? 0) === 0) {
            $score = 0;
        }

        $assistedGroups = collect($groups)
            ->filter(fn (array $group) => ($group['status'] ?? null) === 'assisted')
            ->keys()
            ->values()
            ->all();

        $feedback = [
            'fields' => $fields,
            '_meta' => [
                'attempt_scope' => 'component',
                'definition_version' => $practice['definition_version'] ?? null,
                'definition_version' => $expectedDefinitionVersion,                'max_attempts' => self::MAX_ATTEMPTS_PER_COMPONENT,
                'attempts' => $attempts,
                'status' => $componentStatus,
                'groups' => $groups,
                'completion_mode' => $isCompleted
                    ? (empty($assistedGroups) ? 'mandiri' : 'bantuan')
                    : null,
                'assisted_groups' => $assistedGroups,
            ],
        ];

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
                'is_completed' => $isCompleted,
                'submitted_at' => now(),
            ]
        );


        return back()
            ->withInput()
            ->with(
                'practice_modal',
                $this->buildModalPayload(
                    $practiceKey,
                    $practice,
                    $groups,
                    $attempts,
                    $isCompleted,
                    $componentStatus,
                )
            );
    }

    private function buildModalPayload(
        string $practiceKey,
        array $practice,
        array $groups,
        int $attempts,
        bool $isCompleted,
        string $componentStatus,
    ): array {
        $baseTitle = match ($practice['type'] ?? '') {
            'aktivitas' => 'Aktivitas',
            'contoh_simulasi' => 'Contoh Simulasi',
            default => 'Cek Pemahaman',
        };

        $assisted = $componentStatus === 'assisted';
        $incomplete = $componentStatus === 'incomplete';
        $attemptScopeLabel = 'seluruh ' . strtolower($baseTitle);

        $unfinishedNumbers = collect($groups)
            ->filter(fn (array $group) => ! in_array($group['status'] ?? null, ['passed'], true))
            ->map(fn (array $group) => $group['number'] ?? null)
            ->filter()
            ->values()
            ->all();

        $groupMessage = empty($unfinishedNumbers)
            ? null
            : 'Bagian yang masih perlu diperbaiki: nomor ' . implode(', ', $unfinishedNumbers) . '.';

        if ($isCompleted) {
            return [
                'practice_key' => $practiceKey,
                'status' => $assisted ? 'assisted' : 'success',
                'title' => $assisted
                    ? "{$baseTitle} Selesai dengan Bantuan"
                    : "{$baseTitle} Selesai",
                'message' => $assisted
                    ? 'Tiga kesempatan untuk ' . $attemptScopeLabel . ' telah digunakan. Jawaban yang belum tepat telah ditampilkan pada kolom terkait.'
                    : 'Semua jawaban telah benar. Anda dapat melanjutkan ke tahap berikutnya.',
                'button_label' => 'Tutup',
                'feedback_messages' => array_values(array_filter([
                    $assisted ? 'Poin hanya diberikan pada soal aktivitas yang selesai benar secara mandiri.' : null,
                ])),
            ];
        }

        if ($incomplete) {
            return [
                'practice_key' => $practiceKey,
                'status' => 'incomplete',
                'title' => 'Jawaban Belum Lengkap',
                'message' => 'Lengkapi bagian berwarna kuning terlebih dahulu. Kolom yang belum diisi tidak mengurangi kesempatan.',
                'button_label' => 'Kembali Mengisi',
                'feedback_messages' => [],
            ];
        }

        $remainingAttempts = max(0, self::MAX_ATTEMPTS_PER_COMPONENT - $attempts);

        return [
            'practice_key' => $practiceKey,
            'status' => 'revision',
            'title' => 'Jawaban Perlu Diperbaiki',
            'message' => 'Periksa kembali jawaban berwarna merah dan baca umpan balik di bawah kolom terkait. Kesempatan tersisa untuk ' . $attemptScopeLabel . ': ' . $remainingAttempts . ' dari ' . self::MAX_ATTEMPTS_PER_COMPONENT . '.',
            'button_label' => 'Perbaiki Jawaban',
            'feedback_messages' => array_values(array_filter([$groupMessage])),
        ];
    }

    private function buildFieldFeedback(array $question, mixed $answer, string $state, bool $revealed = false, array $optionStatuses = []): array
    {
        $isCorrect = in_array($state, ['correct', 'revealed'], true);

        $feedback = [
            'is_correct' => $isCorrect,
            'is_revealed' => $revealed,
            'state' => $state,
            'answer' => $answer,
            'correct_answer' => $this->correctAnswerValue($question),
            'message' => match ($state) {
                'revealed' => 'Jawaban benar ditampilkan setelah tiga kesempatan. Pelajari kembali alasan jawaban ini sebelum melanjutkan.',
                'empty' => 'Bagian ini belum diisi. Lengkapi jawaban terlebih dahulu, kemudian lakukan pemeriksaan kembali.',
                'correct' => $question['feedback_correct'],
                default => $question['feedback_wrong'],
            },
        ];

        if (($question['input_type'] ?? 'text') === 'checkbox') {
            $feedback['option_statuses'] = $optionStatuses;
        }

        return $feedback;
    }

    private function evaluateField(array $question, mixed $answer): array
    {
        $inputType = $question['input_type'] ?? 'text';

        if ($inputType === 'checkbox') {
            $selected = $this->normalizeMultiple($answer);
            $accepted = $this->normalizeMultiple($question['accepted_answers']);

            return [
                'is_correct' => $selected === $accepted,
                'option_statuses' => $this->optionStatuses($answer, $question['accepted_answers']),
            ];
        }

        $studentAnswer = $this->normalize($answer);
        $acceptedAnswers = collect($question['accepted_answers'])
            ->map(fn ($accepted) => $this->normalize($accepted))
            ->all();

        return [
            'is_correct' => in_array($studentAnswer, $acceptedAnswers, true),
            'option_statuses' => [],
        ];
    }

    private function optionStatuses(mixed $selectedValue, array $acceptedAnswers): array
    {
        $selected = is_array($selectedValue) ? $selectedValue : [];
        $normalizedSelected = $this->normalizeMultiple($selected);
        $normalizedAccepted = $this->normalizeMultiple($acceptedAnswers);

        $optionKeys = array_values(array_unique(array_merge(
            array_map(fn ($item) => (string) $item, $selected),
            array_map(fn ($item) => (string) $item, $acceptedAnswers)
        )));

        $statuses = [];

        foreach ($optionKeys as $optionKey) {
            $normalized = $this->normalize($optionKey);
            $isChecked = in_array($normalized, $normalizedSelected, true);
            $shouldBeChecked = in_array($normalized, $normalizedAccepted, true);

            $statuses[$optionKey] = [
                'is_checked' => $isChecked,
                'should_be_checked' => $shouldBeChecked,

                /*
                | Jangan memberi penanda pada opsi benar yang belum dipilih.
                | Dengan begitu, mahasiswa tidak dapat menebak jawaban dari warna
                | sebelum sistem menampilkan bantuan pada kesempatan terakhir.
                */
                'state' => $isChecked
                    ? ($shouldBeChecked ? 'correct' : 'wrong')
                    : 'neutral',
            ];
        }

        return $statuses;
    }

    private function revealedOptionStatuses(array $question): array
    {
        if (($question['input_type'] ?? 'text') !== 'checkbox') {
            return [];
        }

        return collect($question['accepted_answers'])
            ->mapWithKeys(fn ($option) => [(string) $option => [
                'is_checked' => true,
                'should_be_checked' => true,
                'state' => 'revealed',
            ]])
            ->all();
    }

    private function isAnswerEmpty(mixed $answer, string $inputType): bool
    {
        if ($inputType === 'checkbox') {
            return ! is_array($answer)
                || count(array_filter($answer, fn ($item) => trim((string) $item) !== '')) === 0;
        }

        return trim((string) $answer) === '';
    }

    private function correctAnswerValue(array $question): mixed
    {
        if (($question['input_type'] ?? 'text') === 'checkbox') {
            return $question['accepted_answers'];
        }

        return $question['display_answer'] ?? ($question['accepted_answers'][0] ?? '');
    }

    private function getPracticeDefinition(string $practiceKey): ?array
    {

                /* SUBBAB_4_1_ESELON_TEREDUKSI_CALL_START */
        if ($definition = $this->getSubbab41PracticeDefinition($practiceKey)) {
            return $definition;
        }
        /* SUBBAB_4_1_ESELON_TEREDUKSI_CALL_END */


        if ($definition = $this->getSubbab42PracticeDefinition($practiceKey)) {
            return $definition;
        }

        if ($definition = $this->getSubbab43PracticeDefinition($practiceKey)) {
            return $definition;
        }


        if ($definition = $this->getSubbab33PracticeDefinition($practiceKey)) {
            return $definition;
        }        if ($definition = $this->getSubbab32PracticeDefinition($practiceKey)) {
            return $definition;
        }

        if ($definition = $this->getSubbab31PracticeDefinition($practiceKey)) {
            return $definition;
        }
        if ($definition = $this->getSubbab22PracticeDefinition($practiceKey)) {
            return $definition;
        }

        return match ($practiceKey) {
            'aktivitas-1-1' => [
                'title' => 'Aktivitas 1.1 - Laboratorium Validasi Aljabar',
                'type' => 'aktivitas',
                'max_score' => 100,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['q1_suku_bermasalah', 'q1_pangkat'],
                        'points' => 34,
                    ],
                    'q2' => [
                        'number' => 2,
                        'fields' => ['q2_pelanggar'],
                        'points' => 33,
                    ],
                    'q3' => [
                        'number' => 3,
                        'fields' => ['q3_pelanggar'],
                        'points' => 33,
                    ],
                ],
                'questions' => [
                    'q1_suku_bermasalah' => [
                        'accepted_answers' => ['tidak ada', 'tidakada'],
                        'display_answer' => 'Tidak Ada',
                        'feedback_correct' => 'Benar. Persamaan ini sudah linear, sehingga tidak ada suku bermasalah.',
                        'feedback_wrong' => 'Belum tepat. Pada persamaan ini semua variabel berpangkat satu dan tidak memuat akar, hasil kali antarvariabel, atau fungsi khusus.',
                    ],
                    'q1_pangkat' => [
                        'accepted_answers' => ['1', 'satu', 'pangkat satu'],
                        'display_answer' => '1',
                        'feedback_correct' => 'Benar. Pangkat tertinggi semua variabel pada persamaan tersebut adalah 1.',
                        'feedback_wrong' => 'Belum tepat. Perhatikan x₁, x₂, dan x₃. Semua variabel pada persamaan tersebut berpangkat satu.',
                    ],
                    'q2_pelanggar' => [
                        'accepted_answers' => ['3√y', '√y', '3sqrt y', 'sqrt y', '3sqrt(y)', 'sqrt(y)', '3 akar y', 'akar y'],
                        'display_answer' => '3√y',
                        'feedback_correct' => 'Benar. Suku 3√y atau √y melanggar aturan linearitas karena variabel berada di dalam akar.',
                        'feedback_wrong' => 'Belum tepat. Cari suku yang memuat variabel di dalam tanda akar.',
                    ],
                    'q3_pelanggar' => [
                        'accepted_answers' => ['x₁x₂', 'x1x2', 'x1 x2', 'x1*x2', 'x_1x_2', 'x_1 x_2', 'x_1*x_2'],
                        'display_answer' => 'x₁x₂',
                        'feedback_correct' => 'Benar. Suku x₁x₂ melanggar aturan linearitas karena terjadi perkalian antarvariabel.',
                        'feedback_wrong' => 'Belum tepat. Cari suku yang menunjukkan dua variabel dikalikan secara langsung.',
                    ],
                ],
            ],

            'cek-pemahaman-1-2' => [
                'title' => 'Cek Pemahaman 1.2 - Bentuk Umum Sistem Persamaan Linear',
                'type' => 'cek_pemahaman',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['m', 'n'],
                        'points' => 0,
                    ],
                    'q2' => [
                        'number' => 2,
                        'fields' => ['a12', 'a23', 'a32'],
                        'points' => 0,
                    ],
                    'q3' => [
                        'number' => 3,
                        'fields' => ['b1', 'b2', 'b3', 'jenis'],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'm' => [
                        'accepted_answers' => ['3', 'tiga'],
                        'display_answer' => '3',
                        'feedback_correct' => 'Benar. Sistem terdiri dari tiga persamaan.',
                        'feedback_wrong' => 'Belum tepat. Hitung banyaknya baris persamaan pada sistem.',
                    ],
                    'n' => [
                        'accepted_answers' => ['3', 'tiga'],
                        'display_answer' => '3',
                        'feedback_correct' => 'Benar. Sistem melibatkan tiga variabel.',
                        'feedback_wrong' => 'Belum tepat. Perhatikan banyaknya variabel yang digunakan.',
                    ],
                    'a12' => [
                        'accepted_answers' => ['-2'],
                        'display_answer' => '-2',
                        'feedback_correct' => 'Benar. Koefisien a₁₂ adalah -2.',
                        'feedback_wrong' => 'Belum tepat. a₁₂ berada pada baris pertama dan kolom kedua.',
                    ],
                    'a23' => [
                        'accepted_answers' => ['-1'],
                        'display_answer' => '-1',
                        'feedback_correct' => 'Benar. Koefisien a₂₃ adalah -1.',
                        'feedback_wrong' => 'Belum tepat. a₂₃ berada pada baris kedua dan kolom ketiga.',
                    ],
                    'a32' => [
                        'accepted_answers' => ['0', 'nol'],
                        'display_answer' => '0',
                        'feedback_correct' => 'Benar. Koefisien a₃₂ adalah 0.',
                        'feedback_wrong' => 'Belum tepat. Tidak ada suku y pada persamaan ketiga, sehingga koefisiennya 0.',
                    ],
                    'b1' => [
                        'accepted_answers' => ['14'],
                        'display_answer' => '14',
                        'feedback_correct' => 'Benar. b₁ bernilai 14.',
                        'feedback_wrong' => 'Belum tepat. Lihat konstanta pada ruas kanan persamaan pertama.',
                    ],
                    'b2' => [
                        'accepted_answers' => ['0', 'nol'],
                        'display_answer' => '0',
                        'feedback_correct' => 'Benar. b₂ bernilai 0.',
                        'feedback_wrong' => 'Belum tepat. Lihat konstanta pada ruas kanan persamaan kedua.',
                    ],
                    'b3' => [
                        'accepted_answers' => ['-7'],
                        'display_answer' => '-7',
                        'feedback_correct' => 'Benar. b₃ bernilai -7.',
                        'feedback_wrong' => 'Belum tepat. Lihat konstanta pada ruas kanan persamaan ketiga.',
                    ],
                    'jenis' => [
                        'accepted_answers' => ['nonhomogen', 'non-homogen', 'sistem persamaan linear nonhomogen', 'sistem persamaan linear non-homogen'],
                        'display_answer' => 'Non-Homogen',
                        'feedback_correct' => 'Benar. Ada konstanta yang tidak bernilai 0, sehingga sistemnya non-homogen.',
                        'feedback_wrong' => 'Belum tepat. Sistem non-homogen memiliki minimal satu konstanta yang tidak bernilai 0.',
                    ],
                ],
            ],

            'aktivitas-1-2-server' => [
                'title' => 'Aktivitas 1.2 Pemodelan Alokasi Sumber Daya Server',
                'type' => 'aktivitas',
                'max_score' => 100,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['q1_jumlah_persamaan', 'q1_jumlah_variabel'],
                        'points' => 20,
                    ],
                    'q2' => [
                        'number' => 2,
                        'fields' => ['q2_persamaan_cpu'],
                        'points' => 20,
                    ],
                    'q3' => [
                        'number' => 3,
                        'fields' => ['q3_persamaan_ram'],
                        'points' => 20,
                    ],
                    'q4' => [
                        'number' => 4,
                        'fields' => ['q4_jenis_sistem'],
                        'points' => 20,
                    ],
                    'q5' => [
                        'number' => 5,
                        'fields' => ['q5_pernyataan'],
                        'points' => 20,
                    ],
                ],
                'questions' => [
                    'q1_jumlah_persamaan' => [
                        'accepted_answers' => ['2', 'dua'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Ada dua batas sumber daya yang dimodelkan, yaitu CPU dan RAM, sehingga terdapat dua persamaan.',
                        'feedback_wrong' => 'Belum tepat. Hitung jumlah batas sumber daya yang digunakan dalam kasus ini.',
                    ],
                    'q1_jumlah_variabel' => [
                        'accepted_answers' => ['2', 'dua'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Model memiliki dua variabel, yaitu x untuk Layanan Web dan y untuk Layanan Basis Data.',
                        'feedback_wrong' => 'Belum tepat. Perhatikan jumlah jenis layanan yang dinyatakan sebagai variabel.',
                    ],
                    'q2_persamaan_cpu' => [
                        'accepted_answers' => ['2x+3y=40', '3y+2x=40'],
                        'display_answer' => '2x + 3y = 40',
                        'feedback_correct' => 'Benar. Layanan Web membutuhkan 2 unit CPU dan Layanan Basis Data membutuhkan 3 unit CPU, dengan total 40 unit CPU.',
                        'feedback_wrong' => 'Belum tepat. Gunakan kebutuhan CPU: 2 untuk setiap x dan 3 untuk setiap y, dengan batas total 40.',
                    ],
                    'q3_persamaan_ram' => [
                        'accepted_answers' => ['x+4y=65', '1x+4y=65', '4y+x=65', '4y+1x=65'],
                        'display_answer' => 'x + 4y = 65',
                        'feedback_correct' => 'Benar. Layanan Web membutuhkan 1 unit RAM dan Layanan Basis Data membutuhkan 4 unit RAM, dengan total 65 unit RAM.',
                        'feedback_wrong' => 'Belum tepat. Gunakan kebutuhan RAM: 1 untuk setiap x dan 4 untuk setiap y, dengan batas total 65.',
                    ],
                    'q4_jenis_sistem' => [
                        'accepted_answers' => ['homogen', 'sistem homogen', 'spl homogen'],
                        'display_answer' => 'Homogen',
                        'feedback_correct' => 'Benar. Jika seluruh konstanta pada ruas kanan bernilai 0, sistem tersebut menjadi SPL homogen.',
                        'feedback_wrong' => 'Belum tepat. Sistem dengan seluruh konstanta di ruas kanan sama dengan 0 disebut sistem homogen.',
                    ],
                    'q5_pernyataan' => [
                        'input_type' => 'checkbox',
                        'accepted_answers' => ['a11', 'b2', 'non_homogen'],
                        'feedback_correct' => 'Benar. Pernyataan pertama, ketiga, dan keempat benar. Pada persamaan RAM, koefisien x adalah 1, bukan 0.',
                        'feedback_wrong' => 'Belum tepat. Ingat bahwa x pada persamaan RAM dapat ditulis sebagai 1x. Pernyataan yang benar adalah pertama, ketiga, dan keempat.',
                    ],
                ],
            ],


            'cek-pemahaman-1-3' => [
                'title' => 'Cek Pemahaman 1.3 - Kemungkinan Solusi Sistem Persamaan Linear',
                'type' => 'cek_pemahaman',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['cek_q1_garis'],
                        'points' => 0,
                    ],
                    'q2' => [
                        'number' => 2,
                        'fields' => ['cek_q2_garis'],
                        'points' => 0,
                    ],
                    'q3' => [
                        'number' => 3,
                        'fields' => ['cek_q3_pernyataan'],
                        'points' => 0,
                    ],
                    'q4' => [
                        'number' => 4,
                        'fields' => ['cek_q4_konsistensi'],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'cek_q1_garis' => [
                        'accepted_answers' => ['berpotongan'],
                        'display_answer' => 'BERPOTONGAN',
                        'feedback_correct' => 'Benar. Dua garis dengan kemiringan berbeda bertemu pada satu titik, sehingga sistem memiliki tepat satu solusi.',
                        'feedback_wrong' => 'Belum tepat. Amati dua garis yang bertemu pada satu koordinat. Kondisi tersebut bukan berhimpit dan bukan sejajar.',
                    ],
                    'cek_q2_garis' => [
                        'accepted_answers' => ['berhimpit'],
                        'display_answer' => 'BERHIMPIT',
                        'feedback_correct' => 'Benar. Kedua garis menumpuk pada jalur yang sama, sehingga setiap titik pada garis menjadi solusi.',
                        'feedback_wrong' => 'Belum tepat. Perhatikan bahwa dua garis tidak memiliki jalur yang berbeda dan tidak hanya bertemu pada satu titik.',
                    ],
                    'cek_q3_pernyataan' => [
                        'input_type' => 'checkbox',
                        'accepted_answers' => ['cek_q3_a', 'cek_q3_b', 'cek_q3_d'],
                        'feedback_correct' => 'Benar. Dua persamaan yang memiliki ruas kiri sama tetapi konstanta berbeda membentuk garis sejajar. Sistemnya tidak memiliki solusi dan bersifat inkonsisten.',
                        'feedback_wrong' => 'Belum tepat. Perhatikan dua persamaan dengan ruas kiri yang sama, tetapi konstanta berbeda. Garisnya tidak akan bertemu.',
                    ],
                    'cek_q4_konsistensi' => [
                        'accepted_answers' => ['inkonsisten', 'sistem inkonsisten'],
                        'display_answer' => 'INKONSISTEN',
                        'feedback_correct' => 'Benar. Sistem yang tidak memiliki solusi disebut sistem inkonsisten.',
                        'feedback_wrong' => 'Belum tepat. Sistem konsisten memiliki paling tidak satu solusi, sedangkan sistem pada grafik sejajar tidak memiliki solusi.',
                    ],
                ],
            ],

            'aktivitas-1-3-solusi' => [
                'title' => 'Aktivitas 1.3 Analisis Skenario Solusi di Dunia Nyata',
                'type' => 'aktivitas',
                'max_score' => 100,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['aktivitas_q1_solusi'],
                        'points' => 34,
                    ],
                    'q2' => [
                        'number' => 2,
                        'fields' => ['aktivitas_q2_solusi'],
                        'points' => 33,
                    ],
                    'q3' => [
                        'number' => 3,
                        'fields' => ['aktivitas_q3_pernyataan'],
                        'points' => 33,
                    ],
                ],
                'questions' => [
                    'aktivitas_q1_solusi' => [
                        'accepted_answers' => ['solusi tunggal'],
                        'display_answer' => 'SOLUSI TUNGGAL',
                        'feedback_correct' => 'Benar. Dua garis dengan arah kemiringan berbeda berpotongan pada satu titik, sehingga sistem memiliki solusi tunggal.',
                        'feedback_wrong' => 'Belum tepat. Jika dua garis dengan kemiringan berbeda saling berpotongan pada satu titik, sistem tidak memiliki banyak solusi.',
                    ],
                    'aktivitas_q2_solusi' => [
                        'accepted_answers' => ['solusi banyak', 'tak berhingga banyaknya solusi', 'tak hingga banyaknya solusi'],
                        'display_answer' => 'SOLUSI BANYAK',
                        'feedback_correct' => 'Benar. Dua garis yang berhimpit memiliki semua titik yang sama, sehingga sistem memiliki solusi banyak.',
                        'feedback_wrong' => 'Belum tepat. Perhatikan bahwa aturan kedua merupakan salinan dari aturan pertama, sehingga kedua garis berhimpit.',
                    ],
                    'aktivitas_q3_pernyataan' => [
                        'input_type' => 'checkbox',
                        'accepted_answers' => ['aktivitas_q3_a', 'aktivitas_q3_d'],
                        'feedback_correct' => 'Benar. Aturan total poin 50 dan 100 tidak dapat dipenuhi secara bersamaan. Dua garis sejajar menghasilkan sistem inkonsisten dengan 0 solusi.',
                        'feedback_wrong' => 'Belum tepat. Karena kedua aturan memiliki bentuk ruas kiri sama tetapi konstanta berbeda, garisnya sejajar dan sistem tidak memiliki solusi.',
                    ],
                ],
            ],


            'contoh-simulasi-1-4-perhitungan' => [
                'title' => 'Contoh Simulasi 1.4 - Penyelesaian SPL Skala Kecil',
                'type' => 'contoh_simulasi',
                'max_score' => 0,
                'groups' => [
                    'perhitungan' => [
                        'number' => 1,
                        'fields' => [
                            'sim_l3_ruas_kiri',
                            'sim_l3_ruas_kanan',
                            'sim_l3_nilai_x',
                            'sim_l4_y_sub_x',
                            'sim_l4_y_pengurang',
                            'sim_l4_nilai_y',
                            'sim_l4_z_sub_x',
                            'sim_l4_z_sub_y',
                            'sim_l4_z_ruas_kiri',
                            'sim_l4_z_pengurang',
                            'sim_l4_z_negatif',
                            'sim_l4_nilai_z',
                            'sim_hasil_x',
                            'sim_hasil_y',
                            'sim_hasil_z',
                        ],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'sim_l3_ruas_kiri' => [
                        'accepted_answers' => ['3x'],
                        'display_answer' => '3x',
                        'feedback_correct' => 'Benar. Setelah Persamaan 5 dikurangkan dari Persamaan 4, suku y habis dan ruas kiri menjadi 3x.',
                        'feedback_wrong' => 'Belum tepat. Kurangkan (x + y) dari (4x + y). Hasil pada ruas kiri adalah 3x.',
                    ],
                    'sim_l3_ruas_kanan' => [
                        'accepted_answers' => ['6'],
                        'display_answer' => '6',
                        'feedback_correct' => 'Benar. Ruas kanan diperoleh dari 9 - 3, yaitu 6.',
                        'feedback_wrong' => 'Belum tepat. Kurangkan ruas kanan Persamaan 5 dari ruas kanan Persamaan 4: 9 - 3.',
                    ],
                    'sim_l3_nilai_x' => [
                        'accepted_answers' => ['2'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Dari 3x = 6 diperoleh x = 2.',
                        'feedback_wrong' => 'Belum tepat. Bagi kedua ruas pada 3x = 6 dengan 3.',
                    ],
                    'sim_l4_y_sub_x' => [
                        'accepted_answers' => ['2'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Nilai x yang disubstitusikan ke Persamaan 5 adalah 2.',
                        'feedback_wrong' => 'Belum tepat. Gunakan nilai x yang diperoleh pada Langkah 3.',
                    ],
                    'sim_l4_y_pengurang' => [
                        'accepted_answers' => ['2'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Dari 2 + y = 3 diperoleh y = 3 - 2.',
                        'feedback_wrong' => 'Belum tepat. Pindahkan konstanta 2 ke ruas kanan dengan operasi pengurangan.',
                    ],
                    'sim_l4_nilai_y' => [
                        'accepted_answers' => ['1'],
                        'display_answer' => '1',
                        'feedback_correct' => 'Benar. Nilai y adalah 1.',
                        'feedback_wrong' => 'Belum tepat. Selesaikan 3 - 2 untuk memperoleh nilai y.',
                    ],
                    'sim_l4_z_sub_x' => [
                        'accepted_answers' => ['2'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Nilai x yang disubstitusikan ke Persamaan 2 adalah 2.',
                        'feedback_wrong' => 'Belum tepat. Gunakan nilai x yang sudah diperoleh pada Langkah 3.',
                    ],
                    'sim_l4_z_sub_y' => [
                        'accepted_answers' => ['1'],
                        'display_answer' => '1',
                        'feedback_correct' => 'Benar. Nilai y yang disubstitusikan ke Persamaan 2 adalah 1.',
                        'feedback_wrong' => 'Belum tepat. Gunakan nilai y yang sudah diperoleh dari Persamaan 5.',
                    ],
                    'sim_l4_z_ruas_kiri' => [
                        'accepted_answers' => ['1'],
                        'display_answer' => '1',
                        'feedback_correct' => 'Benar. Ruas kiri 2 - 1 - z disederhanakan menjadi 1 - z.',
                        'feedback_wrong' => 'Belum tepat. Sederhanakan bagian konstanta pada 2 - 1 - z.',
                    ],
                    'sim_l4_z_pengurang' => [
                        'accepted_answers' => ['1'],
                        'display_answer' => '1',
                        'feedback_correct' => 'Benar. Dari 1 - z = 2 diperoleh -z = 2 - 1.',
                        'feedback_wrong' => 'Belum tepat. Pindahkan konstanta 1 ke ruas kanan dengan operasi pengurangan.',
                    ],
                    'sim_l4_z_negatif' => [
                        'accepted_answers' => ['1'],
                        'display_answer' => '1',
                        'feedback_correct' => 'Benar. Hasil dari 2 - 1 adalah 1, sehingga -z = 1.',
                        'feedback_wrong' => 'Belum tepat. Hitung 2 - 1 terlebih dahulu.',
                    ],
                    'sim_l4_nilai_z' => [
                        'accepted_answers' => ['-1'],
                        'display_answer' => '-1',
                        'feedback_correct' => 'Benar. Karena -z = 1, maka z = -1.',
                        'feedback_wrong' => 'Belum tepat. Jika -z = 1, kalikan kedua ruas dengan -1.',
                    ],
                    'sim_hasil_x' => [
                        'accepted_answers' => ['2'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Nilai x pada himpunan penyelesaian adalah 2.',
                        'feedback_wrong' => 'Belum tepat. Gunakan hasil eliminasi pada Langkah 3.',
                    ],
                    'sim_hasil_y' => [
                        'accepted_answers' => ['1'],
                        'display_answer' => '1',
                        'feedback_correct' => 'Benar. Nilai y pada himpunan penyelesaian adalah 1.',
                        'feedback_wrong' => 'Belum tepat. Gunakan hasil substitusi ke Persamaan 5.',
                    ],
                    'sim_hasil_z' => [
                        'accepted_answers' => ['-1'],
                        'display_answer' => '-1',
                        'feedback_correct' => 'Benar. Nilai z pada himpunan penyelesaian adalah -1.',
                        'feedback_wrong' => 'Belum tepat. Gunakan hasil substitusi ke Persamaan 2.',
                    ],
                ],
            ],

            'cek-pemahaman-1-4-metode' => [
                'title' => 'Cek Pemahaman 1.4 - Keterbatasan Metode Dasar',
                'type' => 'cek_pemahaman',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['cek_metode_pernyataan'],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'cek_metode_pernyataan' => [
                        'input_type' => 'checkbox',
                        'accepted_answers' => ['cek_metode_a', 'cek_metode_c'],
                        'feedback_correct' => 'Benar. Untuk SPL berukuran besar, proses eliminasi-substitusi manual cenderung panjang dan rawan kesalahan operasi berulang.',
                        'feedback_wrong' => 'Belum tepat. Pertimbangkan dampak banyaknya variabel dan operasi tanda tambah atau kurang yang harus ditulis secara manual.',
                    ],
                ],
            ],

            'contoh-simulasi-1-4-matriks-a' => [
                'title' => 'Contoh Simulasi 1.4 - Ekstraksi Matriks Koefisien',
                'type' => 'contoh_simulasi',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => [
                            'ma11', 'ma12', 'ma13',
                            'ma21', 'ma22', 'ma23',
                            'ma31', 'ma32', 'ma33',
                        ],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'ma11' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar. Koefisien x pada persamaan pertama adalah 2.', 'feedback_wrong' => 'Belum tepat. Lihat koefisien x pada persamaan pertama.'],
                    'ma12' => ['accepted_answers' => ['3'], 'display_answer' => '3', 'feedback_correct' => 'Benar. Koefisien y pada persamaan pertama adalah 3.', 'feedback_wrong' => 'Belum tepat. Lihat koefisien y pada persamaan pertama.'],
                    'ma13' => ['accepted_answers' => ['-1'], 'display_answer' => '-1', 'feedback_correct' => 'Benar. Koefisien z pada persamaan pertama adalah -1.', 'feedback_wrong' => 'Belum tepat. Perhatikan tanda negatif di depan z pada persamaan pertama.'],
                    'ma21' => ['accepted_answers' => ['4'], 'display_answer' => '4', 'feedback_correct' => 'Benar. Koefisien x pada persamaan kedua adalah 4.', 'feedback_wrong' => 'Belum tepat. Lihat koefisien x pada persamaan kedua.'],
                    'ma22' => ['accepted_answers' => ['-1'], 'display_answer' => '-1', 'feedback_correct' => 'Benar. Koefisien y pada persamaan kedua adalah -1.', 'feedback_wrong' => 'Belum tepat. Perhatikan tanda negatif di depan y pada persamaan kedua.'],
                    'ma23' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar. Koefisien z pada persamaan kedua adalah 2.', 'feedback_wrong' => 'Belum tepat. Lihat koefisien z pada persamaan kedua.'],
                    'ma31' => ['accepted_answers' => ['-1'], 'display_answer' => '-1', 'feedback_correct' => 'Benar. Koefisien x pada persamaan ketiga adalah -1.', 'feedback_wrong' => 'Belum tepat. Perhatikan tanda negatif di depan x pada persamaan ketiga.'],
                    'ma32' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar. Koefisien y pada persamaan ketiga adalah 2.', 'feedback_wrong' => 'Belum tepat. Lihat koefisien y pada persamaan ketiga.'],
                    'ma33' => ['accepted_answers' => ['3'], 'display_answer' => '3', 'feedback_correct' => 'Benar. Koefisien z pada persamaan ketiga adalah 3.', 'feedback_wrong' => 'Belum tepat. Lihat koefisien z pada persamaan ketiga.'],
                ],
            ],

            'contoh-simulasi-1-4-ax-b' => [
                'title' => 'Contoh Simulasi 1.4 - Persamaan Matriks Ax = b',
                'type' => 'contoh_simulasi',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['axb_x1', 'axb_x2', 'axb_x3', 'axb_b1', 'axb_b2', 'axb_b3'],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'axb_x1' => ['accepted_answers' => ['x'], 'display_answer' => 'x', 'feedback_correct' => 'Benar. Entri pertama pada matriks variabel adalah x.', 'feedback_wrong' => 'Belum tepat. Matriks variabel disusun sesuai urutan variabel pada SPL, yaitu x, y, z.'],
                    'axb_x2' => ['accepted_answers' => ['y'], 'display_answer' => 'y', 'feedback_correct' => 'Benar. Entri kedua pada matriks variabel adalah y.', 'feedback_wrong' => 'Belum tepat. Gunakan urutan variabel pada sistem, yaitu x, y, z.'],
                    'axb_x3' => ['accepted_answers' => ['z'], 'display_answer' => 'z', 'feedback_correct' => 'Benar. Entri ketiga pada matriks variabel adalah z.', 'feedback_wrong' => 'Belum tepat. Gunakan urutan variabel pada sistem, yaitu x, y, z.'],
                    'axb_b1' => ['accepted_answers' => ['5'], 'display_answer' => '5', 'feedback_correct' => 'Benar. Konstanta pada ruas kanan persamaan pertama adalah 5.', 'feedback_wrong' => 'Belum tepat. Lihat nilai pada ruas kanan Persamaan 1.'],
                    'axb_b2' => ['accepted_answers' => ['-1'], 'display_answer' => '-1', 'feedback_correct' => 'Benar. Konstanta pada ruas kanan persamaan kedua adalah -1.', 'feedback_wrong' => 'Belum tepat. Lihat nilai pada ruas kanan Persamaan 2.'],
                    'axb_b3' => ['accepted_answers' => ['4'], 'display_answer' => '4', 'feedback_correct' => 'Benar. Konstanta pada ruas kanan persamaan ketiga adalah 4.', 'feedback_wrong' => 'Belum tepat. Lihat nilai pada ruas kanan Persamaan 3.'],
                ],
            ],

            'cek-pemahaman-1-4-ax-b' => [
                'title' => 'Cek Pemahaman 1.4 - Notasi Ax = b',
                'type' => 'cek_pemahaman',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['cek_axb_pernyataan'],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'cek_axb_pernyataan' => [
                        'input_type' => 'checkbox',
                        'accepted_answers' => ['cek_axb_a', 'cek_axb_c'],
                        'feedback_correct' => 'Benar. Notasi b adalah matriks konstanta pada ruas kanan, sedangkan entri A dibentuk oleh koefisien yang mendampingi variabel.',
                        'feedback_wrong' => 'Belum tepat. Ingat bahwa x berbentuk vektor kolom, sedangkan b tidak harus bernilai 0.',
                    ],
                ],
            ],

            'contoh-simulasi-1-4-augmented' => [
                'title' => 'Contoh Simulasi 1.4 - Augmented Matrix',
                'type' => 'contoh_simulasi',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => [
                            'aug11', 'aug12', 'aug13', 'aug14',
                            'aug21', 'aug22', 'aug23', 'aug24',
                            'aug31', 'aug32', 'aug33', 'aug34',
                        ],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'aug11' => [
                        'accepted_answers' => ['2'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Elemen pada baris pertama sudah sesuai.',
                        'feedback_wrong' => 'Belum tepat. Periksa kembali koefisien variabel x pada persamaan pertama.',
                    ],
                    'aug12' => [
                        'accepted_answers' => ['3'],
                        'display_answer' => '3',
                        'feedback_correct' => 'Benar. Elemen pada baris pertama sudah sesuai.',
                        'feedback_wrong' => 'Belum tepat. Periksa kembali koefisien variabel y pada persamaan pertama.',
                    ],
                    'aug13' => [
                        'accepted_answers' => ['-1'],
                        'display_answer' => '-1',
                        'feedback_correct' => 'Benar. Elemen pada baris pertama sudah sesuai.',
                        'feedback_wrong' => 'Belum tepat. Perhatikan tanda koefisien variabel z pada persamaan pertama.',
                    ],
                    'aug14' => [
                        'accepted_answers' => ['5'],
                        'display_answer' => '5',
                        'feedback_correct' => 'Benar. Konstanta pada baris pertama sudah sesuai.',
                        'feedback_wrong' => 'Belum tepat. Periksa nilai konstanta pada ruas kanan persamaan pertama.',
                    ],

                    'aug21' => [
                        'accepted_answers' => ['4'],
                        'display_answer' => '4',
                        'feedback_correct' => 'Benar. Elemen pada baris kedua sudah sesuai.',
                        'feedback_wrong' => 'Belum tepat. Periksa kembali koefisien variabel x pada persamaan kedua.',
                    ],
                    'aug22' => [
                        'accepted_answers' => ['-1'],
                        'display_answer' => '-1',
                        'feedback_correct' => 'Benar. Elemen pada baris kedua sudah sesuai.',
                        'feedback_wrong' => 'Belum tepat. Perhatikan tanda koefisien variabel y pada persamaan kedua.',
                    ],
                    'aug23' => [
                        'accepted_answers' => ['2'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Elemen pada baris kedua sudah sesuai.',
                        'feedback_wrong' => 'Belum tepat. Periksa kembali koefisien variabel z pada persamaan kedua.',
                    ],
                    'aug24' => [
                        'accepted_answers' => ['-1'],
                        'display_answer' => '-1',
                        'feedback_correct' => 'Benar. Konstanta pada baris kedua sudah sesuai.',
                        'feedback_wrong' => 'Belum tepat. Perhatikan tanda nilai konstanta pada ruas kanan persamaan kedua.',
                    ],

                    'aug31' => [
                        'accepted_answers' => ['-1'],
                        'display_answer' => '-1',
                        'feedback_correct' => 'Benar. Elemen pada baris ketiga sudah sesuai.',
                        'feedback_wrong' => 'Belum tepat. Perhatikan tanda koefisien variabel x pada persamaan ketiga.',
                    ],
                    'aug32' => [
                        'accepted_answers' => ['2'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Elemen pada baris ketiga sudah sesuai.',
                        'feedback_wrong' => 'Belum tepat. Periksa kembali koefisien variabel y pada persamaan ketiga.',
                    ],
                    'aug33' => [
                        'accepted_answers' => ['3'],
                        'display_answer' => '3',
                        'feedback_correct' => 'Benar. Elemen pada baris ketiga sudah sesuai.',
                        'feedback_wrong' => 'Belum tepat. Periksa kembali koefisien variabel z pada persamaan ketiga.',
                    ],
                    'aug34' => [
                        'accepted_answers' => ['4'],
                        'display_answer' => '4',
                        'feedback_correct' => 'Benar. Konstanta pada baris ketiga sudah sesuai.',
                        'feedback_wrong' => 'Belum tepat. Periksa nilai konstanta pada ruas kanan persamaan ketiga.',
                    ],
                ],
            ],

            'cek-pemahaman-1-4-terjemahan-matriks' => [
                'title' => 'Cek Pemahaman 1.4 - Terjemahan Augmented Matrix',
                'type' => 'cek_pemahaman',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['terjemah_baris1', 'terjemah_baris2', 'terjemah_baris3'],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'terjemah_baris1' => [
                        'accepted_answers' => ['x-2y=8', '1x-2y+0z=8', 'x-2y+0z=8'],
                        'display_answer' => 'x - 2y = 8',
                        'feedback_correct' => 'Benar. Persamaan pada baris pertama sudah sesuai dengan elemen matriks.',
                        'feedback_wrong' => 'Belum tepat. Cocokkan kembali setiap elemen pada baris pertama dengan urutan koefisien x, y, z, lalu konstanta di sebelah kanan garis vertikal.',
                    ],
                    'terjemah_baris2' => [
                        'accepted_answers' => ['3y+z=4', '0x+3y+z=4', '0x+3y+1z=4'],
                        'display_answer' => '3y + z = 4',
                        'feedback_correct' => 'Benar. Persamaan pada baris kedua sudah sesuai dengan elemen matriks.',
                        'feedback_wrong' => 'Belum tepat. Periksa kembali urutan koefisien x, y, dan z pada baris kedua. Elemen terakhir setelah garis vertikal merupakan konstanta.',
                    ],
                    'terjemah_baris3' => [
                        'accepted_answers' => ['2x-5z=-1', '2x+0y-5z=-1'],
                        'display_answer' => '2x - 5z = -1',
                        'feedback_correct' => 'Benar. Persamaan pada baris ketiga sudah sesuai dengan elemen matriks.',
                        'feedback_wrong' => 'Belum tepat. Periksa posisi setiap koefisien pada baris ketiga, terutama apabila ada variabel yang tidak memiliki nilai koefisien tertulis.',
                    ],
                ],
            ],

            'aktivitas-1-4-matriks' => [
                'title' => 'Aktivitas 1.4 Pemodelan Matriks pada Kasus Komputasi Dunia Nyata',
                'type' => 'aktivitas',
                'max_score' => 100,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => [
                            'game_a11', 'game_a12', 'game_a13',
                            'game_a21', 'game_a22', 'game_a23',
                            'game_a31', 'game_a32', 'game_a33',
                            'game_b1', 'game_b2', 'game_b3',
                        ],
                        'points' => 34,
                    ],
                    'q2' => [
                        'number' => 2,
                        'fields' => [
                            'cloud_a11', 'cloud_a12', 'cloud_a13', 'cloud_b1',
                            'cloud_a21', 'cloud_a22', 'cloud_a23', 'cloud_b2',
                            'cloud_a31', 'cloud_a32', 'cloud_a33', 'cloud_b3',
                        ],
                        'points' => 33,
                    ],
                    'q3' => [
                        'number' => 3,
                        'fields' => [
                            'debug_pernyataan',
                            'debug_a11', 'debug_a12', 'debug_a13', 'debug_b1',
                            'debug_a21', 'debug_a22', 'debug_a23', 'debug_b2',
                            'debug_a31', 'debug_a32', 'debug_a33', 'debug_b3',
                        ],
                        'points' => 33,
                    ],
                ],
                'questions' => [
                    'game_a11' => ['accepted_answers' => ['3'], 'display_answer' => '3', 'feedback_correct' => 'Benar. Satu Pedang membutuhkan 3 Besi.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan Besi untuk Pedang.'],
                    'game_a12' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar. Satu Pedang membutuhkan 1 Perak.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan Perak untuk Pedang.'],
                    'game_a13' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar. Satu Pedang tidak membutuhkan Emas.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan Emas untuk Pedang.'],
                    'game_a21' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar. Satu Perisai membutuhkan 2 Besi.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan Besi untuk Perisai.'],
                    'game_a22' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar. Satu Perisai membutuhkan 2 Perak.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan Perak untuk Perisai.'],
                    'game_a23' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar. Satu Perisai membutuhkan 1 Emas.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan Emas untuk Perisai.'],
                    'game_a31' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar. Satu Zirah tidak membutuhkan Besi.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan Besi untuk Zirah.'],
                    'game_a32' => ['accepted_answers' => ['4'], 'display_answer' => '4', 'feedback_correct' => 'Benar. Satu Zirah membutuhkan 4 Perak.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan Perak untuk Zirah.'],
                    'game_a33' => ['accepted_answers' => ['3'], 'display_answer' => '3', 'feedback_correct' => 'Benar. Satu Zirah membutuhkan 3 Emas.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan Emas untuk Zirah.'],
                    'game_b1' => ['accepted_answers' => ['50'], 'display_answer' => '50', 'feedback_correct' => 'Benar. Harga jual Pedang adalah 50 koin.', 'feedback_wrong' => 'Belum tepat. Lihat harga jual Pedang.'],
                    'game_b2' => ['accepted_answers' => ['80'], 'display_answer' => '80', 'feedback_correct' => 'Benar. Harga jual Perisai adalah 80 koin.', 'feedback_wrong' => 'Belum tepat. Lihat harga jual Perisai.'],
                    'game_b3' => ['accepted_answers' => ['120'], 'display_answer' => '120', 'feedback_correct' => 'Benar. Harga jual Zirah adalah 120 koin.', 'feedback_wrong' => 'Belum tepat. Lihat harga jual Zirah.'],

                    'cloud_a11' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar. Basic memakai 2 core CPU.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan CPU server Basic.'],
                    'cloud_a12' => ['accepted_answers' => ['4'], 'display_answer' => '4', 'feedback_correct' => 'Benar. Pro memakai 4 core CPU.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan CPU server Pro.'],
                    'cloud_a13' => ['accepted_answers' => ['8'], 'display_answer' => '8', 'feedback_correct' => 'Benar. Enterprise memakai 8 core CPU.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan CPU server Enterprise.'],
                    'cloud_b1' => ['accepted_answers' => ['64'], 'display_answer' => '64', 'feedback_correct' => 'Benar. Total CPU yang disewa adalah 64 core.', 'feedback_wrong' => 'Belum tepat. Lihat total CPU yang disebutkan.'],
                    'cloud_a21' => ['accepted_answers' => ['4'], 'display_answer' => '4', 'feedback_correct' => 'Benar. Basic memakai 4 GB RAM.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan RAM server Basic.'],
                    'cloud_a22' => ['accepted_answers' => ['16'], 'display_answer' => '16', 'feedback_correct' => 'Benar. Pro memakai 16 GB RAM.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan RAM server Pro.'],
                    'cloud_a23' => ['accepted_answers' => ['32'], 'display_answer' => '32', 'feedback_correct' => 'Benar. Enterprise memakai 32 GB RAM.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan RAM server Enterprise.'],
                    'cloud_b2' => ['accepted_answers' => ['200'], 'display_answer' => '200', 'feedback_correct' => 'Benar. Total RAM yang disewa adalah 200 GB.', 'feedback_wrong' => 'Belum tepat. Lihat total RAM yang disebutkan.'],
                    'cloud_a31' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar. Basic memakai 1 TB Storage.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan Storage server Basic.'],
                    'cloud_a32' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar. Pro memakai 1 TB Storage.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan Storage server Pro.'],
                    'cloud_a33' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar. Enterprise memakai 2 TB Storage.', 'feedback_wrong' => 'Belum tepat. Lihat kebutuhan Storage server Enterprise.'],
                    'cloud_b3' => ['accepted_answers' => ['20'], 'display_answer' => '20', 'feedback_correct' => 'Benar. Total Storage yang disewa adalah 20 TB.', 'feedback_wrong' => 'Belum tepat. Lihat total Storage yang disebutkan.'],

                    'debug_pernyataan' => [
                        'input_type' => 'checkbox',
                        'accepted_answers' => ['debug_a', 'debug_c'],
                        'feedback_correct' => 'Benar. Kesalahan terjadi pada Baris 1 dan Baris 3. Baris 2 sudah memuat koefisien yang sesuai.',
                        'feedback_wrong' => 'Belum tepat. Bandingkan setiap koefisien x, y, z, dan konstanta pada SPL asli dengan matriks input Programmer Junior.',
                    ],
                    'debug_a11' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar. Koefisien x pada Baris 1 adalah 1.', 'feedback_wrong' => 'Belum tepat. Variabel x tanpa angka memiliki koefisien 1.'],
                    'debug_a12' => ['accepted_answers' => ['-3'], 'display_answer' => '-3', 'feedback_correct' => 'Benar. Koefisien y pada Baris 1 adalah -3.', 'feedback_wrong' => 'Belum tepat. Perhatikan tanda negatif di depan 3y.'],
                    'debug_a13' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar. Variabel z tidak muncul pada Baris 1, sehingga koefisiennya 0.', 'feedback_wrong' => 'Belum tepat. Variabel z tidak muncul pada Baris 1.'],
                    'debug_b1' => ['accepted_answers' => ['5'], 'display_answer' => '5', 'feedback_correct' => 'Benar. Konstanta pada Baris 1 adalah 5.', 'feedback_wrong' => 'Belum tepat. Lihat ruas kanan Baris 1.'],
                    'debug_a21' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar. Koefisien x pada Baris 2 adalah 2.', 'feedback_wrong' => 'Belum tepat. Lihat koefisien x pada Baris 2.'],
                    'debug_a22' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar. Variabel y tanpa angka memiliki koefisien 1.', 'feedback_wrong' => 'Belum tepat. Variabel y tanpa angka memiliki koefisien 1.'],
                    'debug_a23' => ['accepted_answers' => ['4'], 'display_answer' => '4', 'feedback_correct' => 'Benar. Koefisien z pada Baris 2 adalah 4.', 'feedback_wrong' => 'Belum tepat. Lihat koefisien z pada Baris 2.'],
                    'debug_b2' => ['accepted_answers' => ['10'], 'display_answer' => '10', 'feedback_correct' => 'Benar. Konstanta pada Baris 2 adalah 10.', 'feedback_wrong' => 'Belum tepat. Lihat ruas kanan Baris 2.'],
                    'debug_a31' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar. Variabel x tidak muncul pada Baris 3, sehingga koefisiennya 0.', 'feedback_wrong' => 'Belum tepat. Variabel x tidak muncul pada Baris 3.'],
                    'debug_a32' => ['accepted_answers' => ['-1'], 'display_answer' => '-1', 'feedback_correct' => 'Benar. Koefisien y pada Baris 3 adalah -1.', 'feedback_wrong' => 'Belum tepat. Perhatikan tanda negatif di depan y.'],
                    'debug_a33' => ['accepted_answers' => ['5'], 'display_answer' => '5', 'feedback_correct' => 'Benar. Koefisien z pada Baris 3 adalah 5.', 'feedback_wrong' => 'Belum tepat. Lihat koefisien z pada Baris 3.'],
                    'debug_b3' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar. Konstanta pada Baris 3 adalah 2.', 'feedback_wrong' => 'Belum tepat. Lihat ruas kanan Baris 3.'],
                ],
            ],


            'contoh-simulasi-2-2-pertukaran' => [
                'title' => 'Contoh Simulasi 2.2.1 - Pertukaran Dua Baris',
                'type' => 'contoh_simulasi',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => [
                            'baris_target',
                            'baris_pengganti',
                            'notasi',
                            'hasil_11', 'hasil_12', 'hasil_13', 'hasil_14',
                            'hasil_21', 'hasil_22', 'hasil_23', 'hasil_24',
                            'hasil_31', 'hasil_32', 'hasil_33', 'hasil_34',
                        ],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'baris_target' => [
                        'accepted_answers' => ['1', 'baris 1', 'baris ke-1'],
                        'display_answer' => '1',
                        'feedback_correct' => 'Benar. Baris pertama yang diawali 0 menjadi baris yang diturunkan.',
                        'feedback_wrong' => 'Belum tepat. Elemen pembuka bernilai 0 berada pada Baris-1.',
                    ],
                    'baris_pengganti' => [
                        'accepted_answers' => ['2', 'baris 2', 'baris ke-2'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Baris ke-2 diawali oleh angka 1.',
                        'feedback_wrong' => 'Belum tepat. Pilih baris di bawah Baris-1 yang memiliki elemen pertama bernilai 1.',
                    ],
                    'notasi' => [
                        'accepted_answers' => ['b1↔b2', 'b1<->b2', 'b1<=>b2', 'b_1↔b_2', 'b_1<->b_2'],
                        'display_answer' => 'B1 ↔ B2',
                        'feedback_correct' => 'Benar. Pertukaran dilakukan antara Baris-1 dan Baris-2.',
                        'feedback_wrong' => 'Belum tepat. Gunakan notasi pertukaran dua baris, bukan operasi perkalian atau penjumlahan.',
                    ],
                    'hasil_11' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Perhatikan Baris-2 awal yang berpindah ke Baris-1.'],
                    'hasil_12' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Perhatikan Baris-2 awal yang berpindah ke Baris-1.'],
                    'hasil_13' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Perhatikan Baris-2 awal yang berpindah ke Baris-1.'],
                    'hasil_14' => ['accepted_answers' => ['6'], 'display_answer' => '6', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Perhatikan Baris-2 awal yang berpindah ke Baris-1.'],
                    'hasil_21' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Perhatikan Baris-1 awal yang berpindah ke Baris-2.'],
                    'hasil_22' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Perhatikan Baris-1 awal yang berpindah ke Baris-2.'],
                    'hasil_23' => ['accepted_answers' => ['3'], 'display_answer' => '3', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Perhatikan Baris-1 awal yang berpindah ke Baris-2.'],
                    'hasil_24' => ['accepted_answers' => ['9'], 'display_answer' => '9', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Perhatikan Baris-1 awal yang berpindah ke Baris-2.'],
                    'hasil_31' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-3 tidak berubah.'],
                    'hasil_32' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-3 tidak berubah.'],
                    'hasil_33' => ['accepted_answers' => ['-1'], 'display_answer' => '-1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-3 tidak berubah.'],
                    'hasil_34' => ['accepted_answers' => ['3'], 'display_answer' => '3', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-3 tidak berubah.'],
                ],
            ],

            'contoh-simulasi-2-2-perkalian-a' => [
                'title' => 'Contoh Simulasi 2.2.2 - Perkalian Baris dengan Konstanta Tak Nol',
                'type' => 'contoh_simulasi',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['baris_target', 'konstanta', 'notasi', 'hasil_21', 'hasil_22', 'hasil_23'],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'baris_target' => [
                        'accepted_answers' => ['2', 'baris 2', 'baris ke-2'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Targetnya adalah Baris-2.',
                        'feedback_wrong' => 'Belum tepat. Elemen pembuka yang ingin diubah berada pada Baris-2 Kolom-2.',
                    ],
                    'konstanta' => [
                        'accepted_answers' => ['1/2', '1 per 2', '0.5'],
                        'display_answer' => '1/2',
                        'feedback_correct' => 'Benar. Bilangan 2 dikalikan 1/2 menghasilkan 1.',
                        'feedback_wrong' => 'Belum tepat. Gunakan kebalikan perkalian dari 2.',
                    ],
                    'notasi' => [
                        'accepted_answers' => ['b2←1/2b2', 'b2<-1/2b2', 'b_2←1/2b_2', 'b_2<-1/2b_2'],
                        'display_answer' => 'B2 ← 1/2 B2',
                        'feedback_correct' => 'Benar. Seluruh Baris-2 dikalikan dengan 1/2.',
                        'feedback_wrong' => 'Belum tepat. Baris target harus tetap Baris-2 dengan pengali 1/2.',
                    ],
                    'hasil_21' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '0 dikalikan 1/2 tetap 0.'],
                    'hasil_22' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '2 dikalikan 1/2 bernilai 1.'],
                    'hasil_23' => ['accepted_answers' => ['4'], 'display_answer' => '4', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '8 dikalikan 1/2 bernilai 4.'],
                ],
            ],

            'contoh-simulasi-2-2-perkalian-b' => [
                'title' => 'Contoh Simulasi 2.2.3 - Perkalian Baris dengan Pecahan',
                'type' => 'contoh_simulasi',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['baris_target', 'konstanta', 'notasi', 'hasil_21', 'hasil_22', 'hasil_23'],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'baris_target' => [
                        'accepted_answers' => ['2', 'baris 2', 'baris ke-2'],
                        'display_answer' => '2',
                        'feedback_correct' => 'Benar. Targetnya adalah Baris-2.',
                        'feedback_wrong' => 'Belum tepat. Elemen 2/3 berada pada Baris-2 Kolom-2.',
                    ],
                    'konstanta' => [
                        'accepted_answers' => ['3/2', '3 per 2', '1.5'],
                        'display_answer' => '3/2',
                        'feedback_correct' => 'Benar. Kebalikan perkalian dari 2/3 adalah 3/2.',
                        'feedback_wrong' => 'Belum tepat. Gunakan kebalikan perkalian dari 2/3.',
                    ],
                    'notasi' => [
                        'accepted_answers' => ['b2←3/2b2', 'b2<-3/2b2', 'b_2←3/2b_2', 'b_2<-3/2b_2'],
                        'display_answer' => 'B2 ← 3/2 B2',
                        'feedback_correct' => 'Benar. Seluruh Baris-2 dikalikan dengan 3/2.',
                        'feedback_wrong' => 'Belum tepat. Baris target tetap Baris-2 dengan pengali 3/2.',
                    ],
                    'hasil_21' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '0 dikalikan 3/2 tetap 0.'],
                    'hasil_22' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '2/3 dikalikan 3/2 bernilai 1.'],
                    'hasil_23' => ['accepted_answers' => ['6'], 'display_answer' => '6', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '4 dikalikan 3/2 bernilai 6.'],
                ],
            ],

            'contoh-simulasi-2-2-penjumlahan-a' => [
                'title' => 'Contoh Simulasi 2.2.4 - Penjumlahan Kelipatan Baris',
                'type' => 'contoh_simulasi',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['baris_target', 'baris_acuan', 'konstanta', 'notasi', 'hasil_31', 'hasil_32', 'hasil_33', 'hasil_34'],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'baris_target' => ['accepted_answers' => ['3', 'baris 3', 'baris ke-3'], 'display_answer' => '3', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Elemen 2 yang akan dinolkan berada pada Baris-3 Kolom-1.'],
                    'baris_acuan' => ['accepted_answers' => ['1', 'baris 1', 'baris ke-1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Gunakan Baris-1 yang memiliki pivot 1 pada kolom pertama.'],
                    'konstanta' => ['accepted_answers' => ['-2'], 'display_answer' => '-2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Gunakan lawan dari angka target 2, yaitu -2.'],
                    'notasi' => [
                        'accepted_answers' => ['b3←-2b1+b3', 'b3<- -2b1+b3', 'b3<- -2b1+b3', 'b_3←-2b_1+b_3', 'b_3<- -2b_1+b_3'],
                        'display_answer' => 'B3 ← -2B1 + B3',
                        'feedback_correct' => 'Benar.',
                        'feedback_wrong' => 'Gunakan Baris-1 sebagai acuan untuk mengenolkan elemen pertama Baris-3.',
                    ],
                    'hasil_31' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-2(1) + 2 bernilai 0.'],
                    'hasil_32' => ['accepted_answers' => ['-1'], 'display_answer' => '-1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-2(1) + 1 bernilai -1.'],
                    'hasil_33' => ['accepted_answers' => ['-3'], 'display_answer' => '-3', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-2(1) + (-1) bernilai -3.'],
                    'hasil_34' => ['accepted_answers' => ['-9'], 'display_answer' => '-9', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-2(6) + 3 bernilai -9.'],
                ],
            ],

            'contoh-simulasi-2-2-penjumlahan-b' => [
                'title' => 'Contoh Simulasi 2.2.5 - Penjumlahan Kelipatan dengan Pecahan',
                'type' => 'contoh_simulasi',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => ['baris_target', 'baris_acuan', 'konstanta', 'notasi', 'hasil_21', 'hasil_22', 'hasil_23', 'hasil_24'],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'baris_target' => ['accepted_answers' => ['2', 'baris 2', 'baris ke-2'], 'display_answer' => '2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Elemen 1/2 yang akan dinolkan berada pada Baris-2 Kolom-1.'],
                    'baris_acuan' => ['accepted_answers' => ['1', 'baris 1', 'baris ke-1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Gunakan Baris-1 sebagai acuan karena memiliki pivot 1 pada kolom pertama.'],
                    'konstanta' => ['accepted_answers' => ['-1/2', '-1 per 2', '-0.5'], 'display_answer' => '-1/2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Gunakan lawan dari 1/2, yaitu -1/2.'],
                    'notasi' => [
                        'accepted_answers' => ['b2←-1/2b1+b2', 'b2<- -1/2b1+b2', 'b_2←-1/2b_1+b_2', 'b_2<- -1/2b_1+b_2'],
                        'display_answer' => 'B2 ← -1/2 B1 + B2',
                        'feedback_correct' => 'Benar.',
                        'feedback_wrong' => 'Gunakan Baris-1 sebagai acuan dan -1/2 sebagai pengali.',
                    ],
                    'hasil_21' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-1/2(1) + 1/2 bernilai 0.'],
                    'hasil_22' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-1/2(2) + 3 bernilai 2.'],
                    'hasil_23' => ['accepted_answers' => ['3/2', '1.5', '3 per 2'], 'display_answer' => '3/2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-1/2(-1) + 1 bernilai 3/2.'],
                    'hasil_24' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-1/2(4) + 4 bernilai 2.'],
                ],
            ],

            'aktivitas-2-1-obe' => [
                'title' => 'Aktivitas 2.1 - Latihan Mandiri Operasi Baris Elementer',
                'type' => 'aktivitas',
                'max_score' => 100,
                'groups' => [
                    'kasus_1' => [
                        'number' => 1,
                        'fields' => [
                            'k1_i', 'k1_j', 'k1_notasi',
                            'k1_11', 'k1_12', 'k1_13', 'k1_14',
                            'k1_21', 'k1_22', 'k1_23', 'k1_24',
                            'k1_31', 'k1_32', 'k1_33', 'k1_34',
                        ],
                        'points' => 20,
                    ],
                    'kasus_2a' => [
                        'number' => 2,
                        'fields' => ['k2a_i', 'k2a_k', 'k2a_notasi', 'k2a_21', 'k2a_22', 'k2a_23', 'k2a_24'],
                        'points' => 20,
                    ],
                    'kasus_2b' => [
                        'number' => 3,
                        'fields' => ['k2b_i', 'k2b_k', 'k2b_notasi', 'k2b_21', 'k2b_22', 'k2b_23', 'k2b_24'],
                        'points' => 20,
                    ],
                    'kasus_3a' => [
                        'number' => 4,
                        'fields' => ['k3a_i', 'k3a_j', 'k3a_k', 'k3a_notasi', 'k3a_21', 'k3a_22', 'k3a_23'],
                        'points' => 20,
                    ],
                    'kasus_3b' => [
                        'number' => 5,
                        'fields' => ['k3b_i', 'k3b_j', 'k3b_k', 'k3b_notasi', 'k3b_21', 'k3b_22', 'k3b_23'],
                        'points' => 20,
                    ],
                ],
                'questions' => [
                    'k1_i' => ['accepted_answers' => ['1', 'baris 1', 'baris ke-1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris yang diawali 0 adalah Baris-1.'],
                    'k1_j' => ['accepted_answers' => ['3', 'baris 3', 'baris ke-3'], 'display_answer' => '3', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris di bawah yang diawali 1 adalah Baris-3.'],
                    'k1_notasi' => ['accepted_answers' => ['b1↔b3', 'b1<->b3', 'b_1↔b_3', 'b_1<->b_3'], 'display_answer' => 'B1 ↔ B3', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Tukar Baris-1 dengan Baris-3.'],
                    'k1_11' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-1 baru berasal dari Baris-3 awal.'],
                    'k1_12' => ['accepted_answers' => ['4'], 'display_answer' => '4', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-1 baru berasal dari Baris-3 awal.'],
                    'k1_13' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-1 baru berasal dari Baris-3 awal.'],
                    'k1_14' => ['accepted_answers' => ['7'], 'display_answer' => '7', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-1 baru berasal dari Baris-3 awal.'],
                    'k1_21' => ['accepted_answers' => ['3'], 'display_answer' => '3', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-2 tidak berubah.'],
                    'k1_22' => ['accepted_answers' => ['-1'], 'display_answer' => '-1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-2 tidak berubah.'],
                    'k1_23' => ['accepted_answers' => ['5'], 'display_answer' => '5', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-2 tidak berubah.'],
                    'k1_24' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-2 tidak berubah.'],
                    'k1_31' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-3 baru berasal dari Baris-1 awal.'],
                    'k1_32' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-3 baru berasal dari Baris-1 awal.'],
                    'k1_33' => ['accepted_answers' => ['-1'], 'display_answer' => '-1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-3 baru berasal dari Baris-1 awal.'],
                    'k1_34' => ['accepted_answers' => ['4'], 'display_answer' => '4', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Baris-3 baru berasal dari Baris-1 awal.'],

                    'k2a_i' => ['accepted_answers' => ['2', 'baris 2', 'baris ke-2'], 'display_answer' => '2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Target berada pada Baris-2.'],
                    'k2a_k' => ['accepted_answers' => ['-1/3', '-1 per 3', '-0.3333333333'], 'display_answer' => '-1/3', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Kebalikan perkalian dari -3 adalah -1/3.'],
                    'k2a_notasi' => ['accepted_answers' => ['b2←-1/3b2', 'b2<- -1/3b2', 'b_2←-1/3b_2', 'b_2<- -1/3b_2'], 'display_answer' => 'B2 ← -1/3 B2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Kalikan Baris-2 dengan -1/3.'],
                    'k2a_21' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '0 dikalikan -1/3 tetap 0.'],
                    'k2a_22' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-3 dikalikan -1/3 bernilai 1.'],
                    'k2a_23' => ['accepted_answers' => ['-2'], 'display_answer' => '-2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '6 dikalikan -1/3 bernilai -2.'],
                    'k2a_24' => ['accepted_answers' => ['3'], 'display_answer' => '3', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-9 dikalikan -1/3 bernilai 3.'],

                    'k2b_i' => ['accepted_answers' => ['2', 'baris 2', 'baris ke-2'], 'display_answer' => '2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Target berada pada Baris-2.'],
                    'k2b_k' => ['accepted_answers' => ['-4/3', '-4 per 3', '-1.3333333333'], 'display_answer' => '-4/3', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Kebalikan perkalian dari -3/4 adalah -4/3.'],
                    'k2b_notasi' => ['accepted_answers' => ['b2←-4/3b2', 'b2<- -4/3b2', 'b_2←-4/3b_2', 'b_2<- -4/3b_2'], 'display_answer' => 'B2 ← -4/3 B2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Kalikan Baris-2 dengan -4/3.'],
                    'k2b_21' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '0 dikalikan -4/3 tetap 0.'],
                    'k2b_22' => ['accepted_answers' => ['1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-3/4 dikalikan -4/3 bernilai 1.'],
                    'k2b_23' => ['accepted_answers' => ['-4'], 'display_answer' => '-4', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '3 dikalikan -4/3 bernilai -4.'],
                    'k2b_24' => ['accepted_answers' => ['8'], 'display_answer' => '8', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-6 dikalikan -4/3 bernilai 8.'],

                    'k3a_i' => ['accepted_answers' => ['2', 'baris 2', 'baris ke-2'], 'display_answer' => '2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Elemen 3 yang ingin dinolkan berada pada Baris-2.'],
                    'k3a_j' => ['accepted_answers' => ['1', 'baris 1', 'baris ke-1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Gunakan Baris-1 yang memiliki elemen awal 1.'],
                    'k3a_k' => ['accepted_answers' => ['-3'], 'display_answer' => '-3', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Lawan dari 3 adalah -3.'],
                    'k3a_notasi' => ['accepted_answers' => ['b2←-3b1+b2', 'b2<- -3b1+b2', 'b_2←-3b_1+b_2', 'b_2<- -3b_1+b_2'], 'display_answer' => 'B2 ← -3B1 + B2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Gunakan -3B1 lalu tambahkan ke B2.'],
                    'k3a_21' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-3(1) + 3 bernilai 0.'],
                    'k3a_22' => ['accepted_answers' => ['-14'], 'display_answer' => '-14', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-3(4) + (-2) bernilai -14.'],
                    'k3a_23' => ['accepted_answers' => ['-16'], 'display_answer' => '-16', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '-3(7) + 5 bernilai -16.'],

                    'k3b_i' => ['accepted_answers' => ['2', 'baris 2', 'baris ke-2'], 'display_answer' => '2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Elemen -2/3 yang ingin dinolkan berada pada Baris-2.'],
                    'k3b_j' => ['accepted_answers' => ['1', 'baris 1', 'baris ke-1'], 'display_answer' => '1', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Gunakan Baris-1 yang memiliki elemen awal 1.'],
                    'k3b_k' => ['accepted_answers' => ['2/3', '2 per 3', '0.6666666667'], 'display_answer' => '2/3', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Lawan dari -2/3 adalah 2/3.'],
                    'k3b_notasi' => ['accepted_answers' => ['b2←2/3b1+b2', 'b2<-2/3b1+b2', 'b_2←2/3b_1+b_2', 'b_2<-2/3b_1+b_2'], 'display_answer' => 'B2 ← 2/3 B1 + B2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => 'Gunakan 2/3 B1 lalu tambahkan ke B2.'],
                    'k3b_21' => ['accepted_answers' => ['0'], 'display_answer' => '0', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '2/3(1) + (-2/3) bernilai 0.'],
                    'k3b_22' => ['accepted_answers' => ['9'], 'display_answer' => '9', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '2/3(6) + 5 bernilai 9.'],
                    'k3b_23' => ['accepted_answers' => ['2'], 'display_answer' => '2', 'feedback_correct' => 'Benar.', 'feedback_wrong' => '2/3(-3) + 4 bernilai 2.'],
                ],
            ],            default => null,
        };
    }

    private function normalize(mixed $value): string
    {
        $value = strtolower(trim((string) $value));

        /*
        | MathLive dapat mengirim beberapa variasi pecahan:
        | \frac{1}{2}, \frac12, \frac{1}2, dan \frac1{2}.
        | Semua bentuk tersebut diseragamkan menjadi 1/2.
        */
        $fractionCommand = '(?:dfrac|tfrac|frac)';

        for ($iteration = 0; $iteration < 5; $iteration++) {
            $before = $value;

            $value = preg_replace_callback(
                '/\\\\(?:mathrm|mathit|operatorname|text)\s*\{([^{}]*)\}/u',
                static fn (array $matches): string => $matches[1],
                $value
            ) ?? $value;

            $value = preg_replace_callback(
                '/\\\\' . $fractionCommand . '\s*\{([^{}]*)\}\s*\{([^{}]*)\}/u',
                static fn (array $matches): string => trim($matches[1]) . '/' . trim($matches[2]),
                $value
            ) ?? $value;

            $value = preg_replace_callback(
                '/\\\\' . $fractionCommand . '\s*\{([^{}]*)\}\s*([+-]?\d+)/u',
                static fn (array $matches): string => trim($matches[1]) . '/' . trim($matches[2]),
                $value
            ) ?? $value;

            $value = preg_replace_callback(
                '/\\\\' . $fractionCommand . '\s*([+-]?\d+)\s*\{([^{}]*)\}/u',
                static fn (array $matches): string => trim($matches[1]) . '/' . trim($matches[2]),
                $value
            ) ?? $value;

            $value = preg_replace_callback(
                '/\\\\' . $fractionCommand . '\s*([+-]?\d)\s*([+-]?\d)(?!\d)/u',
                static fn (array $matches): string => $matches[1] . '/' . $matches[2],
                $value
            ) ?? $value;

            if ($value === $before) {
                break;
            }
        }

        $value = strtr($value, [
            '½' => '1/2',
            '⅓' => '1/3',
            '⅔' => '2/3',
            '¼' => '1/4',
            '¾' => '3/4',
            '⅕' => '1/5',
            '⅖' => '2/5',
            '⅗' => '3/5',
            '⅘' => '4/5',
        ]);

        $value = str_replace(
            [
                '\\longleftarrow', '\\leftarrow', '\\gets', '<-',
                '\\rightarrow', '→',
                '\\left', '\\right', '\\bigl', '\\bigr', '\\Bigl', '\\Bigr',
                '\\,', '\\;', '\\:', '\\!', '\\quad', '\\qquad', '~',
                '\\cdot', '\\times', '·', '×',
            ],
            [
                '←', '←', '←', '←',
                '→', '→',
                '', '', '', '', '', '',
                '', '', '', '', '', '', '',
                '', '', '', '',
            ],
            $value
        );

        $value = str_replace(['{', '}', '\\'], '', $value);
        $value = str_replace(["\u{00A0}", "\u{2009}", "\u{202F}"], '', $value);
        $value = str_replace(['√', '−', '–', '—'], ['sqrt', '-', '-', '-'], $value);
        $value = str_replace(['₁', '₂', '₃', '₄'], ['1', '2', '3', '4'], $value);
        $value = str_replace(['_', '*', '(', ')', '[', ']'], '', $value);
        $value = preg_replace('/\s+/u', '', $value) ?? $value;

        return $value;
    }

    private function normalizeMultiple(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->map(fn ($item) => $this->normalize($item))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    // SUBBAB_2_2_OBE_REVISION_V2
    private function getSubbab22PracticeDefinition(string $practiceKey): ?array
    {
        $question = static function (array $acceptedAnswers, string $displayAnswer, string $feedbackWrong): array {
            return [
                'accepted_answers' => $acceptedAnswers,
                'display_answer' => $displayAnswer,
                'feedback_correct' => 'Benar.',
                'feedback_wrong' => $feedbackWrong,
            ];
        };

        $valueAnswers = static function (string $value): array {
            return match ($value) {
                '1/2' => ['1/2', '1 per 2'],
                '-1/2' => ['-1/2', '-1 per 2'],
                '2/3' => ['2/3', '2 per 3'],
                '-2/3' => ['-2/3', '-2 per 3'],
                '-1/3' => ['-1/3', '-1 per 3'],
                '-3/4' => ['-3/4', '-3 per 4'],
                '-4/3' => ['-4/3', '-4 per 3'],
                '3/2' => ['3/2', '3 per 2'],
                default => [$value],
            };
        };

        $valueQuestions = static function (array $values, string $feedbackWrong) use ($question, $valueAnswers): array {
            $questions = [];

            foreach ($values as $field => $value) {
                $value = (string) $value;
                $questions[$field] = $question(
                    $valueAnswers($value),
                    $value,
                    $feedbackWrong,
                );
            }

            return $questions;
        };

        return match ($practiceKey) {
            'contoh-simulasi-2-2-pertukaran' => [
                'title' => 'Contoh Simulasi 2.2.1 - Pertukaran Dua Baris',
                'type' => 'contoh_simulasi',
                'definition_version' => 'subbab22_obe_revisi_v2',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => [
                            'baris_target', 'baris_pengganti',
                            'hasil_11', 'hasil_13', 'hasil_22', 'hasil_24',
                        ],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'baris_target' => $question(
                        ['1', 'baris 1', 'baris ke-1'],
                        '1',
                        'Elemen pembuka bernilai 0 berada pada Baris-1.'
                    ),
                    'baris_pengganti' => $question(
                        ['2', 'baris 2', 'baris ke-2'],
                        '2',
                        'Pilih baris di bawah Baris-1 yang memiliki elemen pertama bernilai 1.'
                    ),
                    ...$valueQuestions([
                        'hasil_11' => '1',
                        'hasil_13' => '1',
                        'hasil_22' => '1',
                        'hasil_24' => '9',
                    ], 'Perhatikan kembali posisi setiap baris setelah Baris-1 dan Baris-2 ditukar.'),
                ],
            ],

            'contoh-simulasi-2-2-perkalian-a' => [
                'title' => 'Contoh Simulasi 2.2.2 - Perkalian Baris dengan Konstanta Tak Nol',
                'type' => 'contoh_simulasi',
                'definition_version' => 'subbab22_obe_revisi_v2',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => [
                            'rincian_awal_21', 'rincian_awal_22', 'rincian_awal_23',
                            'rincian_hasil_21', 'rincian_hasil_22', 'rincian_hasil_23',
                            'hasil_21', 'hasil_22', 'hasil_23',
                        ],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    ...$valueQuestions([
                        'rincian_awal_21' => '0',
                        'rincian_awal_22' => '2',
                        'rincian_awal_23' => '8',
                        'rincian_hasil_21' => '0',
                        'rincian_hasil_22' => '1',
                        'rincian_hasil_23' => '4',
                        'hasil_21' => '0',
                        'hasil_22' => '1',
                        'hasil_23' => '4',
                    ], 'Kalikan setiap elemen pada Baris-2 dengan 1/2.'),
                ],
            ],

            'contoh-simulasi-2-2-perkalian-b' => [
                'title' => 'Contoh Simulasi 2.2.3 - Perkalian Baris dengan Pecahan',
                'type' => 'contoh_simulasi',
                'definition_version' => 'subbab22_obe_revisi_v2',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => [
                            'rincian_awal_21', 'rincian_awal_22', 'rincian_awal_23',
                            'rincian_hasil_21', 'rincian_hasil_22', 'rincian_hasil_23',
                            'hasil_21', 'hasil_22', 'hasil_23',
                        ],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    ...$valueQuestions([
                        'rincian_awal_21' => '0',
                        'rincian_awal_22' => '2/3',
                        'rincian_awal_23' => '4',
                        'rincian_hasil_21' => '0',
                        'rincian_hasil_22' => '1',
                        'rincian_hasil_23' => '6',
                        'hasil_21' => '0',
                        'hasil_22' => '1',
                        'hasil_23' => '6',
                    ], 'Kalikan setiap elemen pada Baris-2 dengan 3/2.'),
                ],
            ],

            'contoh-simulasi-2-2-penjumlahan-a' => [
                'title' => 'Contoh Simulasi 2.2.4 - Penjumlahan Kelipatan Baris',
                'type' => 'contoh_simulasi',
                'definition_version' => 'subbab22_obe_revisi_v2',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => [
                            'rincian_target_31', 'rincian_target_32', 'rincian_target_33', 'rincian_target_34',
                            'rincian_kali_31', 'rincian_kali_32', 'rincian_kali_33', 'rincian_kali_34',
                            'rincian_jumlah_target_31', 'rincian_jumlah_target_32', 'rincian_jumlah_target_33', 'rincian_jumlah_target_34',
                            'rincian_hasil_31', 'rincian_hasil_32', 'rincian_hasil_33', 'rincian_hasil_34',
                            'hasil_31', 'hasil_32', 'hasil_33', 'hasil_34',
                        ],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    ...$valueQuestions([
                        'rincian_target_31' => '2',
                        'rincian_target_32' => '1',
                        'rincian_target_33' => '-1',
                        'rincian_target_34' => '3',
                        'rincian_kali_31' => '-2',
                        'rincian_kali_32' => '-2',
                        'rincian_kali_33' => '-2',
                        'rincian_kali_34' => '-12',
                        'rincian_jumlah_target_31' => '2',
                        'rincian_jumlah_target_32' => '1',
                        'rincian_jumlah_target_33' => '-1',
                        'rincian_jumlah_target_34' => '3',
                        'rincian_hasil_31' => '0',
                        'rincian_hasil_32' => '-1',
                        'rincian_hasil_33' => '-3',
                        'rincian_hasil_34' => '-9',
                        'hasil_31' => '0',
                        'hasil_32' => '-1',
                        'hasil_33' => '-3',
                        'hasil_34' => '-9',
                    ], 'Gunakan hasil operasi -2B1 + B3 pada setiap elemen Baris-3.'),
                ],
            ],

            'contoh-simulasi-2-2-penjumlahan-b' => [
                'title' => 'Contoh Simulasi 2.2.5 - Penjumlahan Kelipatan dengan Pecahan',
                'type' => 'contoh_simulasi',
                'definition_version' => 'subbab22_obe_revisi_v2',
                'max_score' => 0,
                'groups' => [
                    'q1' => [
                        'number' => 1,
                        'fields' => [
                            'rincian_target_21', 'rincian_target_22', 'rincian_target_23', 'rincian_target_24',
                            'rincian_kali_21', 'rincian_kali_22', 'rincian_kali_23', 'rincian_kali_24',
                            'rincian_jumlah_target_21', 'rincian_jumlah_target_22', 'rincian_jumlah_target_23', 'rincian_jumlah_target_24',
                            'rincian_hasil_21', 'rincian_hasil_22', 'rincian_hasil_23', 'rincian_hasil_24',
                            'hasil_21', 'hasil_22', 'hasil_23', 'hasil_24',
                        ],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    ...$valueQuestions([
                        'rincian_target_21' => '1/2',
                        'rincian_target_22' => '3',
                        'rincian_target_23' => '1',
                        'rincian_target_24' => '4',
                        'rincian_kali_21' => '-1/2',
                        'rincian_kali_22' => '-1',
                        'rincian_kali_23' => '1/2',
                        'rincian_kali_24' => '-2',
                        'rincian_jumlah_target_21' => '1/2',
                        'rincian_jumlah_target_22' => '3',
                        'rincian_jumlah_target_23' => '1',
                        'rincian_jumlah_target_24' => '4',
                        'rincian_hasil_21' => '0',
                        'rincian_hasil_22' => '2',
                        'rincian_hasil_23' => '3/2',
                        'rincian_hasil_24' => '2',
                        'hasil_21' => '0',
                        'hasil_22' => '2',
                        'hasil_23' => '3/2',
                        'hasil_24' => '2',
                    ], 'Gunakan hasil operasi -1/2B1 + B2 pada setiap elemen Baris-2.'),
                ],
            ],

            'aktivitas-2-1-obe' => [
                'title' => 'Aktivitas 2.1 - Latihan Mandiri Operasi Baris Elementer',
                'type' => 'aktivitas',
                'definition_version' => 'subbab22_obe_revisi_v2',
                'max_score' => 100,
                'groups' => [
                    'k1' => [
                        'number' => 1,
                        'fields' => [
                            'k1_i', 'k1_j', 'k1_notasi',
                            'k1_11', 'k1_12', 'k1_13', 'k1_14',
                            'k1_21', 'k1_22', 'k1_23', 'k1_24',
                            'k1_31', 'k1_32', 'k1_33', 'k1_34',
                        ],
                        'points' => 20,
                    ],
                    'k2a' => [
                        'number' => 2,
                        'fields' => [
                            'k2a_i', 'k2a_k', 'k2a_notasi',
                            'k2a_rincian_awal_21', 'k2a_rincian_awal_22', 'k2a_rincian_awal_23', 'k2a_rincian_awal_24',
                            'k2a_rincian_hasil_21', 'k2a_rincian_hasil_22', 'k2a_rincian_hasil_23', 'k2a_rincian_hasil_24',
                            'k2a_11', 'k2a_12', 'k2a_13', 'k2a_14',
                            'k2a_21', 'k2a_22', 'k2a_23', 'k2a_24',
                            'k2a_31', 'k2a_32', 'k2a_33', 'k2a_34',
                        ],
                        'points' => 20,
                    ],
                    'k2b' => [
                        'number' => 3,
                        'fields' => [
                            'k2b_i', 'k2b_k', 'k2b_notasi',
                            'k2b_rincian_awal_21', 'k2b_rincian_awal_22', 'k2b_rincian_awal_23', 'k2b_rincian_awal_24',
                            'k2b_rincian_hasil_21', 'k2b_rincian_hasil_22', 'k2b_rincian_hasil_23', 'k2b_rincian_hasil_24',
                            'k2b_11', 'k2b_12', 'k2b_13', 'k2b_14',
                            'k2b_21', 'k2b_22', 'k2b_23', 'k2b_24',
                            'k2b_31', 'k2b_32', 'k2b_33', 'k2b_34',
                        ],
                        'points' => 20,
                    ],
                    'k3a' => [
                        'number' => 4,
                        'fields' => [
                            'k3a_i', 'k3a_j', 'k3a_k', 'k3a_notasi',
                            'k3a_rincian_acuan_11', 'k3a_rincian_acuan_12', 'k3a_rincian_acuan_13',
                            'k3a_rincian_target_21', 'k3a_rincian_target_22', 'k3a_rincian_target_23',
                            'k3a_rincian_kali_21', 'k3a_rincian_kali_22', 'k3a_rincian_kali_23',
                            'k3a_rincian_jumlah_target_21', 'k3a_rincian_jumlah_target_22', 'k3a_rincian_jumlah_target_23',
                            'k3a_rincian_hasil_21', 'k3a_rincian_hasil_22', 'k3a_rincian_hasil_23',
                            'k3a_11', 'k3a_12', 'k3a_13',
                            'k3a_21', 'k3a_22', 'k3a_23',
                        ],
                        'points' => 20,
                    ],
                    'k3b' => [
                        'number' => 5,
                        'fields' => [
                            'k3b_i', 'k3b_j', 'k3b_k', 'k3b_notasi',
                            'k3b_rincian_acuan_11', 'k3b_rincian_acuan_12', 'k3b_rincian_acuan_13',
                            'k3b_rincian_target_21', 'k3b_rincian_target_22', 'k3b_rincian_target_23',
                            'k3b_rincian_kali_21', 'k3b_rincian_kali_22', 'k3b_rincian_kali_23',
                            'k3b_rincian_jumlah_target_21', 'k3b_rincian_jumlah_target_22', 'k3b_rincian_jumlah_target_23',
                            'k3b_rincian_hasil_21', 'k3b_rincian_hasil_22', 'k3b_rincian_hasil_23',
                            'k3b_11', 'k3b_12', 'k3b_13',
                            'k3b_21', 'k3b_22', 'k3b_23',
                        ],
                        'points' => 20,
                    ],
                ],
                'questions' => [
                    'k1_i' => $question(['1', 'baris 1', 'baris ke-1'], '1', 'Baris yang diawali 0 adalah Baris-1.'),
                    'k1_j' => $question(['3', 'baris 3', 'baris ke-3'], '3', 'Baris di bawah yang diawali angka 1 adalah Baris-3.'),
                    'k1_notasi' => $question(
                        ['b1↔b3', 'b1<->b3', 'b1<=>b3', 'b_1↔b_3', 'b_1<->b_3'],
                        'B1 ↔ B3',
                        'Gunakan notasi pertukaran antara Baris-1 dan Baris-3.'
                    ),
                    ...$valueQuestions([
                        'k1_11' => '1', 'k1_12' => '4', 'k1_13' => '2', 'k1_14' => '7',
                        'k1_21' => '3', 'k1_22' => '-1', 'k1_23' => '5', 'k1_24' => '2',
                        'k1_31' => '0', 'k1_32' => '2', 'k1_33' => '-1', 'k1_34' => '4',
                    ], 'Tukar seluruh isi Baris-1 dengan Baris-3. Baris-2 tidak berubah.'),

                    'k2a_i' => $question(['2', 'baris 2', 'baris ke-2'], '2', 'Elemen target berada pada Baris-2.'),
                    'k2a_k' => $question(['-1/3', '-1 per 3'], '-1/3', 'Kebalikan perkalian dari -3 adalah -1/3.'),
                    'k2a_notasi' => $question(
                        ['b2←-1/3b2', 'b2<--1/3b2', 'b_2←-1/3b_2', 'b_2<--1/3b_2'],
                        'B2 ← -1/3 B2',
                        'Kalikan seluruh Baris-2 dengan -1/3.'
                    ),
                    ...$valueQuestions([
                        'k2a_rincian_awal_21' => '0', 'k2a_rincian_awal_22' => '-3', 'k2a_rincian_awal_23' => '6', 'k2a_rincian_awal_24' => '-9',
                        'k2a_rincian_hasil_21' => '0', 'k2a_rincian_hasil_22' => '1', 'k2a_rincian_hasil_23' => '-2', 'k2a_rincian_hasil_24' => '3',
                        'k2a_11' => '1', 'k2a_12' => '3', 'k2a_13' => '-4', 'k2a_14' => '7',
                        'k2a_21' => '0', 'k2a_22' => '1', 'k2a_23' => '-2', 'k2a_24' => '3',
                        'k2a_31' => '2', 'k2a_32' => '1', 'k2a_33' => '5', 'k2a_34' => '4',
                    ], 'Kalikan seluruh elemen Baris-2 dengan -1/3.'),

                    'k2b_i' => $question(['2', 'baris 2', 'baris ke-2'], '2', 'Elemen target berada pada Baris-2.'),
                    'k2b_k' => $question(['-4/3', '-4 per 3'], '-4/3', 'Kebalikan perkalian dari -3/4 adalah -4/3.'),
                    'k2b_notasi' => $question(
                        ['b2←-4/3b2', 'b2<--4/3b2', 'b_2←-4/3b_2', 'b_2<--4/3b_2'],
                        'B2 ← -4/3 B2',
                        'Kalikan seluruh Baris-2 dengan -4/3.'
                    ),
                    ...$valueQuestions([
                        'k2b_rincian_awal_21' => '0', 'k2b_rincian_awal_22' => '-3/4', 'k2b_rincian_awal_23' => '3', 'k2b_rincian_awal_24' => '-6',
                        'k2b_rincian_hasil_21' => '0', 'k2b_rincian_hasil_22' => '1', 'k2b_rincian_hasil_23' => '-4', 'k2b_rincian_hasil_24' => '8',
                        'k2b_11' => '1', 'k2b_12' => '2', 'k2b_13' => '-1', 'k2b_14' => '5',
                        'k2b_21' => '0', 'k2b_22' => '1', 'k2b_23' => '-4', 'k2b_24' => '8',
                        'k2b_31' => '-2', 'k2b_32' => '1', 'k2b_33' => '4', 'k2b_34' => '8',
                    ], 'Kalikan seluruh elemen Baris-2 dengan -4/3.'),

                    'k3a_i' => $question(['2', 'baris 2', 'baris ke-2'], '2', 'Elemen 3 yang ingin dinolkan berada pada Baris-2.'),
                    'k3a_j' => $question(['1', 'baris 1', 'baris ke-1'], '1', 'Gunakan Baris-1 sebagai baris acuan.'),
                    'k3a_k' => $question(['-3'], '-3', 'Lawan dari 3 adalah -3.'),
                    'k3a_notasi' => $question(
                        ['b2←-3b1+b2', 'b2<--3b1+b2', 'b_2←-3b_1+b_2', 'b_2<--3b_1+b_2'],
                        'B2 ← -3B1 + B2',
                        'Gunakan -3B1 lalu tambahkan ke B2.'
                    ),
                    ...$valueQuestions([
                        'k3a_rincian_acuan_11' => '1', 'k3a_rincian_acuan_12' => '4', 'k3a_rincian_acuan_13' => '7',
                        'k3a_rincian_target_21' => '3', 'k3a_rincian_target_22' => '-2', 'k3a_rincian_target_23' => '5',
                        'k3a_rincian_kali_21' => '-3', 'k3a_rincian_kali_22' => '-12', 'k3a_rincian_kali_23' => '-21',
                        'k3a_rincian_jumlah_target_21' => '3', 'k3a_rincian_jumlah_target_22' => '-2', 'k3a_rincian_jumlah_target_23' => '5',
                        'k3a_rincian_hasil_21' => '0', 'k3a_rincian_hasil_22' => '-14', 'k3a_rincian_hasil_23' => '-16',
                        'k3a_11' => '1', 'k3a_12' => '4', 'k3a_13' => '7',
                        'k3a_21' => '0', 'k3a_22' => '-14', 'k3a_23' => '-16',
                    ], 'Gunakan hasil operasi -3B1 + B2 pada setiap elemen Baris-2.'),

                    'k3b_i' => $question(['2', 'baris 2', 'baris ke-2'], '2', 'Elemen -2/3 yang ingin dinolkan berada pada Baris-2.'),
                    'k3b_j' => $question(['1', 'baris 1', 'baris ke-1'], '1', 'Gunakan Baris-1 sebagai baris acuan.'),
                    'k3b_k' => $question(['2/3', '2 per 3'], '2/3', 'Lawan dari -2/3 adalah 2/3.'),
                    'k3b_notasi' => $question(
                        ['b2←2/3b1+b2', 'b2<-2/3b1+b2', 'b_2←2/3b_1+b_2', 'b_2<-2/3b_1+b_2'],
                        'B2 ← 2/3 B1 + B2',
                        'Gunakan 2/3B1 lalu tambahkan ke B2.'
                    ),
                    ...$valueQuestions([
                        'k3b_rincian_acuan_11' => '1', 'k3b_rincian_acuan_12' => '6', 'k3b_rincian_acuan_13' => '-3',
                        'k3b_rincian_target_21' => '-2/3', 'k3b_rincian_target_22' => '5', 'k3b_rincian_target_23' => '4',
                        'k3b_rincian_kali_21' => '2/3', 'k3b_rincian_kali_22' => '4', 'k3b_rincian_kali_23' => '-2',
                        'k3b_rincian_jumlah_target_21' => '-2/3', 'k3b_rincian_jumlah_target_22' => '5', 'k3b_rincian_jumlah_target_23' => '4',
                        'k3b_rincian_hasil_21' => '0', 'k3b_rincian_hasil_22' => '9', 'k3b_rincian_hasil_23' => '2',
                        'k3b_11' => '1', 'k3b_12' => '6', 'k3b_13' => '-3',
                        'k3b_21' => '0', 'k3b_22' => '9', 'k3b_23' => '2',
                    ], 'Gunakan hasil operasi 2/3B1 + B2 pada setiap elemen Baris-2.'),
                ],
            ],

            default => null,
        };
    }



    /* SUBBAB_3_1_ESELON_BARIS_V1 */
    private function getSubbab31PracticeDefinition(string $practiceKey): ?array
    {
        return match ($practiceKey) {
            'aktivitas-3-1-eselon-baris' => [
                'title' => 'Aktivitas 3.1 - Uji Visual Eselon Baris',
                'type' => 'aktivitas',
                'max_score' => 100,
                'groups' => [
                    'matrix_a' => [
                        'number' => 1,
                        'fields' => ['matrix_a'],
                        'points' => 20,
                    ],
                    'matrix_b' => [
                        'number' => 2,
                        'fields' => ['matrix_b'],
                        'points' => 20,
                    ],
                    'matrix_c' => [
                        'number' => 3,
                        'fields' => ['matrix_c'],
                        'points' => 20,
                    ],
                    'matrix_d' => [
                        'number' => 4,
                        'fields' => ['matrix_d'],
                        'points' => 20,
                    ],
                    'matrix_e' => [
                        'number' => 5,
                        'fields' => ['matrix_e'],
                        'points' => 20,
                    ],
                ],
                'questions' => [
                    'matrix_a' => [
                        'accepted_answers' => ['eselon'],
                        'display_answer' => 'Zona Matriks Eselon Baris',
                        'feedback_correct' => 'Benar. Matriks A memiliki 1 utama yang membentuk pola tangga ke kanan.',
                        'feedback_wrong' => 'Periksa kembali 1 utama pada setiap baris dan pola posisi pivot dari atas ke bawah.',
                    ],
                    'matrix_b' => [
                        'accepted_answers' => ['bukan'],
                        'display_answer' => 'Zona Bukan Eselon Baris',
                        'feedback_correct' => 'Benar. Posisi 1 utama pada baris ketiga bergeser ke kiri dari baris sebelumnya.',
                        'feedback_wrong' => 'Amati posisi 1 utama pada baris kedua dan ketiga. Posisi pivot tidak membentuk pola tangga ke kanan.',
                    ],
                    'matrix_c' => [
                        'accepted_answers' => ['bukan'],
                        'display_answer' => 'Zona Bukan Eselon Baris',
                        'feedback_correct' => 'Benar. Bilangan tak nol pertama pada baris kedua adalah 2, bukan 1 utama.',
                        'feedback_wrong' => 'Periksa nilai bilangan tak nol pertama pada setiap baris yang tidak seluruhnya nol.',
                    ],
                    'matrix_d' => [
                        'accepted_answers' => ['eselon'],
                        'display_answer' => 'Zona Matriks Eselon Baris',
                        'feedback_correct' => 'Benar. Matriks D memiliki pola pivot yang tepat dan baris nol berada di bagian bawah.',
                        'feedback_wrong' => 'Periksa urutan pivot dan posisi baris nol pada matriks ini.',
                    ],
                    'matrix_e' => [
                        'accepted_answers' => ['bukan'],
                        'display_answer' => 'Zona Bukan Eselon Baris',
                        'feedback_correct' => 'Benar. 1 utama pada baris kedua berada lebih ke kiri daripada 1 utama pada baris pertama.',
                        'feedback_wrong' => 'Bandingkan posisi 1 utama pada Baris-1 dan Baris-2. Pivot baris bawah harus bergeser ke kanan.',
                    ],
                ],
            ],

            default => null,
        };
    }


    /**
     * SUBBAB_3_2_SIMULASI_ESELON_BARIS_KEPUTUSAN_V3
     * SUBBAB_3_2_NOTASI_ACUAN_DAHULU_V1
     */
    private function getSubbab32PracticeDefinition(string $practiceKey): ?array
    {
        $operationQuestion = static function (
            string $target,
            array $expressions,
            string $displayAnswer,
            string $feedbackWrong
        ): array {
            $arrows = ['\leftarrow', '\gets', '←', '<-'];
            $acceptedAnswers = [];

            foreach ($expressions as $expression) {
                foreach ($arrows as $arrow) {
                    $acceptedAnswers[] = "{$target} {$arrow} {$expression}";
                }
            }

            return [
                'accepted_answers' => array_values(array_unique($acceptedAnswers)),
                'display_answer' => $displayAnswer,
                'feedback_correct' => 'Benar. Notasi operasi yang digunakan sudah sesuai dengan target eliminasi.',
                'feedback_wrong' => $feedbackWrong,
            ];
        };

        $valueAnswers = static function (string $value): array {
            return match ($value) {
                '1/3' => ['1/3', '1 per 3', '\frac{1}{3}', '\dfrac{1}{3}'],
                '-1/3' => ['-1/3', '-1 per 3', '-\frac{1}{3}', '-\dfrac{1}{3}'],
                '5/3' => ['5/3', '5 per 3', '\frac{5}{3}', '\dfrac{5}{3}'],
                default => [$value],
            };
        };

        $valueQuestions = static function (array $values, string $feedbackWrong) use ($valueAnswers): array {
            $questions = [];

            foreach ($values as $field => $value) {
                $questions[$field] = [
                    'accepted_answers' => $valueAnswers((string) $value),
                    'display_answer' => (string) $value,
                    'feedback_correct' => 'Benar.',
                    'feedback_wrong' => $feedbackWrong,
                ];
            }

            return $questions;
        };

        $decisionQuestion = static function (
            string $correctAnswer,
            string $feedbackCorrect,
            string $feedbackWrong
        ): array {
            return [
                'accepted_answers' => [$correctAnswer],
                'display_answer' => strtoupper($correctAnswer),
                'feedback_correct' => $feedbackCorrect,
                'feedback_wrong' => $feedbackWrong,
            ];
        };

        return match ($practiceKey) {
            'contoh-simulasi-3-2-eselon-baris' => [
                'title' => 'Contoh Simulasi 3.2 - Mengubah Matriks Menjadi Eselon Baris',
                'type' => 'contoh_simulasi',
                'max_score' => 0,
                'definition_version' => 'subbab-3-2-keputusan-v3',
                'groups' => [
                    'fase_1' => [
                        'number' => 1,
                        'fields' => [
                            'fase1_q1_pivot',
                            'fase1_q1_baris2',
                            'fase1_q1_baris3',
                            'fase1_q1_baris4',

                            'fase1_target1a_notasi', 'fase1_target1a_k',
                            'fase1_target1a_produk_21', 'fase1_target1a_produk_22', 'fase1_target1a_produk_23', 'fase1_target1a_produk_24', 'fase1_target1a_produk_25',
                            'fase1_target1a_hasil_21', 'fase1_target1a_hasil_22', 'fase1_target1a_hasil_23', 'fase1_target1a_hasil_24', 'fase1_target1a_hasil_25',

                            'fase1_target1b_notasi', 'fase1_target1b_k',
                            'fase1_target1b_produk_31', 'fase1_target1b_produk_32', 'fase1_target1b_produk_33', 'fase1_target1b_produk_34', 'fase1_target1b_produk_35',
                            'fase1_target1b_hasil_31', 'fase1_target1b_hasil_32', 'fase1_target1b_hasil_33', 'fase1_target1b_hasil_34', 'fase1_target1b_hasil_35',

                            'fase1_target1c_notasi', 'fase1_target1c_k',
                            'fase1_target1c_produk_41', 'fase1_target1c_produk_42', 'fase1_target1c_produk_43', 'fase1_target1c_produk_44', 'fase1_target1c_produk_45',
                            'fase1_target1c_hasil_41', 'fase1_target1c_hasil_42', 'fase1_target1c_hasil_43', 'fase1_target1c_hasil_44', 'fase1_target1c_hasil_45',
                        ],
                        'points' => 0,
                    ],
                    'fase_2' => [
                        'number' => 2,
                        'fields' => [
                            'fase2_q1_pivot',
                            'fase2_q2_baris3',
                            'fase2_q3_baris4',

                            'fase2_pivot_notasi',
                            'fase2_pivot_hasil_21', 'fase2_pivot_hasil_22', 'fase2_pivot_hasil_23', 'fase2_pivot_hasil_24', 'fase2_pivot_hasil_25',

                            'fase2_target2a_notasi', 'fase2_target2a_k',
                            'fase2_target2a_produk_31', 'fase2_target2a_produk_32', 'fase2_target2a_produk_33', 'fase2_target2a_produk_34', 'fase2_target2a_produk_35',
                            'fase2_target2a_hasil_31', 'fase2_target2a_hasil_32', 'fase2_target2a_hasil_33', 'fase2_target2a_hasil_34', 'fase2_target2a_hasil_35',
                        ],
                        'points' => 0,
                    ],
                    'fase_3' => [
                        'number' => 3,
                        'fields' => [
                            'fase3_q1_pivot',
                            'fase3_q2_baris4',

                            'fase3_pivot_notasi',
                            'fase3_pivot_hasil_31', 'fase3_pivot_hasil_32', 'fase3_pivot_hasil_33', 'fase3_pivot_hasil_34', 'fase3_pivot_hasil_35',

                            'fase3_target3a_notasi', 'fase3_target3a_k',
                            'fase3_target3a_produk_41', 'fase3_target3a_produk_42', 'fase3_target3a_produk_43', 'fase3_target3a_produk_44', 'fase3_target3a_produk_45',
                            'fase3_target3a_hasil_41', 'fase3_target3a_hasil_42', 'fase3_target3a_hasil_43', 'fase3_target3a_hasil_44', 'fase3_target3a_hasil_45',
                        ],
                        'points' => 0,
                    ],
                    'fase_4' => [
                        'number' => 4,
                        'fields' => [
                            'fase4_q1_pivot',

                            'fase4_pivot_notasi',
                            'fase4_pivot_hasil_41', 'fase4_pivot_hasil_42', 'fase4_pivot_hasil_43', 'fase4_pivot_hasil_44', 'fase4_pivot_hasil_45',
                        ],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    'fase1_q1_pivot' => $decisionQuestion(
                        'ya',
                        'Benar. Elemen utama pada Baris-1 Kolom-1 sudah bernilai 1.',
                        'Jawaban yang benar adalah YA. Elemen utama pada Baris-1 Kolom-1 sudah bernilai 1.'
                    ),
                    'fase1_q1_baris2' => $decisionQuestion(
                        'tidak',
                        'Benar. Elemen pada Baris-2 Kolom-1 bernilai -1 sehingga belum bernilai 0.',
                        'Jawaban yang benar adalah TIDAK. Elemen pada Baris-2 Kolom-1 bernilai -1 sehingga perlu dieliminasi.'
                    ),
                    'fase1_q1_baris3' => $decisionQuestion(
                        'tidak',
                        'Benar. Elemen pada Baris-3 Kolom-1 bernilai 2 sehingga belum bernilai 0.',
                        'Jawaban yang benar adalah TIDAK. Elemen pada Baris-3 Kolom-1 bernilai 2 sehingga perlu dieliminasi.'
                    ),
                    'fase1_q1_baris4' => $decisionQuestion(
                        'tidak',
                        'Benar. Elemen pada Baris-4 Kolom-1 bernilai 1 sehingga belum bernilai 0.',
                        'Jawaban yang benar adalah TIDAK. Elemen pada Baris-4 Kolom-1 bernilai 1 sehingga perlu dieliminasi.'
                    ),

                    'fase2_q1_pivot' => $decisionQuestion(
                        'tidak',
                        'Benar. Elemen utama pada Baris-2 Kolom-2 bernilai 3, sehingga harus diubah menjadi 1.',
                        'Jawaban yang benar adalah TIDAK. Elemen utama pada Baris-2 Kolom-2 masih bernilai 3.'
                    ),
                    'fase2_q2_baris3' => $decisionQuestion(
                        'tidak',
                        'Benar. Elemen pada Baris-3 Kolom-2 bernilai -1 sehingga belum bernilai 0.',
                        'Jawaban yang benar adalah TIDAK. Elemen pada Baris-3 Kolom-2 bernilai -1 sehingga perlu dieliminasi.'
                    ),
                    'fase2_q3_baris4' => $decisionQuestion(
                        'ya',
                        'Benar. Elemen pada Baris-4 Kolom-2 sudah bernilai 0.',
                        'Jawaban yang benar adalah YA. Elemen pada Baris-4 Kolom-2 sudah bernilai 0 sehingga tidak perlu dieliminasi.'
                    ),

                    'fase3_q1_pivot' => $decisionQuestion(
                        'tidak',
                        'Benar. Elemen utama pada Baris-3 Kolom-3 bernilai -1, sehingga harus diubah menjadi 1.',
                        'Jawaban yang benar adalah TIDAK. Elemen utama pada Baris-3 Kolom-3 masih bernilai -1.'
                    ),
                    'fase3_q2_baris4' => $decisionQuestion(
                        'tidak',
                        'Benar. Elemen pada Baris-4 Kolom-3 bernilai -1 sehingga belum bernilai 0.',
                        'Jawaban yang benar adalah TIDAK. Elemen pada Baris-4 Kolom-3 bernilai -1 sehingga perlu dieliminasi.'
                    ),

                    'fase4_q1_pivot' => $decisionQuestion(
                        'tidak',
                        'Benar. Elemen utama pada Baris-4 Kolom-4 bernilai -3, sehingga harus diubah menjadi 1.',
                        'Jawaban yang benar adalah TIDAK. Elemen utama pada Baris-4 Kolom-4 masih bernilai -3.'
                    ),

                    'fase1_target1a_notasi' => $operationQuestion(
                        'B_2',
                        ['B_1 + B_2', 'B_2 + B_1', '1B_1 + B_2'],
                        'B_2 \leftarrow B_1 + B_2',
                        'Gunakan Baris-1 untuk menghilangkan elemen -1 pada Baris-2 Kolom-1.'
                    ),
                    ...$valueQuestions([
                        'fase1_target1a_k' => '1',
                        'fase1_target1a_produk_21' => '1',
                        'fase1_target1a_produk_22' => '1',
                        'fase1_target1a_produk_23' => '2',
                        'fase1_target1a_produk_24' => '-1',
                        'fase1_target1a_produk_25' => '5',
                        'fase1_target1a_hasil_21' => '0',
                        'fase1_target1a_hasil_22' => '3',
                        'fase1_target1a_hasil_23' => '3',
                        'fase1_target1a_hasil_24' => '0',
                        'fase1_target1a_hasil_25' => '15',
                    ], 'Periksa kembali hasil operasi B2 ← B1 + B2 pada setiap elemen Baris-2.'),

                    'fase1_target1b_notasi' => $operationQuestion(
                        'B_3',
                        ['-2B_1 + B_3', 'B_3 - 2B_1', 'B_3 + (-2)B_1'],
                        'B_3 \leftarrow -2B_1 + B_3',
                        'Gunakan kelipatan Baris-1 yang membuat 2 pada Baris-3 Kolom-1 menjadi 0.'
                    ),
                    ...$valueQuestions([
                        'fase1_target1b_k' => '-2',
                        'fase1_target1b_produk_31' => '-2',
                        'fase1_target1b_produk_32' => '-2',
                        'fase1_target1b_produk_33' => '-4',
                        'fase1_target1b_produk_34' => '2',
                        'fase1_target1b_produk_35' => '-10',
                        'fase1_target1b_hasil_31' => '0',
                        'fase1_target1b_hasil_32' => '-1',
                        'fase1_target1b_hasil_33' => '-2',
                        'fase1_target1b_hasil_34' => '5',
                        'fase1_target1b_hasil_35' => '2',
                    ], 'Periksa kembali hasil operasi B3 ← -2B1 + B3 pada setiap elemen Baris-3.'),

                    'fase1_target1c_notasi' => $operationQuestion(
                        'B_4',
                        ['-B_1 + B_4', 'B_4 - B_1', 'B_4 + (-1)B_1'],
                        'B_4 \leftarrow -B_1 + B_4',
                        'Gunakan Baris-1 untuk menghilangkan elemen 1 pada Baris-4 Kolom-1.'
                    ),
                    ...$valueQuestions([
                        'fase1_target1c_k' => '-1',
                        'fase1_target1c_produk_41' => '-1',
                        'fase1_target1c_produk_42' => '-1',
                        'fase1_target1c_produk_43' => '-2',
                        'fase1_target1c_produk_44' => '1',
                        'fase1_target1c_produk_45' => '-5',
                        'fase1_target1c_hasil_41' => '0',
                        'fase1_target1c_hasil_42' => '0',
                        'fase1_target1c_hasil_43' => '-1',
                        'fase1_target1c_hasil_44' => '2',
                        'fase1_target1c_hasil_45' => '2',
                    ], 'Periksa kembali hasil operasi B4 ← -B1 + B4 pada setiap elemen Baris-4.'),

                    'fase2_pivot_notasi' => $operationQuestion(
                        'B_2',
                        ['\frac{1}{3}B_2', '1/3B_2', '(1/3)B_2', 'B_2/3'],
                        'B_2 \leftarrow \frac{1}{3}B_2',
                        'Gunakan kebalikan perkalian dari 3 agar elemen utama Baris-2 menjadi 1.'
                    ),
                    ...$valueQuestions([
                        'fase2_pivot_hasil_21' => '0',
                        'fase2_pivot_hasil_22' => '1',
                        'fase2_pivot_hasil_23' => '1',
                        'fase2_pivot_hasil_24' => '0',
                        'fase2_pivot_hasil_25' => '5',
                    ], 'Kalikan seluruh elemen Baris-2 dengan 1/3.'),

                    'fase2_target2a_notasi' => $operationQuestion(
                        'B_3',
                        ['B_2 + B_3', 'B_3 + B_2', '1B_2 + B_3'],
                        'B_3 \leftarrow B_2 + B_3',
                        'Gunakan Baris-2 untuk menghilangkan elemen -1 pada Baris-3 Kolom-2.'
                    ),
                    ...$valueQuestions([
                        'fase2_target2a_k' => '1',
                        'fase2_target2a_produk_31' => '0',
                        'fase2_target2a_produk_32' => '1',
                        'fase2_target2a_produk_33' => '1',
                        'fase2_target2a_produk_34' => '0',
                        'fase2_target2a_produk_35' => '5',
                        'fase2_target2a_hasil_31' => '0',
                        'fase2_target2a_hasil_32' => '0',
                        'fase2_target2a_hasil_33' => '-1',
                        'fase2_target2a_hasil_34' => '5',
                        'fase2_target2a_hasil_35' => '7',
                    ], 'Periksa kembali hasil operasi B3 ← B2 + B3 pada setiap elemen Baris-3.'),

                    'fase3_pivot_notasi' => $operationQuestion(
                        'B_3',
                        ['-B_3', '(-1)B_3', '-1B_3'],
                        'B_3 \leftarrow -B_3',
                        'Kalikan Baris-3 dengan -1 agar elemen utama -1 menjadi 1.'
                    ),
                    ...$valueQuestions([
                        'fase3_pivot_hasil_31' => '0',
                        'fase3_pivot_hasil_32' => '0',
                        'fase3_pivot_hasil_33' => '1',
                        'fase3_pivot_hasil_34' => '-5',
                        'fase3_pivot_hasil_35' => '-7',
                    ], 'Kalikan seluruh elemen Baris-3 dengan -1.'),

                    'fase3_target3a_notasi' => $operationQuestion(
                        'B_4',
                        ['B_3 + B_4', 'B_4 + B_3', '1B_3 + B_4'],
                        'B_4 \leftarrow B_3 + B_4',
                        'Gunakan Baris-3 untuk menghilangkan elemen -1 pada Baris-4 Kolom-3.'
                    ),
                    ...$valueQuestions([
                        'fase3_target3a_k' => '1',
                        'fase3_target3a_produk_41' => '0',
                        'fase3_target3a_produk_42' => '0',
                        'fase3_target3a_produk_43' => '1',
                        'fase3_target3a_produk_44' => '-5',
                        'fase3_target3a_produk_45' => '-7',
                        'fase3_target3a_hasil_41' => '0',
                        'fase3_target3a_hasil_42' => '0',
                        'fase3_target3a_hasil_43' => '0',
                        'fase3_target3a_hasil_44' => '-3',
                        'fase3_target3a_hasil_45' => '-5',
                    ], 'Periksa kembali hasil operasi B4 ← B3 + B4 pada setiap elemen Baris-4.'),

                    'fase4_pivot_notasi' => $operationQuestion(
                        'B_4',
                        ['-\frac{1}{3}B_4', '-1/3B_4', '(-1/3)B_4', 'B_4/(-3)'],
                        'B_4 \leftarrow -\frac{1}{3}B_4',
                        'Gunakan kebalikan perkalian dari -3 agar elemen utama Baris-4 menjadi 1.'
                    ),
                    ...$valueQuestions([
                        'fase4_pivot_hasil_41' => '0',
                        'fase4_pivot_hasil_42' => '0',
                        'fase4_pivot_hasil_43' => '0',
                        'fase4_pivot_hasil_44' => '1',
                        'fase4_pivot_hasil_45' => '5/3',
                    ], 'Kalikan seluruh elemen Baris-4 dengan -1/3.'),
                ],
            ],
            default => null,
        };
    }



    // SUBBAB_3_3_RINCIAN_LENGKAP_V2
    private function getSubbab33PracticeDefinition(string $practiceKey): ?array
    {
        $valueAnswers = static function (string $value): array {
            return match ($value) {
                '1/2' => ['1/2', '1 per 2'],
                '-1/2' => ['-1/2', '-1 per 2'],
                '1/3' => ['1/3', '1 per 3'],
                '-1/3' => ['-1/3', '-1 per 3'],
                '4/3' => ['4/3', '4 per 3'],
                '5/3' => ['5/3', '5 per 3'],
                '11/3' => ['11/3', '11 per 3'],
                '5/2' => ['5/2', '5 per 2'],
                default => [$value],
            };
        };

        $valueQuestion = static function (string $value, string $wrong) use ($valueAnswers): array {
            return [
                'accepted_answers' => $valueAnswers($value),
                'display_answer' => $value,
                'feedback_correct' => 'Benar.',
                'feedback_wrong' => $wrong,
            ];
        };

        $valueQuestions = static function (array $values, string $wrong) use ($valueQuestion): array {
            $questions = [];

            foreach ($values as $field => $value) {
                $questions[$field] = $valueQuestion((string) $value, $wrong);
            }

            return $questions;
        };

        $decisionQuestion = static function (string $answer, string $detail): array {
            return [
                'accepted_answers' => [$answer],
                'display_answer' => ucfirst($answer),
                'feedback_correct' => 'Benar. ' . $detail,
                'feedback_wrong' => 'Jawaban yang benar adalah ' . strtoupper($answer) . '. ' . $detail,
            ];
        };

        $operationQuestion = static function (array $accepted, string $display, string $wrong): array {
            return [
                'accepted_answers' => $accepted,
                'display_answer' => $display,
                'feedback_correct' => 'Benar.',
                'feedback_wrong' => $wrong,
            ];
        };

        return match ($practiceKey) {
            'contoh-simulasi-3-3-substitusi-balik' => [
                'title' => 'Contoh Simulasi 3.3 - Substitusi Balik',
                'type' => 'contoh_simulasi',
                'definition_version' => 'subbab33_eliminasi_gauss_v1',
                'max_score' => 0,
                'groups' => [
                    'baris_3' => [
                        'number' => 1,
                        'fields' => ['c_b3_numerator_a', 'c_b3_numerator_b'],
                        'points' => 0,
                    ],
                    'baris_2' => [
                        'number' => 2,
                        'fields' => [
                            'c_b2_sub_x3', 'c_b2_pengurang', 'c_b2_pembilang_5',
                            'c_b2_pembilang_sub', 'c_b2_hasil',
                        ],
                        'points' => 0,
                    ],
                    'baris_1' => [
                        'number' => 3,
                        'fields' => [
                            'c_b1_x2', 'c_b1_x3', 'c_b1_penyederhanaan_a',
                            'c_b1_penyederhanaan_b', 'c_b1_total', 'c_b1_pengurang',
                            'c_b1_pembilang_5', 'c_b1_pembilang_sub', 'c_b1_hasil',
                        ],
                        'points' => 0,
                    ],
                    'hasil' => [
                        'number' => 4,
                        'fields' => ['c_hasil_x1', 'c_hasil_x2', 'c_hasil_x3', 'c_hasil_x4'],
                        'points' => 0,
                    ],
                ],
                'questions' => [
                    ...$valueQuestions([
                        'c_b3_numerator_a' => '-21',
                        'c_b3_numerator_b' => '4',
                        'c_b2_sub_x3' => '4',
                        'c_b2_pengurang' => '4',
                        'c_b2_pembilang_5' => '15',
                        'c_b2_pembilang_sub' => '4',
                        'c_b2_hasil' => '11',
                        'c_b1_x2' => '11',
                        'c_b1_x3' => '4',
                        'c_b1_penyederhanaan_a' => '11',
                        'c_b1_penyederhanaan_b' => '8',
                        'c_b1_total' => '14',
                        'c_b1_pengurang' => '14',
                        'c_b1_pembilang_5' => '15',
                        'c_b1_pembilang_sub' => '14',
                        'c_b1_hasil' => '1',
                    ], 'Periksa kembali proses substitusi balik dari baris yang lebih bawah.'),
                    'c_hasil_x1' => $valueQuestion('1/3', 'Nilai x₁ diperoleh dari Baris-1.'),
                    'c_hasil_x2' => $valueQuestion('11/3', 'Nilai x₂ diperoleh dari Baris-2.'),
                    'c_hasil_x3' => $valueQuestion('4/3', 'Nilai x₃ diperoleh dari Baris-3.'),
                    'c_hasil_x4' => $valueQuestion('5/3', 'Nilai x₄ diperoleh dari Baris-4.'),
                ],
            ],

            'aktivitas-3-2-eliminasi-gauss' => [
                'title' => 'Aktivitas 3.2 - Menyelesaikan SPL dengan Eliminasi Gauss',
                'type' => 'aktivitas',
                'definition_version' => 'subbab33_eliminasi_gauss_v2',
                'max_score' => 100,
                'groups' => [
                    'fase_1' => [
                        'number' => 1,
                        'fields' => [
                            'a_f1_q1_pivot', 'a_f1_pivot_notasi',
                            'a_f1_pivot_11', 'a_f1_pivot_12', 'a_f1_pivot_13', 'a_f1_pivot_14',
                            'a_f1_q2_baris2', 'a_f1_b2_notasi', 'a_f1_b2_k',
                            'a_f1_b2_produk_1', 'a_f1_b2_produk_2', 'a_f1_b2_produk_3', 'a_f1_b2_produk_4',
                            'a_f1_b2_hasil_1', 'a_f1_b2_hasil_2', 'a_f1_b2_hasil_3', 'a_f1_b2_hasil_4',
                            'a_f1_q3_baris3', 'a_f1_b3_notasi', 'a_f1_b3_k',
                            'a_f1_b3_produk_1', 'a_f1_b3_produk_2', 'a_f1_b3_produk_3', 'a_f1_b3_produk_4',
                            'a_f1_b3_hasil_1', 'a_f1_b3_hasil_2', 'a_f1_b3_hasil_3', 'a_f1_b3_hasil_4',
                        ],
                        'points' => 25,
                    ],
                    'fase_2' => [
                        'number' => 2,
                        'fields' => [
                            'a_f2_q1_pivot', 'a_f2_pivot_notasi', 'a_f2_pivot_23', 'a_f2_pivot_24',
                            'a_f2_q2_baris3', 'a_f2_b3_notasi', 'a_f2_b3_k',
                            'a_f2_b3_produk_1', 'a_f2_b3_produk_2', 'a_f2_b3_produk_3', 'a_f2_b3_produk_4',
                            'a_f2_b3_awal_1', 'a_f2_b3_awal_2', 'a_f2_b3_awal_3', 'a_f2_b3_awal_4',
                            'a_f2_b3_hasil_3', 'a_f2_b3_hasil_4',
                        ],
                        'points' => 25,
                    ],
                    'fase_3' => [
                        'number' => 3,
                        'fields' => [
                            'a_f3_notasi',
                            'a_f3_final_11', 'a_f3_final_12', 'a_f3_final_13', 'a_f3_final_14',
                            'a_f3_final_23', 'a_f3_final_24', 'a_f3_final_34',
                        ],
                        'points' => 25,
                    ],
                    'fase_4' => [
                        'number' => 4,
                        'fields' => [
                            'a_f4_z',
                            'a_f4_y_koefisien', 'a_f4_y_ruas_kanan_awal',
                            'a_f4_y_koefisien_sub', 'a_f4_y_sub_z', 'a_f4_y_ruas_kanan_sub',
                            'a_f4_y_pengurang', 'a_f4_y_ruas_kanan_sederhana',
                            'a_f4_y_ruas_kanan_pindah', 'a_f4_y_pindah_ruas', 'a_f4_y_hasil',
                            'a_f4_x_ruas_kanan_awal', 'a_f4_x_sub_y', 'a_f4_x_sub_z',
                            'a_f4_x_ruas_kanan_sub', 'a_f4_x_ruas_kanan_sederhana',
                            'a_f4_x_pindah_ruas', 'a_f4_x_pembilang', 'a_f4_x_hasil_pembilang',
                            'a_f4_hasil_x', 'a_f4_hasil_y', 'a_f4_hasil_z',
                        ],
                        'points' => 25,
                    ],
                ],
                'questions' => [
                    'a_f1_q1_pivot' => $decisionQuestion(
                        'tidak',
                        'Elemen utama Baris-1 bernilai 2 sehingga harus diubah menjadi 1 utama.'
                    ),
                    'a_f1_pivot_notasi' => $operationQuestion(
                        [
                            'b1←1/2b1',
                            'b_1←1/2b_1',
                            'b1<-1/2b1',
                            'b_1<-1/2b_1',
                            'b_1\leftarrow\frac{1}{2}b_1',
                            'b_1\leftarrow\frac12b_1',
                            'b_1\leftarrow\frac{1}2b_1',
                            'b_1\leftarrow\frac1{2}b_1',
                            'b1\leftarrow\frac{1}{2}b1',
                        ],
                        'B₁ ← 1/2 B₁',
                        'Kalikan seluruh elemen Baris-1 dengan 1/2.'
                    ),
                    ...$valueQuestions([
                        'a_f1_pivot_11' => '1',
                        'a_f1_pivot_12' => '1',
                        'a_f1_pivot_13' => '-1/2',
                        'a_f1_pivot_14' => '2',
                    ], 'Periksa kembali hasil perkalian Baris-1 dengan 1/2.'),

                    'a_f1_q2_baris2' => $decisionQuestion(
                        'tidak',
                        'Elemen Baris-2 Kolom-1 bernilai 4 sehingga perlu dieliminasi.'
                    ),
                    'a_f1_b2_notasi' => $operationQuestion(
                        ['b2←-4b1+b2', 'b_2←-4b_1+b_2', 'b2<- -4b1+b2', 'b_2<- -4b_1+b_2'],
                        'B₂ ← -4B₁ + B₂',
                        'Gunakan -4B₁ kemudian tambahkan ke B₂.'
                    ),
                    ...$valueQuestions([
                        'a_f1_b2_k' => '-4',
                        'a_f1_b2_produk_1' => '-4',
                        'a_f1_b2_produk_2' => '-4',
                        'a_f1_b2_produk_3' => '2',
                        'a_f1_b2_produk_4' => '-8',
                        'a_f1_b2_hasil_1' => '0',
                        'a_f1_b2_hasil_2' => '-3',
                        'a_f1_b2_hasil_3' => '3',
                        'a_f1_b2_hasil_4' => '3',
                    ], 'Periksa kembali rincian operasi B₂ ← -4B₁ + B₂.'),

                    'a_f1_q3_baris3' => $decisionQuestion(
                        'tidak',
                        'Elemen Baris-3 Kolom-1 bernilai -2 sehingga perlu dieliminasi.'
                    ),
                    'a_f1_b3_notasi' => $operationQuestion(
                        ['b3←2b1+b3', 'b_3←2b_1+b_3', 'b3<-2b1+b3', 'b_3<-2b_1+b_3'],
                        'B₃ ← 2B₁ + B₃',
                        'Gunakan 2B₁ kemudian tambahkan ke B₃.'
                    ),
                    ...$valueQuestions([
                        'a_f1_b3_k' => '2',
                        'a_f1_b3_produk_1' => '2',
                        'a_f1_b3_produk_2' => '2',
                        'a_f1_b3_produk_3' => '-1',
                        'a_f1_b3_produk_4' => '4',
                        'a_f1_b3_hasil_1' => '0',
                        'a_f1_b3_hasil_2' => '3',
                        'a_f1_b3_hasil_3' => '2',
                        'a_f1_b3_hasil_4' => '2',
                    ], 'Periksa kembali rincian operasi B₃ ← 2B₁ + B₃.'),

                    'a_f2_q1_pivot' => $decisionQuestion(
                        'tidak',
                        'Elemen utama Baris-2 bernilai -3 sehingga harus diubah menjadi 1 utama.'
                    ),
                    'a_f2_pivot_notasi' => $operationQuestion(
                        ['b2←-1/3b2', 'b_2←-1/3b_2', 'b2<- -1/3b2', 'b_2<- -1/3b_2'],
                        'B₂ ← -1/3 B₂',
                        'Kalikan seluruh elemen Baris-2 dengan -1/3.'
                    ),
                    ...$valueQuestions([
                        'a_f2_pivot_23' => '-1',
                        'a_f2_pivot_24' => '-1',
                    ], 'Periksa kembali hasil perkalian Baris-2 dengan -1/3.'),

                    'a_f2_q2_baris3' => $decisionQuestion(
                        'tidak',
                        'Elemen Baris-3 Kolom-2 bernilai 3 sehingga perlu dieliminasi.'
                    ),
                    'a_f2_b3_notasi' => $operationQuestion(
                        ['b3←-3b2+b3', 'b_3←-3b_2+b_3', 'b3<- -3b2+b3', 'b_3<- -3b_2+b_3'],
                        'B₃ ← -3B₂ + B₃',
                        'Gunakan -3B₂ kemudian tambahkan ke B₃.'
                    ),
                    ...$valueQuestions([
                        'a_f2_b3_k' => '-3',
                        'a_f2_b3_produk_1' => '0',
                        'a_f2_b3_produk_2' => '-3',
                        'a_f2_b3_produk_3' => '3',
                        'a_f2_b3_produk_4' => '3',
                        'a_f2_b3_awal_1' => '0',
                        'a_f2_b3_awal_2' => '3',
                        'a_f2_b3_awal_3' => '2',
                        'a_f2_b3_awal_4' => '2',
                        'a_f2_b3_hasil_3' => '5',
                        'a_f2_b3_hasil_4' => '5',
                    ], 'Periksa kembali rincian operasi B₃ ← -3B₂ + B₃.'),

                    'a_f3_notasi' => $operationQuestion(
                        ['b3←1/5b3', 'b_3←1/5b_3', 'b3<-1/5b3', 'b_3<-1/5b_3'],
                        'B₃ ← 1/5 B₃',
                        'Kalikan seluruh elemen Baris-3 dengan 1/5.'
                    ),
                    ...$valueQuestions([
                        'a_f3_final_11' => '1',
                        'a_f3_final_12' => '1',
                        'a_f3_final_13' => '-1/2',
                        'a_f3_final_14' => '2',
                        'a_f3_final_23' => '-1',
                        'a_f3_final_24' => '-1',
                        'a_f3_final_34' => '1',
                    ], 'Periksa kembali matriks eselon baris final.'),

                    ...$valueQuestions([
                        'a_f4_z' => '1',
                        'a_f4_y_koefisien' => '-1',
                        'a_f4_y_ruas_kanan_awal' => '-1',
                        'a_f4_y_koefisien_sub' => '-1',
                        'a_f4_y_sub_z' => '1',
                        'a_f4_y_ruas_kanan_sub' => '-1',
                        'a_f4_y_pengurang' => '1',
                        'a_f4_y_ruas_kanan_sederhana' => '-1',
                        'a_f4_y_ruas_kanan_pindah' => '-1',
                        'a_f4_y_pindah_ruas' => '1',
                        'a_f4_y_hasil' => '0',
                        'a_f4_x_ruas_kanan_awal' => '2',
                        'a_f4_x_sub_y' => '0',
                        'a_f4_x_sub_z' => '1',
                        'a_f4_x_ruas_kanan_sub' => '2',
                        'a_f4_x_ruas_kanan_sederhana' => '2',
                        'a_f4_x_pindah_ruas' => '2',
                        'a_f4_x_pembilang' => '4',
                        'a_f4_x_hasil_pembilang' => '5',
                    ], 'Periksa kembali proses substitusi balik berdasarkan matriks eselon baris final.'),
                    'a_f4_hasil_x' => $valueQuestion('5/2', 'Nilai akhir x diperoleh dari Baris-1.'),
                    'a_f4_hasil_y' => $valueQuestion('0', 'Nilai akhir y diperoleh dari Baris-2.'),
                    'a_f4_hasil_z' => $valueQuestion('1', 'Nilai akhir z diperoleh dari Baris-3.'),
                ],
            ],

            default => null,
        };
    }


        /* SUBBAB_4_1_ESELON_TEREDUKSI_METHOD_START */
    private function getSubbab41PracticeDefinition(string $practiceKey): ?array
    {
        return match ($practiceKey) {
            'aktivitas-4-1-eselon-tereduksi' => [
                'title' => 'Aktivitas 4.1 - Uji Visual Matriks Eselon Baris Tereduksi',
                'type' => 'aktivitas',
                'definition_version' => 'subbab41_eselon_tereduksi_v2',
                'max_score' => 100,
                'groups' => [
                    'pengelompokan_matriks' => [
                        'number' => 1,
                        'fields' => [
                            'matrix_a',
                            'matrix_b',
                            'matrix_c',
                            'matrix_d',
                            'matrix_e',
                        ],
                        'points' => 100,
                    ],
                ],
                'questions' => [
                    'matrix_a' => [
                        'accepted_answers' => ['tereduksi'],
                        'display_answer' => 'Zona Eselon Baris Tereduksi',
                        'feedback_correct' => 'Benar. Setiap kolom yang memuat 1 utama hanya memiliki elemen nol selain 1 utama tersebut.',
                        'feedback_wrong' => 'Belum tepat. Periksa setiap kolom yang memuat 1 utama, termasuk elemen yang berada di atasnya.',
                    ],
                    'matrix_b' => [
                        'accepted_answers' => ['eselon'],
                        'display_answer' => 'Zona Eselon Baris',
                        'feedback_correct' => 'Benar. Matriks sudah berbentuk eselon baris, tetapi belum tereduksi penuh.',
                        'feedback_wrong' => 'Belum tepat. Periksa kembali elemen bukan nol yang masih berada di atas 1 utama.',
                    ],
                    'matrix_c' => [
                        'accepted_answers' => ['eselon'],
                        'display_answer' => 'Zona Eselon Baris',
                        'feedback_correct' => 'Benar. Matriks sudah berbentuk eselon baris, tetapi belum tereduksi penuh.',
                        'feedback_wrong' => 'Belum tepat. Perhatikan apakah semua kolom yang memuat 1 utama sudah memiliki nol di bagian atasnya.',
                    ],
                    'matrix_d' => [
                        'accepted_answers' => ['eselon'],
                        'display_answer' => 'Zona Eselon Baris',
                        'feedback_correct' => 'Benar. Matriks sudah berbentuk eselon baris, tetapi belum tereduksi penuh.',
                        'feedback_wrong' => 'Belum tepat. Periksa kembali elemen selain 1 utama pada setiap kolom pivot.',
                    ],
                    'matrix_e' => [
                        'accepted_answers' => ['tereduksi'],
                        'display_answer' => 'Zona Eselon Baris Tereduksi',
                        'feedback_correct' => 'Benar. Matriks memenuhi syarat eselon baris tereduksi dan baris nol berada di bagian bawah.',
                        'feedback_wrong' => 'Belum tepat. Periksa posisi baris nol serta elemen pada kolom yang memuat 1 utama.',
                    ],
                ],
            ],
            default => null,
        };
    }
    /* SUBBAB_4_1_ESELON_TEREDUKSI_METHOD_END */


    /* SUBBAB_4_2_GAUSS_JORDAN_V1 */
    private function getSubbab42PracticeDefinition(string $practiceKey): ?array
    {
        $fractionAnswers = static function (string $value): array {
            $answers = [$value];

            if (str_contains($value, '/')) {
                [$numerator, $denominator] = explode('/', $value, 2);

                $answers[] = $numerator . ' per ' . $denominator;
                $answers[] = $numerator . 'per' . $denominator;
                $answers[] = '\\frac{' . $numerator . '}{' . $denominator . '}';
            }

            return array_values(array_unique($answers));
        };

        $valueQuestion = static function (string $value, string $feedback) use ($fractionAnswers): array {
            return [
                'accepted_answers' => $fractionAnswers($value),
                'display_answer' => $value,
                'feedback_correct' => 'Benar.',
                'feedback_wrong' => $feedback,
            ];
        };

        $decisionQuestion = static function (string $answer, string $description): array {
            return [
                'accepted_answers' => [$answer],
                'display_answer' => strtoupper($answer),
                'feedback_correct' => 'Benar. ' . $description,
                'feedback_wrong' => 'Belum tepat. Periksa kembali nilai elemen pada posisi yang ditanyakan.',
            ];
        };

        $operationQuestion = static function (array $accepted, string $display, string $feedback): array {
            return [
                'accepted_answers' => $accepted,
                'display_answer' => $display,
                'feedback_correct' => 'Benar. Notasi operasi sudah sesuai.',
                'feedback_wrong' => $feedback,
            ];
        };

        $operations = [
            'f5a' => [
                'notation' => 'f5a_notasi',
                'notation_answers' => [
                    'b3←5b4+b3', 'b_3←5b_4+b_3',
                    'b3<-5b4+b3', 'b_3<-5b_4+b_3',
                    'b3\\leftarrow5b4+b3', 'b_3\\leftarrow5b_4+b_3',
                ],
                'notation_display' => 'B_3 \leftarrow 5B_4 + B_3',
                'coefficient' => 'f5a_k',
                'coefficient_value' => '5',
                'product' => ['0', '0', '0', '5', '25/3'],
                'result' => ['0', '0', '1', '0', '4/3'],
                'decision' => 'f5_q1_baris3',
                'decision_answer' => 'tidak',
                'decision_description' => 'Elemen Baris-3 Kolom-4 bernilai -5 sehingga perlu dinolkan.',
                'feedback' => 'Periksa kembali operasi B3 ← 5B4 + B3.',
            ],
            'f5b' => [
                'notation' => 'f5b_notasi',
                'notation_answers' => [
                    'b1←b4+b1', 'b_1←b_4+b_1',
                    'b1<-b4+b1', 'b_1<-b_4+b_1',
                    'b1\\leftarrowb4+b1', 'b_1\\leftarrowb_4+b_1',
                ],
                'notation_display' => 'B_1 \leftarrow B_4 + B_1',
                'coefficient' => 'f5b_k',
                'coefficient_value' => '1',
                'product' => ['0', '0', '0', '1', '5/3'],
                'result' => ['1', '1', '2', '0', '20/3'],
                'decision' => 'f5_q3_baris1',
                'decision_answer' => 'tidak',
                'decision_description' => 'Elemen Baris-1 Kolom-4 bernilai -1 sehingga perlu dinolkan.',
                'feedback' => 'Periksa kembali operasi B1 ← B4 + B1.',
            ],
            'f6a' => [
                'notation' => 'f6a_notasi',
                'notation_answers' => [
                    'b2←-b3+b2', 'b_2←-b_3+b_2',
                    'b2<- -b3+b2', 'b_2<- -b_3+b_2',
                    'b2\\leftarrow-b3+b2', 'b_2\\leftarrow-b_3+b_2',
                ],
                'notation_display' => 'B_2 \leftarrow -B_3 + B_2',
                'coefficient' => 'f6a_k',
                'coefficient_value' => '-1',
                'product' => ['0', '0', '-1', '0', '-4/3'],
                'result' => ['0', '1', '0', '0', '11/3'],
                'decision' => 'f6_q1_baris2',
                'decision_answer' => 'tidak',
                'decision_description' => 'Elemen Baris-2 Kolom-3 bernilai 1 sehingga perlu dinolkan.',
                'feedback' => 'Periksa kembali operasi B2 ← -B3 + B2.',
            ],
            'f6b' => [
                'notation' => 'f6b_notasi',
                'notation_answers' => [
                    'b1←-2b3+b1', 'b_1←-2b_3+b_1',
                    'b1<- -2b3+b1', 'b_1<- -2b_3+b_1',
                    'b1\\leftarrow-2b3+b1', 'b_1\\leftarrow-2b_3+b_1',
                ],
                'notation_display' => 'B_1 \leftarrow -2B_3 + B_1',
                'coefficient' => 'f6b_k',
                'coefficient_value' => '-2',
                'product' => ['0', '0', '-2', '0', '-8/3'],
                'result' => ['1', '1', '0', '0', '4'],
                'decision' => 'f6_q2_baris1',
                'decision_answer' => 'tidak',
                'decision_description' => 'Elemen Baris-1 Kolom-3 bernilai 2 sehingga perlu dinolkan.',
                'feedback' => 'Periksa kembali operasi B1 ← -2B3 + B1.',
            ],
            'f7a' => [
                'notation' => 'f7a_notasi',
                'notation_answers' => [
                    'b1←-b2+b1', 'b_1←-b_2+b_1',
                    'b1<- -b2+b1', 'b_1<- -b_2+b_1',
                    'b1\\leftarrow-b2+b1', 'b_1\\leftarrow-b_2+b_1',
                ],
                'notation_display' => 'B_1 \leftarrow -B_2 + B_1',
                'coefficient' => 'f7a_k',
                'coefficient_value' => '-1',
                'product' => ['0', '-1', '0', '0', '-11/3'],
                'result' => ['1', '0', '0', '0', '1/3'],
                'decision' => 'f7_q1_baris1',
                'decision_answer' => 'tidak',
                'decision_description' => 'Elemen Baris-1 Kolom-2 bernilai 1 sehingga perlu dinolkan.',
                'feedback' => 'Periksa kembali operasi B1 ← -B2 + B1.',
            ],
        ];

        $questions = [
            'f5_q1_baris3' => $decisionQuestion(
                'tidak',
                'Elemen Baris-3 Kolom-4 bernilai -5 sehingga perlu dinolkan.'
            ),
            'f5_q2_baris2' => $decisionQuestion(
                'ya',
                'Elemen Baris-2 Kolom-4 sudah bernilai 0 sehingga tidak memerlukan operasi tambahan.'
            ),
            'f5_q3_baris1' => $decisionQuestion(
                'tidak',
                'Elemen Baris-1 Kolom-4 bernilai -1 sehingga perlu dinolkan.'
            ),
            'f6_q1_baris2' => $decisionQuestion(
                'tidak',
                'Elemen Baris-2 Kolom-3 bernilai 1 sehingga perlu dinolkan.'
            ),
            'f6_q2_baris1' => $decisionQuestion(
                'tidak',
                'Elemen Baris-1 Kolom-3 bernilai 2 sehingga perlu dinolkan.'
            ),
            'f7_q1_baris1' => $decisionQuestion(
                'tidak',
                'Elemen Baris-1 Kolom-2 bernilai 1 sehingga perlu dinolkan.'
            ),
        ];

        $phase5Fields = ['f5_q1_baris3', 'f5_q2_baris2', 'f5_q3_baris1'];
        $phase6Fields = ['f6_q1_baris2', 'f6_q2_baris1'];
        $phase7Fields = ['f7_q1_baris1'];

        foreach ($operations as $operationKey => $operation) {
            $questions[$operation['notation']] = $operationQuestion(
                $operation['notation_answers'],
                $operation['notation_display'],
                $operation['feedback']
            );

            $questions[$operation['coefficient']] = $valueQuestion(
                $operation['coefficient_value'],
                $operation['feedback']
            );
            foreach ($operation['product'] as $index => $value) {
                $field = $operationKey . '_produk_' . ($index + 1);
                $questions[$field] = $valueQuestion($value, $operation['feedback']);
            }

            foreach ($operation['result'] as $index => $value) {
                $field = $operationKey . '_hasil_' . ($index + 1);
                $questions[$field] = $valueQuestion($value, $operation['feedback']);
            }

            $fields = array_merge(
                [$operation['notation'], $operation['coefficient']],
                array_map(fn (int $index) => $operationKey . '_produk_' . $index, range(1, 5)),
                array_map(fn (int $index) => $operationKey . '_hasil_' . $index, range(1, 5))
            );

            if (str_starts_with($operationKey, 'f5')) {
                $phase5Fields = array_merge($phase5Fields, $fields);
            } elseif (str_starts_with($operationKey, 'f6')) {
                $phase6Fields = array_merge($phase6Fields, $fields);
            } else {
                $phase7Fields = array_merge($phase7Fields, $fields);
            }
        }

        $finalValues = [
            'final_11' => '1', 'final_12' => '0', 'final_13' => '0', 'final_14' => '0', 'final_15' => '1/3',
            'final_21' => '0', 'final_22' => '1', 'final_23' => '0', 'final_24' => '0', 'final_25' => '11/3',
            'final_31' => '0', 'final_32' => '0', 'final_33' => '1', 'final_34' => '0', 'final_35' => '4/3',
            'final_41' => '0', 'final_42' => '0', 'final_43' => '0', 'final_44' => '1', 'final_45' => '5/3',
        ];

        foreach ($finalValues as $field => $value) {
            $questions[$field] = $valueQuestion(
                $value,
                'Periksa kembali posisi 1 utama dan nilai pada kolom konstanta matriks akhir.'
            );
        }

        return match ($practiceKey) {
            'contoh-simulasi-4-2-eselon-baris-tereduksi' => [
                'title' => 'Contoh Simulasi 4.2 - Mengubah Matriks Menjadi Eselon Baris Tereduksi',
                'type' => 'contoh_simulasi',
                'definition_version' => 'subbab-4-2-gauss-jordan-v1',
                'max_score' => 0,
                'groups' => [
                    'fase_5' => [
                        'number' => 5,
                        'fields' => $phase5Fields,
                        'points' => 0,
                    ],
                    'fase_6' => [
                        'number' => 6,
                        'fields' => $phase6Fields,
                        'points' => 0,
                    ],
                    'fase_7' => [
                        'number' => 7,
                        'fields' => $phase7Fields,
                        'points' => 0,
                    ],
                    'output_matriks' => [
                        'number' => 8,
                        'fields' => array_keys($finalValues),
                        'points' => 0,
                    ],
                ],
                'questions' => $questions,
            ],

            default => null,
        };
    }


    /* SUBBAB_4_3_GAUSS_JORDAN_METHOD_START */
    private function getSubbab43PracticeDefinition(string $practiceKey): ?array
    {
        $fractionAnswers = static function (string $value): array {
            $answers = [$value];

            if (str_contains($value, '/')) {
                [$numerator, $denominator] = explode('/', $value, 2);

                $answers[] = $numerator . ' per ' . $denominator;
                $answers[] = $numerator . 'per' . $denominator;
                $answers[] = '\\frac{' . $numerator . '}{' . $denominator . '}';
            }

            return array_values(array_unique($answers));
        };

        $valueQuestion = static function (string $value, string $feedback) use ($fractionAnswers): array {
            return [
                'accepted_answers' => $fractionAnswers($value),
                'display_answer' => $value,
                'feedback_correct' => 'Benar.',
                'feedback_wrong' => $feedback,
            ];
        };

        $decisionQuestion = static function (string $answer, string $description): array {
            return [
                'accepted_answers' => [$answer],
                'display_answer' => strtoupper($answer),
                'feedback_correct' => 'Benar. ' . $description,
                'feedback_wrong' => 'Belum tepat. Periksa kembali nilai elemen pada posisi yang ditanyakan.',
            ];
        };

        $operationQuestion = static function (
            array $acceptedAnswers,
            string $displayAnswer,
            string $feedback
        ): array {
            return [
                'accepted_answers' => $acceptedAnswers,
                'display_answer' => $displayAnswer,
                'feedback_correct' => 'Benar. Notasi operasi sudah sesuai.',
                'feedback_wrong' => $feedback,
            ];
        };

        $operationAnswers = [
            'f4a_notasi' => [
                'b2←b3+b2',
                'b_2←b_3+b_2',
                'b2<-b3+b2',
                'b_2<-b_3+b_2',
                'b2\leftarrow b3+b2',
                'b_2\leftarrow b_3+b_2',
            ],
            'f4b_notasi' => [
                'b1←1/2b3+b1',
                'b_1←1/2b_3+b_1',
                'b1<-1/2b3+b1',
                'b_1<-1/2b_3+b_1',
                'b1\leftarrow \frac{1}{2}b3+b1',
                'b_1\leftarrow \frac{1}{2}b_3+b_1',
            ],
            'f5a_notasi' => [
                'b1←-b2+b1',
                'b_1←-b_2+b_1',
                'b1<- -b2+b1',
                'b_1<- -b_2+b_1',
                'b1\leftarrow -b2+b1',
                'b_1\leftarrow -b_2+b_1',
            ],
        ];

        $questions = [
            'cek43_b1_ruas_kanan' => $valueQuestion('1/3', 'Periksa kembali konstanta pada Baris-1.'),
            'cek43_b1_solusi' => $valueQuestion('1/3', 'Karena hanya x₁ yang tersisa pada Baris-1, nilainya sama dengan konstanta.'),
            'cek43_b2_ruas_kanan' => $valueQuestion('11/3', 'Periksa kembali konstanta pada Baris-2.'),
            'cek43_b2_solusi' => $valueQuestion('11/3', 'Karena hanya x₂ yang tersisa pada Baris-2, nilainya sama dengan konstanta.'),
            'cek43_b3_ruas_kanan' => $valueQuestion('4/3', 'Periksa kembali konstanta pada Baris-3.'),
            'cek43_b3_solusi' => $valueQuestion('4/3', 'Karena hanya x₃ yang tersisa pada Baris-3, nilainya sama dengan konstanta.'),
            'cek43_b4_ruas_kanan' => $valueQuestion('5/3', 'Periksa kembali konstanta pada Baris-4.'),
            'cek43_b4_solusi' => $valueQuestion('5/3', 'Karena hanya x₄ yang tersisa pada Baris-4, nilainya sama dengan konstanta.'),

            'f4_q1_baris2' => $decisionQuestion(
                'tidak',
                'Elemen Baris-2 Kolom-3 bernilai -1 sehingga perlu dinolkan.'
            ),
            'f4_q2_baris1' => $decisionQuestion(
                'tidak',
                'Elemen Baris-1 Kolom-3 bernilai -1/2 sehingga perlu dinolkan.'
            ),
            'f5_q1_baris1' => $decisionQuestion(
                'tidak',
                'Elemen Baris-1 Kolom-2 bernilai 1 sehingga perlu dinolkan.'
            ),
        ];

        $operations = [
            'f4a' => [
                'notation' => 'f4a_notasi',
                'notation_display' => 'B_2 \leftarrow B_3 + B_2',
                'coefficient' => 'f4a_k',
                'coefficient_value' => '1',
                'product' => ['0', '0', '1', '1'],
                'result' => ['0', '1', '0', '0'],
                'feedback' => 'Periksa kembali operasi B₂ ← B₃ + B₂.',
            ],
            'f4b' => [
                'notation' => 'f4b_notasi',
                'notation_display' => 'B_1 \leftarrow \frac{1}{2}B_3 + B_1',
                'coefficient' => 'f4b_k',
                'coefficient_value' => '1/2',
                'product' => ['0', '0', '1/2', '1/2'],
                'result' => ['1', '1', '0', '5/2'],
                'feedback' => 'Periksa kembali operasi B₁ ← 1/2 B₃ + B₁.',
            ],
            'f5a' => [
                'notation' => 'f5a_notasi',
                'notation_display' => 'B_1 \leftarrow -B_2 + B_1',
                'coefficient' => 'f5a_k',
                'coefficient_value' => '-1',
                'product' => ['0', '-1', '0', '0'],
                'result' => ['1', '0', '0', '5/2'],
                'feedback' => 'Periksa kembali operasi B₁ ← -B₂ + B₁.',
            ],
        ];

        $fase4Fields = ['f4_q1_baris2', 'f4_q2_baris1'];
        $fase5Fields = ['f5_q1_baris1'];

        foreach ($operations as $operationKey => $operation) {
            $questions[$operation['notation']] = $operationQuestion(
                $operationAnswers[$operation['notation']],
                $operation['notation_display'],
                $operation['feedback']
            );

            $questions[$operation['coefficient']] = $valueQuestion(
                $operation['coefficient_value'],
                $operation['feedback']
            );

            foreach ($operation['product'] as $index => $value) {
                $questions[$operationKey . '_produk_' . ($index + 1)] = $valueQuestion(
                    $value,
                    $operation['feedback']
                );
            }

            foreach ($operation['result'] as $index => $value) {
                $questions[$operationKey . '_hasil_' . ($index + 1)] = $valueQuestion(
                    $value,
                    $operation['feedback']
                );
            }

            $fields = array_merge(
                [$operation['notation'], $operation['coefficient']],
                array_map(fn (int $index) => $operationKey . '_produk_' . $index, range(1, 4)),
                array_map(fn (int $index) => $operationKey . '_hasil_' . $index, range(1, 4))
            );

            if (str_starts_with($operationKey, 'f4')) {
                $fase4Fields = array_merge($fase4Fields, $fields);
            } else {
                $fase5Fields = array_merge($fase5Fields, $fields);
            }
        }

        $finalValues = [
            'f6_final_11' => '1',
            'f6_final_12' => '0',
            'f6_final_13' => '0',
            'f6_final_14' => '5/2',
            'f6_final_21' => '0',
            'f6_final_22' => '1',
            'f6_final_23' => '0',
            'f6_final_24' => '0',
            'f6_final_31' => '0',
            'f6_final_32' => '0',
            'f6_final_33' => '1',
            'f6_final_34' => '1',
            'f6_solusi_x' => '5/2',
            'f6_solusi_y' => '0',
            'f6_solusi_z' => '1',
        ];

        foreach ($finalValues as $field => $value) {
            $questions[$field] = $valueQuestion(
                $value,
                'Periksa kembali matriks eselon baris tereduksi final dan nilai solusi variabel.'
            );
        }

        return match ($practiceKey) {
            'cek-pemahaman-4-3-membaca-rref' => [
                'title' => 'Cek Pemahaman 4.3 - Membaca Solusi dari Matriks Eselon Baris Tereduksi',
                'type' => 'cek_pemahaman',
                'definition_version' => 'subbab-4-3-gauss-jordan-v1',
                'max_score' => 0,
                'groups' => [
                    'membaca_solusi' => [
                        'number' => 1,
                        'fields' => [
                            'cek43_b1_ruas_kanan', 'cek43_b1_solusi',
                            'cek43_b2_ruas_kanan', 'cek43_b2_solusi',
                            'cek43_b3_ruas_kanan', 'cek43_b3_solusi',
                            'cek43_b4_ruas_kanan', 'cek43_b4_solusi',
                        ],
                        'points' => 0,
                    ],
                ],
                'questions' => $questions,
            ],

            'aktivitas-4-2-gauss-jordan' => [
                'title' => 'Aktivitas 4.2 - Menyelesaikan SPL dengan Metode Eliminasi Gauss-Jordan',
                'type' => 'aktivitas',
                'definition_version' => 'subbab-4-3-gauss-jordan-v1',
                'max_score' => 100,
                'groups' => [
                    'fase_4' => [
                        'number' => 4,
                        'fields' => $fase4Fields,
                        'points' => 40,
                    ],
                    'fase_5' => [
                        'number' => 5,
                        'fields' => $fase5Fields,
                        'points' => 30,
                    ],
                    'fase_6' => [
                        'number' => 6,
                        'fields' => array_keys($finalValues),
                        'points' => 30,
                    ],
                ],
                'questions' => $questions,
            ],

            default => null,
        };
    }
    /* SUBBAB_4_3_GAUSS_JORDAN_METHOD_END */
}
