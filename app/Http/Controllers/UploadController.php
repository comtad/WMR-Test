<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv',
            'is_private' => 'required|boolean',
        ]);

        $path = $request->file('file')->store('uploads');

        return response()->json(['message' => 'Файл загружен', 'path' => $path]);
    }
}
