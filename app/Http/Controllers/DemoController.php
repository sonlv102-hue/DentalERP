<?php

namespace App\Http\Controllers;

use Database\Seeders\DemoSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DemoController extends Controller
{
    /**
     * Wipes and reseeds the SQLite database with the standard demo dataset,
     * then signs the current user out (their session row is gone anyway).
     */
    public function reset(): RedirectResponse
    {
        if (DB::getDriverName() !== 'sqlite') {
            abort(403, 'Khôi phục dữ liệu demo chỉ khả dụng với SQLite.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Đã khôi phục dữ liệu demo về mặc định. Đăng nhập lại với demo@dentalerp.test / Demo@12345.');
    }
}
