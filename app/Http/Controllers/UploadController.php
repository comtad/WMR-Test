<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRequest;
use App\Jobs\ProcessCsvToTree;
use App\Enums\MediaStatus;
use Illuminate\Support\Facades\Auth;
use League\Csv\Reader;

class UploadController extends Controller
{
    public function __invoke(UploadRequest $request)
    {
        $user = Auth::user();

        $media = $user->addMedia($request->file('file'))
            ->toMediaCollection('csv_files');

        $media->is_private = $request->boolean('is_private');
        $media->status = MediaStatus::LOADED;

        $csvPath = $media->getPath();
        $reader = Reader::createFromPath($csvPath, 'r');
        $reader->setHeaderOffset(0);
        $recordCount = $reader->count();
        $media->record_count = $recordCount;

        $media->save();
        ProcessCsvToTree::dispatch($media->id);

        return response()->noContent();
    }
}
