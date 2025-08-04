<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use League\Csv\Reader;
use League\Csv\Exception;
use App\Models\CustomMedia;

class UploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'extensions:csv', function ($attribute, $value, $fail) {
                try {
                    $reader = Reader::createFromPath($value->getPathname(), 'r');
                    $reader->setHeaderOffset(0);

                    $headers = $reader->getHeader();
                    if (empty($headers)) {
                        $fail('CSV-файл должен содержать заголовки.');
                        return;
                    }
                    $expectedColumns = count($headers);

                    $records = $reader->getRecords();
                    $rowCount = 0;
                    $maxRows = 1000;

                    foreach ($records as $record) {
                        if (count($record) !== $expectedColumns) {
                            $fail('Неконсистентное количество колонок в CSV-файле.');
                            return;
                        }
                        $rowCount++;
                        if ($rowCount >= $maxRows) {
                            break;
                        }
                    }

                    if ($rowCount === 0) {
                        $fail('CSV-файл пуст (кроме заголовков).');
                        return;
                    }

                } catch (Exception $e) {
                    $fail('Файл должен быть валидным CSV: ' . $e->getMessage());
                } catch (\Throwable $e) {
                    $fail('Ошибка обработки CSV: ' . $e->getMessage());
                }
            }],
            'is_private' => ['required', 'boolean'],
            '*' => [
                function ($attribute, $value, $fail) {
                    $user = $this->user();

                    if ($user->created_at->diffInDays(now()) <= 10) {
                        $fail('Вы можете загружать файлы только после 10 дней регистрации.');
                    }

                    $lastMedia = CustomMedia::where('model_type', 'App\\Models\\User')
                        ->where('model_id', $user->id)
                        ->latest('created_at')
                        ->first();

                    if ($lastMedia && $lastMedia->created_at > now()->subMinutes(5)) {
                        $fail('Вы можете загружать файлы не чаще, чем раз в 5 минут.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Файл обязателен для загрузки.',
            'file.file' => 'Загруженный объект должен быть файлом.',
            'file.extensions' => 'Файл должен быть в формате CSV.',
            'is_private.required' => 'Поле приватности обязательно.',
            'is_private.boolean' => 'Поле приватности должно быть булевым.',
        ];
    }
}
