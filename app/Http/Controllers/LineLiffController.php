<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LineLiffController extends Controller
{
    public function showOpponentCreateScreen()
    {
        return view('liff.opponent.create');
    }
}
