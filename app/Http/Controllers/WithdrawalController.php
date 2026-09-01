<?php

namespace App\Http\Controllers;

use App\Models\TradingAccount;
use App\Services\Withdrawals\WithdrawalService;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function index(Request $request, WithdrawalService $service)
    {
        $user = $request->user();

        $accounts = $user->tradingAccounts()->where('status', 'funded')->get();
        $withdrawals = $user->withdrawals()->latest()->get();

        return view('dashboard.withdrawal', [
            'accounts' => $accounts,
            'withdrawals' => $withdrawals,
            'service' => $service,
        ]);
    }

    public function store(Request $request, WithdrawalService $service)
    {
        $data = $request->validate([
            'trading_account_id' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string',
            'wallet_address' => 'required|string|max:255',
            'network' => 'nullable|string|max:60',
        ]);

        $account = TradingAccount::findOrFail($data['trading_account_id']);

        $service->request(
            user: $request->user(),
            account: $account,
            amount: (float) $data['amount'],
            method: $data['method'],
            wallet: $data['wallet_address'],
            network: $data['network'] ?? null,
        );

        return back()->with('status', 'Withdrawal requested — pending review.');
    }
}
