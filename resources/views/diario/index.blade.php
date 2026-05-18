@extends('layouts.app')

@section('content')
@php
    $monthLabel = \Illuminate\Support\Str::ucfirst($currentMonth->locale('pt_BR')->translatedFormat('F \\d\\e Y'));
@endphp
<div class="max-w-5xl mx-auto mt-6 space-y-6">
    <div class="flex items-center justify-between bg-white shadow rounded-lg px-6 py-4">
        <a href="{{ route('diario.index', ['ano' => $prevMonth->year, 'mes' => $prevMonth->month]) }}"
           class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-2">
            <i class="fa fa-chevron-left"></i> Mês anterior
        </a>
        <div class="text-2xl font-bold text-gray-800">{{ $monthLabel }}</div>
        <a href="{{ route('diario.index', ['ano' => $nextMonth->year, 'mes' => $nextMonth->month]) }}"
           class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-2">
            Próximo mês <i class="fa fa-chevron-right"></i>
        </a>
    </div>

    <div class="bg-white shadow rounded-xl p-6">
        <div class="grid grid-cols-7 gap-3 text-center text-sm font-semibold text-gray-500 uppercase tracking-wide">
            @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $weekday)
                <div>{{ $weekday }}</div>
            @endforeach
        </div>

        <div class="mt-4 grid grid-cols-7 gap-3 text-center">
            @foreach ($weeks as $week)
                @foreach ($week as $day)
                    @php
                        $classes = 'rounded-lg p-3 border flex flex-col items-center justify-center gap-1 transition hover:shadow';
                        $classes .= $day['isCurrentMonth'] ? ' bg-white border-gray-200' : ' bg-gray-100 border-gray-200 text-gray-400';
                        if ($day['isToday']) {
                            $classes .= ' ring-2 ring-indigo-500';
                        }
                    @endphp
                    <a href="{{ $day['route'] }}" class="{{ $classes }}">
                        <span class="text-lg font-semibold">{{ $day['date']->format('d') }}</span>
                        <span class="text-xs text-gray-500">{{ $day['date']->locale('pt_BR')->translatedFormat('D') }}</span>
                        @if ($day['hasEntry'])
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        @endif
                    </a>
                @endforeach
            @endforeach
        </div>

        <div class="mt-6 flex items-center gap-6 text-sm text-gray-500">
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-emerald-500"></span>
                <span>Dia com anotação salva</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full border border-indigo-500"></span>
                <span>Hoje</span>
            </div>
        </div>
    </div>
</div>
@endsection

