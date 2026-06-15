<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\EmailAccount;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        if (!$tenant) {
            return redirect()->route('client.login')->withErrors([
                'email' => 'No tenant assigned to this account.',
            ]);
        }

        $accounts = EmailAccount::query()
            ->with('domain')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->get();

        $primaryAccount = $accounts->first();

        return view('client.mail.xmail', compact('tenant', 'accounts', 'primaryAccount'));
    }
}
