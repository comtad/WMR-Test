<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\CustomMedia;  // Импорт твоей CustomMedia для запроса
use App\Http\Resources\MediaResource;  // Импорт ресурса

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('csv_files')
            ->acceptsMimeTypes(['text/csv', 'text/plain', 'application/json'])
            ->acceptsFile(function ($file) {
                $fileName = $file->name;  // Свойство для имени файла
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));  // Парсим расширение
                return in_array($extension, ['csv', 'json']);
            });
    }

    /**
     * Получает доступные медиа-файлы: все public + свои private, с пагинацией по 10, через ресурс.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAccessibleMedia()
    {
        $query = CustomMedia::query()
            ->with('model')  // Загружаем владельца
            ->where(function ($query) {
                $query->where('is_private', false);
            })
            ->orWhere(function ($query) {
                $query->where('model_type', self::class)
                    ->where('model_id', $this->id)
                    ->where('is_private', true);
            })
            ->orderBy('created_at', 'desc');

        return MediaResource::collection($query->paginate(5));  // Оборачиваем в коллекцию ресурса
    }
}
