<?php

namespace App\Domain\Shared\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stored-image handling for models that carry a picture.
 *
 * Keeps three decisions in one place: which disk images live on, that
 * replacing an image deletes the one it replaced, and that a missing file
 * resolves to null rather than a broken URL — a broken image is worse than an
 * initials placeholder, and the avatar component already has one.
 */
trait HasMedia
{
    /**
     * Public URL for a stored image, or null when there is nothing to show.
     */
    public function mediaUrl(string $attribute): ?string
    {
        $path = $this->getAttribute($attribute);

        if (blank($path)) {
            return null;
        }

        $disk = Storage::disk(self::mediaDisk());

        // A path recorded for a file that has since gone is treated as absent.
        return $disk->exists($path) ? $disk->url($path) : null;
    }

    /**
     * Store an upload against this model, replacing whatever was there.
     */
    public function storeMedia(string $attribute, UploadedFile $file, string $folder): string
    {
        $previous = $this->getAttribute($attribute);

        $path = $file->store($folder.'/'.$this->getKey(), self::mediaDisk());

        $this->forceFill([$attribute => $path])->save();

        // Deleted only after the replacement is safely written, so a failed
        // upload never leaves the model with no image at all.
        if (filled($previous) && $previous !== $path) {
            Storage::disk(self::mediaDisk())->delete($previous);
        }

        return $path;
    }

    public function clearMedia(string $attribute): void
    {
        $path = $this->getAttribute($attribute);

        if (blank($path)) {
            return;
        }

        Storage::disk(self::mediaDisk())->delete($path);

        $this->forceFill([$attribute => null])->save();
    }

    public function hasMedia(string $attribute): bool
    {
        return $this->mediaUrl($attribute) !== null;
    }

    protected static function mediaDisk(): string
    {
        return config('platform.media.disk', 'public');
    }
}
