<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GetMediaController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = auth()->user();
        return $user->getAccessibleMedia();
    }
}
