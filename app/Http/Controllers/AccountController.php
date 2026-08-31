<?php

namespace App\Http\Controllers;

use App\Models\TradingAccount;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request, TradingAccount $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        $account->load('challengePlan');

        $snapshots = $account->equitySnapshots()
            ->orderBy('snapshot_at')
            ->get(['equity', 'balance', 'snapshot_at']);

        return view('dashboard.account', [
            'account' => $account,
            'snapshots' => $snapshots,
        ]);
    }
}
