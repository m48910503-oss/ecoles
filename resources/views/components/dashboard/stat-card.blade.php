@props([
    'label',
    'value',
    'caption',
    'icon' => 'fa-chart-line',
    'eyebrow' => 'Signal',
    'tone' => 'violet',
])

@php
    $tones = [
        'violet' => [
            'accent' => 'from-violet-500/25 via-violet-500/10 to-transparent',
            'icon' => 'bg-violet-500/10 text-violet-300 border-violet-400/20',
            'badge' => 'bg-violet-500/10 text-violet-300 border-violet-400/20',
            'shadow' => 'hover:shadow-violet-500/20',
            'border' => 'hover:border-violet-400/40',
        ],
        'emerald' => [
            'accent' => 'from-emerald-400/20 via-emerald-400/10 to-transparent',
            'icon' => 'bg-emerald-400/10 text-emerald-300 border-emerald-300/20',
            'badge' => 'bg-emerald-400/10 text-emerald-300 border-emerald-300/20',
            'shadow' => 'hover:shadow-emerald-400/20',
            'border' => 'hover:border-emerald-300/40',
        ],
        'cyan' => [
            'accent' => 'from-cyan-400/20 via-cyan-400/10 to-transparent',
            'icon' => 'bg-cyan-400/10 text-cyan-300 border-cyan-300/20',
            'badge' => 'bg-cyan-400/10 text-cyan-300 border-cyan-300/20',
            'shadow' => 'hover:shadow-cyan-400/20',
            'border' => 'hover:border-cyan-300/40',
        ],
        'amber' => [
            'accent' => 'from-amber-400/20 via-amber-400/10 to-transparent',
            'icon' => 'bg-amber-400/10 text-amber-200 border-amber-300/20',
            'badge' => 'bg-amber-400/10 text-amber-200 border-amber-300/20',
            'shadow' => 'hover:shadow-amber-400/20',
            'border' => 'hover:border-amber-300/40',
        ],
    ];

    $toneClasses = $tones[$tone] ?? $tones['violet'];
@endphp

<article class="group relative overflow-hidden rounded-[1.75rem] border border-slate-700/50 bg-slate-800/50 p-6 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:scale-[1.01] hover:shadow-lg {{ $toneClasses['shadow'] }} {{ $toneClasses['border'] }}">
    <div class="absolute inset-0 bg-gradient-to-br {{ $toneClasses['accent'] }} opacity-80"></div>
    <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-white/5 blur-2xl transition-all duration-300 group-hover:scale-110"></div>

    <div class="relative">
        <div class="flex items-start justify-between gap-4">
            <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.28em] {{ $toneClasses['badge'] }} [font-family:'JetBrains_Mono',monospace]">
                <i class="fa-solid fa-wave-square text-[0.7rem] transition-transform duration-300 group-hover:rotate-12"></i>
                {{ $eyebrow }}
            </span>

            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border transition-all duration-300 group-hover:scale-110 {{ $toneClasses['icon'] }}">
                <i class="fa-solid {{ $icon }} text-lg transition-transform duration-300 group-hover:-rotate-6"></i>
            </span>
        </div>

        <div class="mt-8">
            <p class="text-sm font-medium uppercase tracking-[0.18em] text-slate-400">{{ $label }}</p>
            <p class="mt-4 text-4xl font-bold leading-none tracking-tight text-white md:text-5xl [font-family:'Syne',sans-serif]">{{ $value }}</p>
            <p class="mt-4 text-base leading-relaxed text-slate-300">{{ $caption }}</p>
        </div>
    </div>
</article>
