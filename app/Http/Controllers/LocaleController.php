<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class LocaleController extends Controller
{
    public function switch(string $locale)
    {
        if (in_array($locale, ['en', 'ja'])) {
            session(['locale' => $locale]);
            App::setLocale($locale);

            if (Auth::check()) {
                $user = Auth::user();
                $user->locale = $locale;
                $user->save();
            }
        }

        return back();
    }
}
