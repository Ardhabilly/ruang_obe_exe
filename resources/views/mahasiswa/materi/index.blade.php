<x-app-layout>
    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-8">
            <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 backdrop-blur-xl">
                <div class="absolute right-[-100px] top-[-100px] h-72 w-72 rounded-full bg-cyan-400/10 blur-3xl"></div>

                <div class="relative grid gap-6 lg:grid-cols-[1fr_320px] lg:items-center">
                    <div>
                        <p class="text-sm font-semibold text-cyan-200">
                            Materi Pembelajaran
                        </p>

                        <h1 class="mt-2 text-4xl font-black tracking-tight text-white">
                            Aljabar Linear RuangOBE
                        </h1>

                        <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-400">
                            Materi disusun secara bertahap mulai dari Sistem Persamaan Linear,
                            Operasi Baris Elementer, Eliminasi Gauss, hingga Eliminasi Gauss-Jordan.
                        </p>
                    </div>

                    <div class="rounded-[1.5rem] border border-white/10 bg-slate-950/40 p-6">
                        <p class="text-sm font-semibold text-slate-400">Progress Materi</p>

                        <div class="mt-4 flex items-end gap-2">
                            <span class="text-5xl font-black text-white">
                                {{ $progressPercentage }}
                            </span>
                            <span class="pb-2 text-sm font-bold text-slate-400">%</span>
                        </div>

                        <div class="mt-5 h-3 overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-gradient-to-r from-cyan-300 to-blue-500"
                                 style="width: {{ $progressPercentage }}%"></div>
                        </div>

                        <p class="mt-4 text-sm text-slate-400">
                            {{ $completedLessons }} dari {{ $totalLessons }} materi selesai.
                        </p>
                    </div>
                </div>
            </section>

            @foreach ($courses as $course)
                <section class="space-y-5">
                    @foreach ($course->modules as $module)
                        <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-cyan-200">
                                        {{ $course->title }}
                                    </p>

                                    <h2 class="mt-1 text-2xl font-black text-white">
                                        {{ $module->title }}
                                    </h2>

                                    <p class="mt-2 text-sm leading-6 text-slate-400">
                                        {{ $module->description }}
                                    </p>
                                </div>

                                <span class="w-fit rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-sm font-bold text-cyan-200">
                                    {{ $module->lessons->count() }} Subbab
                                </span>
                            </div>

                            <div class="mt-6 grid gap-4 md:grid-cols-2">
                                @foreach ($module->lessons as $lesson)
                                    @php
                                        $isCompleted = in_array($lesson->id, $completedLessonIds);
                                    @endphp

                                    <a href="{{ route('mahasiswa.materi.show', $lesson->slug) }}"
                                       class="group rounded-2xl border border-white/10 bg-slate-950/40 p-5 transition hover:border-cyan-300/30 hover:bg-cyan-400/10">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <h3 class="font-bold text-white group-hover:text-cyan-200">
                                                    {{ $lesson->title }}
                                                </h3>

                                                <p class="mt-2 text-sm leading-6 text-slate-400">
                                                    {{ $lesson->learning_outcome }}
                                                </p>
                                            </div>

                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-black
                                                {{ $isCompleted ? 'bg-green-400/10 text-green-200' : 'bg-white/5 text-slate-400' }}">
                                                {{ $isCompleted ? '✓' : $lesson->order_number }}
                                            </span>
                                        </div>

                                        <div class="mt-4 text-sm font-semibold text-cyan-200">
                                            Buka Materi →
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>