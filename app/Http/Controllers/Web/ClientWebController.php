<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ClientWebController extends Controller
{
    use HasPerPage;

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('access clients'), 403);

        $companyId = auth()->user()->company_id;

        $clients = User::role('client')
            ->with('roles', 'clientProjects:id,name,client_id,status')
            ->where('company_id', $companyId)
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->status !== null && $request->status !== '', fn($q) => $q->where('is_active', $request->status))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $totalClients    = User::role('client')->where('company_id', $companyId)->count();
        $activeClients   = User::role('client')->where('company_id', $companyId)->where('is_active', true)->count();
        $inactiveClients = User::role('client')->where('company_id', $companyId)->where('is_active', false)->count();

        return view('clients.index', compact('clients', 'totalClients', 'activeClients', 'inactiveClients'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        return view('clients.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $sendVerification = $request->boolean('send_verification');

        $client = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => $request->password,
            'company_id'        => auth()->user()->company_id,
            'is_active'         => $request->boolean('is_active', true),
            'timezone'          => 'Asia/Jakarta',
            // Default: admin yang buat & memberi tahu kredensialnya langsung ke client,
            // jadi tidak perlu klik link verifikasi email lagi. Bisa diminta verifikasi
            // asli lewat checkbox "send_verification" di form.
            'email_verified_at' => $sendVerification ? null : now(),
        ]);
        $client->assignRole('client');

        if ($sendVerification) {
            $client->sendEmailVerificationNotification();
        }

        return redirect()->route('clients.index')->with('success', 'Client berhasil ditambahkan.');
    }

    public function edit(User $client)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        abort_unless($client->hasRole('client'), 404);
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, User $client)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        abort_unless($client->hasRole('client'), 404);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $client->id,
            'password' => 'nullable|min:8|confirmed',
        ]);

        $client->update(array_filter([
            'name'      => $request->name,
            'email'     => $request->email,
            'is_active' => $request->boolean('is_active'),
            'password'  => $request->filled('password') ? $request->password : null,
        ], fn($v) => $v !== null));

        return redirect()->route('clients.index')->with('success', 'Data client diperbarui.');
    }

    public function destroy(User $client)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        abort_unless($client->hasRole('client'), 404);

        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client dihapus.');
    }
}
