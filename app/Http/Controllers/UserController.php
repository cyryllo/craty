<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('users.form', ['user' => new User]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'magazynier', 'podglad'])],
            'password' => ['required', Password::defaults()],
        ]);

        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return redirect()->route('users.index')->with('status', 'Konto utworzone.');
    }

    public function edit(User $user)
    {
        return view('users.form', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'role' => ['required', Rule::in(['admin', 'magazynier', 'podglad'])],
            'active' => ['boolean'],
            'password' => ['nullable', Password::defaults()],
        ]);

        $data['active'] = $request->boolean('active');

        // Głównego admina nie da się zdegradować ani wyłączyć — inaczej dałoby
        // się w ten sposób obejść ochronę przed usunięciem i zablokować
        // wszystkim dostęp do panelu administracyjnego.
        if ($user->isProtected()) {
            $data['role'] = 'admin';
            $data['active'] = true;
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', 'Konto zaktualizowane.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Nie możesz usunąć własnego konta.');
        }

        if ($user->isProtected()) {
            return back()->with('error', 'Nie można usunąć głównego konta administratora.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Konto usunięte.');
    }
}
