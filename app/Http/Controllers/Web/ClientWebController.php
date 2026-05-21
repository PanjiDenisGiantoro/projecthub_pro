<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ClientWebController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasRole('admin') || auth()->user()->hasRole('manager'), 403);

        $clients = User::role('customer')
            ->with('roles')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->status !== null && $request->status !== '', fn($q) => $q->where('is_active', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('clients.index', compact('clients'));
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

        $client = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'is_active' => $request->boolean('is_active', true),
            'timezone'  => 'Asia/Jakarta',
        ]);
        $client->assignRole('customer');

        return redirect()->route('clients.index')->with('success', 'Client berhasil ditambahkan.');
    }

    public function edit(User $client)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        abort_unless($client->hasRole('customer'), 404);
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, User $client)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        abort_unless($client->hasRole('customer'), 404);

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
        abort_unless($client->hasRole('customer'), 404);

        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client dihapus.');
    }
}
