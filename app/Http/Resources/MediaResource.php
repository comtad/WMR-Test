<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Enums\MediaStatus;  // Импорт для статусов и цветов

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusValue = $this->status;
        $statusColor = MediaStatus::getColor($statusValue);
        $isCompleted = $statusValue === MediaStatus::COMPLETED;

        return [
            'date' => Carbon::parse($this->created_at)->locale('ru')->diffForHumans(),
            'filename' => $this->file_name,
            'record_count' => $this->record_count,
            'status' => [
                'value' => $statusValue,
                'color' => $statusColor,
            ],
            'author' => $this->is_private ? null : $this->model->name,
            'download_json' => $isCompleted ? $this->getUrl() : 'Недоступно',
        ];
    }
}
