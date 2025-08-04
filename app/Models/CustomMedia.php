<?php

namespace App\Models;

use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class CustomMedia extends BaseMedia
{
    use BroadcastsEvents;

    protected $casts = [
        'manipulations' => 'array',
        'custom_properties' => 'array',
        'generated_conversions' => 'array',
        'responsive_images' => 'array',
        'is_private' => 'boolean',
        'status' => 'string',
        'record_count' => 'integer',
    ];

    public function broadcastOn($event)
    {
        \Illuminate\Support\Facades\Log::info('Broadcast on called', ['event' => $event, 'media_id' => $this->id, 'channel' => 'media.events']);
        return new Channel('media.events');
    }

    public function broadcastAs()
    {
        return 'media.' . $this->getTable();
    }

    public function broadcastWith($event)
    {
        \Illuminate\Support\Facades\Log::info('Broadcasting media event from CustomMedia', ['event' => $event, 'media_id' => $this->id]);
        return [
            'action' => 'update_table'
        ];
    }

    public function broadcastWhen($event)
    {
        \Illuminate\Support\Facades\Log::info('Broadcast when checked', ['event' => $event, 'media_id' => $this->id, 'result' => $event !== 'deleted']);
        return $event !== 'deleted';
    }
}
