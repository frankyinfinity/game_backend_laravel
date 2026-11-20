<?php

namespace App\Http\Controllers;

use App\Models\Score;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * List all settings.
     */
    public function index()
    {
        $settings = Setting::query()->orderBy('id')->get();
        $scores = Score::query()->with('groupScore')->orderBy('name')->get();

        return view('settings.index', compact('settings', 'scores'));
    }

    /**
     * Update all setting values from a single form.
     * Receives settings[<id>] = <value>.
     */
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string',
        ]);

        foreach ($request->input('settings') as $id => $value) {
            Setting::query()->whereKey($id)->update(['value' => $value]);
        }

        return redirect()->route('settings.index')->with('success', 'Impostazioni aggiornate.');
    }
}
