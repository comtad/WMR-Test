<?php

namespace App\Jobs;

use App\Models\CustomMedia;
use App\Enums\MediaStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCsvToTree implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mediaId;
    protected $startTime;
    protected $memoryLimit;
    protected const TIMEOUT = 1200;

    public function __construct($mediaId)
    {
        $this->mediaId = $mediaId;
        $this->memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
    }

    public function handle()
    {
        $this->startTime = time();
        $oldMedia = CustomMedia::findOrFail($this->mediaId);

        try {
            $this->updateMediaStatus($oldMedia, MediaStatus::PROCESSING);
            $jsonFilePath = $this->convertCsvToJson($oldMedia->getPath());

            DB::transaction(function () use ($oldMedia, $jsonFilePath) {
                $this->checkTimeout('Timeout before DB transaction');

                $user = $oldMedia->model;
                $newMedia = $this->createMediaFromFile($user, $jsonFilePath, $oldMedia);

                $this->transferMediaAttributes($oldMedia, $newMedia);
                $newMedia->save();

                $oldMedia->delete();
            });

            @unlink($jsonFilePath);
        } catch (\Exception $e) {
            Log::error('ProcessCsvToTree error: '.$e->getMessage());
            $this->updateMediaStatus($oldMedia, MediaStatus::ERROR);
            $this->fail($e);
        }
    }

    protected function updateMediaStatus(CustomMedia $media, string $status): void
    {
        $media->status = $status;
        $media->save();
    }

    protected function convertCsvToJson(string $csvPath): string
    {
        $file = fopen($csvPath, 'r');
        if (!$file) {
            throw new \Exception("Failed to open CSV: $csvPath");
        }

        $headers = fgetcsv($file);
        if (empty($headers)) {
            fclose($file);
            throw new \Exception('Empty CSV or missing headers');
        }

        $isHierarchical = $this->isHierarchicalCsv($headers);
        $outputFile = tempnam(sys_get_temp_dir(), 'json_');

        if ($isHierarchical) {
            $this->buildTreeJson($file, $headers, $outputFile);
        } else {
            $this->buildFlatJson($file, $headers, $outputFile);
        }

        fclose($file);
        return $outputFile;
    }

    protected function buildFlatJson($handle, array $headers, string $outputPath): void
    {
        $output = fopen($outputPath, 'w');
        fwrite($output, "[\n");
        $isFirst = true;
        $lineNumber = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $this->checkConditions(++$lineNumber);

            if (count($row) !== count($headers)) {
                Log::warning("Skipping row $lineNumber: column mismatch");
                continue;
            }

            $data = array_combine($headers, $row);
            $jsonRow = json_encode($data, JSON_UNESCAPED_UNICODE);

            fwrite($output, $isFirst ? $jsonRow : ",\n".$jsonRow);
            $isFirst = false;
        }

        fwrite($output, "\n]");
        fclose($output);
    }

    protected function buildTreeJson($handle, array $headers, string $outputPath): void
    {
        $items = collect();
        $lineNumber = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $this->checkConditions(++$lineNumber);

            if (count($row) !== count($headers)) {
                Log::warning("Skipping row $lineNumber: column mismatch");
                continue;
            }

            $item = array_combine($headers, $row);
            $id = $item['id'] ?? 'row_'.($lineNumber + 1);
            $parentId = $item['parent_id'] ?? $item['parent'] ?? null;

            $items->put($id, [
                'data' => $item,
                'children' => collect(),
                'parent_id' => $parentId
            ]);
        }

        $tree = $items->filter(function ($item) use ($items) {
            $parentId = $item['parent_id'];

            if ($parentId && $items->has($parentId)) {
                $items->get($parentId)['children']->push($item);
                return false;
            }
            return true;
        })->map(function ($item) {
            return $this->formatTreeItem($item);
        });

        file_put_contents($outputPath, json_encode($tree, JSON_PRETTY_PRINT));
    }

    protected function formatTreeItem(array $item): array
    {
        $result = $item['data'];
        $result['children'] = $item['children']->map(function ($child) {
            return $this->formatTreeItem($child);
        })->toArray();

        return $result;
    }

    protected function createMediaFromFile($user, string $filePath, CustomMedia $oldMedia): CustomMedia
    {
        return $user->addMedia($filePath)
            ->usingFileName(pathinfo($oldMedia->file_name, PATHINFO_FILENAME).'.json')
            ->withCustomProperties($oldMedia->custom_properties ?? [])
            ->toMediaCollection('csv_files');
    }

    protected function transferMediaAttributes(CustomMedia $source, CustomMedia $target): void
    {
        $attributes = [
            'is_private',
            'created_at',
            'updated_at',
            'record_count'
        ];

        foreach ($attributes as $attr) {
            $target->$attr = $source->$attr;
        }

        $target->mime_type = 'application/json';
        $target->status = MediaStatus::COMPLETED;
    }

    protected function isHierarchicalCsv(array $headers): bool
    {
        $hierarchyKeys = ['id', 'parent_id', 'parent', 'level', 'depth'];
        $normalized = array_map('strtolower', $headers);

        return count(array_intersect($normalized, $hierarchyKeys)) >= 2;
    }

    protected function checkConditions(int $lineNumber): void
    {
        $this->checkTimeout("Timeout at line $lineNumber");
        $this->checkMemory("Memory limit reached at line $lineNumber");
    }

    protected function checkTimeout(string $message): void
    {
        if (time() - $this->startTime > self::TIMEOUT) {
            throw new \Exception($message);
        }
    }

    protected function checkMemory(string $message): void
    {
        $used = memory_get_usage(true);
        $limit = $this->memoryLimit * 0.85;

        if ($used > $limit) {
            throw new \Exception("$message. Used: {$this->formatBytes($used)}");
        }
    }

    protected function parseMemoryLimit(string $limit): int
    {
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);

        switch ($unit) {
            case 'g': return $value * 1024 ** 3;
            case 'm': return $value * 1024 ** 2;
            case 'k': return $value * 1024;
            default:  return (int) $limit;
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
