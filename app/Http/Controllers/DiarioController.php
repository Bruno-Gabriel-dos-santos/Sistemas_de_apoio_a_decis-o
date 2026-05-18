<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DiarioController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $year = (int) ($request->query('ano', $now->year));
        $month = (int) ($request->query('mes', $now->month));

        try {
            $currentMonth = Carbon::createFromDate($year, $month, 1)->startOfDay();
        } catch (\Throwable $e) {
            abort(404, 'Mês inválido.');
        }

        $startCalendar = $currentMonth->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $endCalendar = $currentMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $weeks = [];
        $cursor = $startCalendar->copy();
        $disk = Storage::disk('local');

        while ($cursor <= $endCalendar) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateClone = $cursor->copy();
                $week[] = [
                    'date' => $dateClone,
                    'isCurrentMonth' => $dateClone->month === $currentMonth->month,
                    'isToday' => $dateClone->isToday(),
                    'hasEntry' => $disk->exists($this->getEntryPath($dateClone)),
                    'route' => route('diario.show', $dateClone->format('Y-m-d')),
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return view('diario.index', [
            'currentMonth' => $currentMonth,
            'weeks' => $weeks,
            'prevMonth' => $currentMonth->copy()->subMonth(),
            'nextMonth' => $currentMonth->copy()->addMonth(),
        ]);
    }

    public function show(string $date)
    {
        $day = $this->parseDate($date);
        $disk = Storage::disk('local');
        $path = $this->getEntryPath($day);
        $content = $disk->exists($path) ? $disk->get($path) : '';

        return view('diario.show', [
            'selectedDate' => $day,
            'content' => $content,
        ]);
    }

    public function save(Request $request, string $date)
    {
        $day = $this->parseDate($date);
        $data = $request->validate([
            'conteudo' => 'nullable|string',
        ]);

        $path = $this->getEntryPath($day);
        $disk = Storage::disk('local');

        $directory = dirname($path);
        if ($directory !== '.' && !$disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $disk->put($path, $data['conteudo'] ?? '');

        return redirect()
            ->route('diario.show', $day->format('Y-m-d'))
            ->with('success', 'Anotação salva com sucesso!');
    }

    protected function parseDate(string $date): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Throwable $e) {
            abort(404, 'Dia inválido.');
        }
    }

    protected function getEntryPath(Carbon $date): string
    {
        return 'diario/' . $date->format('Y/m/d') . '.md';
    }
}


