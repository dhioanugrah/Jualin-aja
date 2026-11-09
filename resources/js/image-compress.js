const MAX_SIDE = 1600;
const QUALITY = 0.82;

/**
 * Perkecil foto di browser sebelum diunggah supaya hemat kuota dan
 * lolos batas 2 MB di server. Jika browser tidak bisa membaca formatnya
 * (misalnya HEIC), file asli dikembalikan apa adanya.
 *
 * @param {File} file
 * @returns {Promise<File>}
 */
export async function compressImage(file) {
    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');

        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', QUALITY));

        if (!blob || blob.size >= file.size) {
            return file;
        }

        const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';

        return new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() });
    } catch {
        return file;
    }
}
