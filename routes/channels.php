<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('media.events', function () {
    return true;
});
