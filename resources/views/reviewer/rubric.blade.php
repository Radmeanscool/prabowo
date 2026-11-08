<x-layouts.portal title="Rubrik Penilaian">
    <x-page-header title="Rubrik penilaian" subtitle="Setiap kriteria dinilai terpisah (1 sampai skor maksimum). Skor akhir 0–100 = Σ(skor ÷ maks × bobot) ÷ Σbobot × 100, dihitung otomatis oleh sistem." />
    @foreach ($phases as $phase)
        <section class="card mb-6">
            <div class="card-body border-b border-navy-100">
                <h2 class="card-title">{{ $phase->name }}</h2>
                <p class="mt-1 text-sm text-navy-600">{{ $phase->description }}</p>
                @if ($phase->ends_at)<p class="mt-1 text-xs font-semibold text-signal-700">Batas penilaian: {{ fmt_date($phase->ends_at, true) }}</p>@endif
            </div>
            <div class="table-wrap rounded-none border-0">
                <table class="table">
                    <thead><tr><th>Kriteria</th><th>Panduan penilaian</th><th>Maks</th><th>Bobot</th></tr></thead>
                    <tbody>
                        @foreach ($phase->criteria as $criterion)
                            <tr>
                                <td class="min-w-48 font-semibold">{{ $criterion->name }} @if ($criterion->theme)<x-theme-badge :theme="$criterion->theme" :icon="false" class="ml-1" />@endif @if ($criterion->requires_comment)<x-badge tone="warning" class="ml-1">Wajib komentar</x-badge>@endif</td>
                                <td class="min-w-64 text-navy-600">{{ $criterion->description }}</td>
                                <td class="tabular-nums">{{ $criterion->max_score }}</td>
                                <td class="tabular-nums">{{ rtrim(rtrim(number_format((float) $criterion->weight, 2, ',', '.'), '0'), ',') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</x-layouts.portal>
