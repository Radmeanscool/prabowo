<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['settings' => Settings::all()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_full_name' => ['required', 'string', 'max:190'],
            'tagline' => ['required', 'string', 'max:190'],
            'tagline_en' => ['nullable', 'string', 'max:190'],
            'spirit' => ['nullable', 'string', 'max:190'],
            'event_period' => ['nullable', 'string', 'max:120'],
            'event_location' => ['nullable', 'string', 'max:190'],
            'hero_intro' => ['nullable', 'string', 'max:600'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:60'],
            'contact_address' => ['nullable', 'string', 'max:300'],
            'open_call_mode' => ['required', Rule::in(['auto', 'open', 'closed'])],
            'open_call_start_at' => ['nullable', 'date'],
            'open_call_end_at' => ['nullable', 'date', 'after:open_call_start_at'],
            'max_team_size' => ['required', 'integer', 'between:1,20'],
            'min_team_size' => ['required', 'integer', 'between:1,20', 'lte:max_team_size'],
            'max_proposals_per_team' => ['required', 'integer', 'between:1,10'],
            'proposal_word_limit' => ['required', 'integer', 'between:50,2000'],
            'max_pdf_size_mb' => ['required', 'integer', 'between:1,64'],
            'max_image_size_mb' => ['required', 'integer', 'between:1,20'],
            'stats' => ['nullable', 'array', 'max:8'],
            'stats.*.value' => ['nullable', 'string', 'max:20'],
            'stats.*.label' => ['nullable', 'string', 'max:60'],
            'stats.*.note' => ['nullable', 'string', 'max:120'],
        ]);

        $values = collect($data)->except('stats')->all();
        foreach (Settings::booleanKeys() as $key) {
            $values[$key] = $request->boolean($key);
        }
        foreach (Settings::integerKeys() as $key) {
            $values[$key] = (int) $values[$key];
        }
        $values['homepage_stats'] = collect($data['stats'] ?? [])
            ->filter(fn ($row) => filled($row['value'] ?? null) && filled($row['label'] ?? null))
            ->map(fn ($row) => ['value' => $row['value'], 'label' => $row['label'], 'note' => $row['note'] ?? ''])
            ->values()->all();

        $before = Settings::all();
        Settings::set($values);
        $changed = collect($values)->filter(fn ($v, $k) => ($before[$k] ?? null) !== $v);
        if ($changed->isNotEmpty()) {
            Audit::log('setting.updated', null, collect($before)->only($changed->keys())->all(), $changed->all(), 'Pengaturan diperbarui');
        }

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
