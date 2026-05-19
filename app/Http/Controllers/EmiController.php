<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmiController extends Controller
{
    public function index()
    {
        return view('emi.calculator');
    }
}
