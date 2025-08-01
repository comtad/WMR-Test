<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class GetAllUsersController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $users = User::select('id', 'email', 'public_password', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return UserResource::collection($users);
    }
}
