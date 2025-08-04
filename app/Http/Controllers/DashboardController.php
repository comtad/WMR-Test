<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = auth()->user();
        $media = $user->getAccessibleMedia();  // Вызов метода из модели, возвращает пагинированный ресурс

        return Inertia::render('Dashboard', [
            'media' => $media,
        ]);
    }
}
