<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAppearanceSettingsRequest;
use App\Http\Requests\UpdateGeneralSettingsRequest;
use App\Models\Setting;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function general(): View
    {
        $settings = Setting::getMany([
            'general.site_name',
            'general.support_email',
            'general.support_phone',
        ]);

        return view('admin.settings.general.index', compact('settings'));
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Setting::setValue('general.site_name', $data['site_name']);
        Setting::setValue('general.support_email', $data['support_email'] ?? null);
        Setting::setValue('general.support_phone', $data['support_phone'] ?? null);

        ActivityLogger::log('updated', 'settings', 0, ['section' => 'general']);

        return redirect()
            ->route('admin.settings.general')
            ->with('success', __('Settings updated successfully.'));
    }

    public function appearance(): View
    {
        $settings = Setting::getMany([
            'appearance.theme',
            'appearance.rtl',
        ]);

        return view('admin.settings.appearance.index', compact('settings'));
    }

    public function updateAppearance(UpdateAppearanceSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Setting::setValue('appearance.theme', $data['theme']);
        Setting::setValue('appearance.rtl', $data['rtl'] ? '1' : '0');

        ActivityLogger::log('updated', 'settings', 0, ['section' => 'appearance']);

        return redirect()
            ->route('admin.settings.appearance')
            ->with('success', __('Appearance settings updated successfully.'));
    }
}
