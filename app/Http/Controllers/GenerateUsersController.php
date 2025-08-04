<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GenerateUsersController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        DB::beginTransaction();

        $tables = collect(Schema::getTableListing())->filter(fn($table) => $table !== 'migrations');

        if ($tables->isNotEmpty()) {
            $tablesString = $tables->implode(', ');
            DB::statement("TRUNCATE TABLE {$tablesString} CASCADE;");
        }

        DB::commit();

        User::factory()->count(6)->create();

        User::factory()->create([
            'created_at' => fake()->dateTimeBetween('-5 days', '-1 day'),
        ]);

        $subRequest = \Illuminate\Support\Facades\Request::create(route('users.index'));
        $response = app()->handle($subRequest);

        return $response;
    }
}
