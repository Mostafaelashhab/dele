<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Runtime overrides layered on top of config/platform.php. Reads go through
 * a single cached map so a page that resolves many settings costs one query.
 */
#[Fillable(['key', 'value', 'group', 'type', 'label', 'description', 'updated_by_user_id'])]
class PlatformSetting extends Model
{
    public const CACHE_KEY = 'platform.settings.map';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return data_get(self::map(), $key, $default ?? config("platform.{$key}"));
    }

    public static function put(string $key, mixed $value, string $group = 'general', ?int $userId = null): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => ['v' => $value], 'group' => $group, 'updated_by_user_id' => $userId],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function map(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function (): array {
            return self::query()
                ->get(['key', 'value'])
                ->mapWithKeys(fn (self $setting) => [$setting->key => $setting->value['v'] ?? null])
                ->all();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }
}
