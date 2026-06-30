@props([
    'matrix' => [],
    'label' => 'Matriks',
    'separatorBefore' => null,
    'emptyMessage' => 'Matriks belum diisi.',
])

@php
    $rows = collect(is_array($matrix) ? $matrix : [])
        ->map(fn ($row) => is_array($row) ? array_values($row) : [$row])
        ->values();

    $columns = max(1, (int) $rows->map(fn ($row) => count($row))->max());

    $hasValue = $rows->isNotEmpty()
        && $rows->flatten()->contains(fn ($cell) => trim((string) $cell) !== '');
@endphp

<div class="overflow-x-auto rounded-2xl border border-slate-300 bg-slate-50 p-4">
    <p class="mb-3 text-center text-sm font-black text-slate-700">{{ $label }}</p>

    @if ($hasValue)
        <div class="mx-auto grid w-max gap-2" style="grid-template-columns: repeat({{ $columns }}, minmax(58px, auto));">
            @foreach ($rows as $row)
                @for ($columnIndex = 0; $columnIndex < $columns; $columnIndex++)
                    @php
                        $cell = $row[$columnIndex] ?? '';
                        $separator = $separatorBefore && ((int) $columnIndex + 1) === (int) $separatorBefore;
                    @endphp
                    <div class="flex min-h-11 min-w-[58px] items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-center text-sm font-black text-slate-900 {{ $separator ? 'border-l-4 border-l-slate-700' : '' }}">
                        {{ trim((string) $cell) !== '' ? $cell : '–' }}
                    </div>
                @endfor
            @endforeach
        </div>
    @else
        <div class="rounded-xl border border-dashed border-slate-300 px-4 py-5 text-center text-sm text-slate-500">
            {{ $emptyMessage }}
        </div>
    @endif
</div>
