<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SimpleModeController extends Controller
{
    public function toggle(Request $request)
    {
        $currentMode = $request->session()->get('clearstay_simple_mode', false);
        $newMode = !$currentMode;

        $request->session()->put('clearstay_simple_mode', $newMode);

        $message = $newMode 
            ? 'Simple Mode activated (大きな文字と分かりやすい表示に切り替えました)'
            : 'Standard Mode restored (標準モードに戻しました)';

        return back()->with('success', $message);
    }
}
