<?php

namespace App\Observers;

use App\Models\CustomMedia;

class MediaObserver
{
    public function updating(CustomMedia $media)
    {
        if ($media->isDirty('status')) {}
    }
}
