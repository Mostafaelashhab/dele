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

    /**
     * The disk for anything that must never be reachable by URL.
     *
     * The ordinary media disk is public — a shop logo and a proof photo are
     * meant to be linkable. An identity document is not: a national ID card
     * sitting on a public disk is fetchable by anyone who guesses the path,
     * with no login and no log. These go on the private disk and are served
     * only through an authorised controller.
     */
    protected static function privateMediaDisk(): string
    {
        return (string) config('platform.media.private_disk', 'local');
    }

    /**
     * Store a document privately, replacing whatever was there before.
     */
    public function storePrivateMedia(string $attribute, UploadedFile $file, string $folder): string
    {
        $previous = $this->getAttribute($attribute);

        $path = $file->store($folder.'/'.$this->getKey(), self::privateMediaDisk());

        $this->forceFill([$attribute => $path])->save();

        if (filled($previous) && $previous !== $path) {
            Storage::disk(self::privateMediaDisk())->delete($previous);
        }

        return $path;
    }

    /**
     * The bytes of a private document, for a caller that has already checked
     * it is allowed to see them. Deliberately returns contents rather than a
     * URL: there is no URL, and there should not be one.
     */
    public function privateMediaContents(string $attribute): ?string
    {
        $path = $this->getAttribute($attribute);

        if (blank($path)) {
            return null;
        }

        $disk = Storage::disk(self::privateMediaDisk());

        return $disk->exists($path) ? $disk->get($path) : null;
    }

    public function hasPrivateMedia(string $attribute): bool
    {
        $path = $this->getAttribute($attribute);

        return filled($path) && Storage::disk(self::privateMediaDisk())->exists($path);
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
