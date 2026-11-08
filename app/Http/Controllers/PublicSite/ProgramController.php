<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\AgendaItem;
use App\Models\Person;
use App\Models\ProgramStage;
use App\Support\Settings;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function stages(): View
    {
        $stages = ProgramStage::query()->public()->get();

        return view('public.program.stages', [
            'phases' => $stages->whereNotNull('phase_number')->groupBy('phase_number'),
            'crossCutting' => $stages->whereNull('phase_number'),
        ]);
    }

    public function timeline(): View
    {
        return view('public.program.timeline', [
            'stages' => ProgramStage::query()->public()->get(),
            'milestones' => AgendaItem::query()->public()->with('stage')->ordered()->get(),
            'openCall' => Settings::openCallState(),
        ]);
    }

    public function agenda(): View
    {
        $items = AgendaItem::query()->public()->with('stage')->ordered()->get();

        return view('public.program.agenda', [
            'groups' => $items->groupBy(fn ($item) => $item->stage?->name ?? 'Kegiatan lain'),
            'stages' => ProgramStage::query()->public()->get()->keyBy('name'),
        ]);
    }

    public function people(): View
    {
        $people = Person::query()->public()->with('theme')->get();

        return view('public.program.people', [
            'groups' => $people->groupBy('type'),
        ]);
    }
}
