<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Filament handles all routing via AdminPanelProvider (path: '/').
| Auth routes are managed by Filament's built-in authentication.
| 
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// Custom Login Routes
Route::get('/login', function () {
    // Jika sudah login, redirect ke panel (path '/')
    if (Auth::check()) {
        return redirect('/');
    }
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);
 
    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended('/');
    }
 
    return back()->withErrors([
        'email' => 'Kredensial email atau password salah.',
    ])->onlyInput('email');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('filament.admin.auth.logout');
