/**
 * Image uploads.
 *
 * Phone cameras produce 3–8 MB files. A rider standing in a stairwell on a
 * patchy connection cannot wait for that, and shared hosting does not want to
 * store it either — so the browser downscales and re-encodes before anything
 * leaves the device. What reaches the server is typically under 200 KB with no
 * visible loss at the sizes it is ever displayed.
 */

const MIME = 'image/jpeg';

const QUALITY = 0.82;

/**
 * Decode, downscale to fit within maxEdge, and re-encode.
 *
 * Falls back to the original file whenever anything is unsupported — an
 * unresized upload is far better than a failed one when a rider is trying to
 * close a delivery.
 */
export async function downscaleImage(file, maxEdge = 1400) {
    if (!file || !file.type?.startsWith('image/')) {
        return file;
    }

    // Nothing to gain from re-encoding something already small.
    if (file.size < 220 * 1024) {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file);
        const { width, height } = bitmap;
        const longest = Math.max(width, height);

        if (longest <= maxEdge) {
            bitmap.close?.();

            return file;
        }

        const scale = maxEdge / longest;
        const targetWidth = Math.round(width * scale);
        const targetHeight = Math.round(height * scale);

        const canvas = document.createElement('canvas');
        canvas.width = targetWidth;
        canvas.height = targetHeight;

        const context = canvas.getContext('2d');
        context.imageSmoothingQuality = 'high';
        context.drawImage(bitmap, 0, 0, targetWidth, targetHeight);
        bitmap.close?.();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, MIME, QUALITY));

        if (!blob || blob.size >= file.size) {
            return file;
        }

        return new File([blob], replaceExtension(file.name), {
            type: MIME,
            lastModified: Date.now(),
        });
    } catch {
        return file;
    }
}

function replaceExtension(name) {
    return `${(name || 'photo').replace(/\.[^.]+$/, '')}.jpg`;
}

/**
 * Alpine component behind <x-ui.image-upload>.
 *
 * Shows a local preview the instant a file is chosen, so the interface
 * responds before the network does.
 */
export function imageUpload({ property, maxEdge = 1400, existing = null } = {}) {
    return {
        preview: existing,
        uploading: false,
        progress: 0,
        error: null,

        async choose(event) {
            const file = event.target.files?.[0];

            if (!file) {
                return;
            }

            this.error = null;

            if (!file.type.startsWith('image/')) {
                this.error = this.$el.dataset.errorType;
                event.target.value = '';

                return;
            }

            const prepared = await downscaleImage(file, maxEdge);

            // Revoke the previous object URL before replacing it, or a rider
            // taking several attempts leaks a blob per attempt.
            if (this.preview?.startsWith('blob:')) {
                URL.revokeObjectURL(this.preview);
            }

            this.preview = URL.createObjectURL(prepared);
            this.uploading = true;
            this.progress = 0;

            this.$wire.upload(
                property,
                prepared,
                () => {
                    this.uploading = false;
                    this.progress = 100;
                },
                () => {
                    this.uploading = false;
                    this.error = this.$el.dataset.errorUpload;
                },
                (event) => {
                    this.progress = event.detail.progress;
                },
            );

            event.target.value = '';
        },

        clear() {
            if (this.preview?.startsWith('blob:')) {
                URL.revokeObjectURL(this.preview);
            }

            this.preview = null;
            this.progress = 0;
            this.$wire.set(property, null);
        },
    };
}

window.downscaleImage = downscaleImage;
window.imageUpload = imageUpload;
