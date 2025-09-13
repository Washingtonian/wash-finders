<?php

namespace App\Http\Controllers;

use App\Models\Provider;

class ProviderController extends Controller
{
    public function index()
    {
        $providers = Provider::first();

        return view('providers.index', compact('providers'));
    }
}
