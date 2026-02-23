<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(Request $request)
    {
        $view = $request->route('view');

        if (!$view) {
            abort(404);
        }

        return view($view);
    }
}

