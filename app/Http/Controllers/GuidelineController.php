<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\ChallengeCatalog;

/**
 * The rules a trader is held to, inside the dashboard. Same source as the
 * public Trading Rules page — the Challenge Plan Builder for targets and
 * limits, settings for the prohibited/allowed copy — so the two can never
 * drift apart and a trader is never shown rules they didn't buy into.
 */
class GuidelineController extends Controller
{
    public function index()
    {
        return view('dashboard.guideline', [
            'types' => ChallengeCatalog::availableTypes(),
            'plansByType' => ChallengeCatalog::plansByType(),
            'prohibited' => Setting::get('prohibited_rules', []),
            'allowed' => Setting::get('allowed_rules', []),
        ]);
    }
}
