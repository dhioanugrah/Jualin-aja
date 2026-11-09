import Alpine from 'alpinejs';
import { compressImage } from './image-compress';
import { renderShareCard } from './share-card';

/**
 * Pengunggah foto barang (F2): Ambil Foto (kamera HP), Pilih dari Galeri,
 * seret-lepas di desktop, pratinjau, urutkan, hapus, kompres di browser.
 *
 * Foto baru dikirim lewat <input name="photos[]">, urutannya lewat
 * <input name="photo_order[]"> dengan nilai "e:{id}" (foto lama) atau "n:{index}" (foto baru).
 */
Alpine.data('photoUploader', ({ max = 5, existing = [] } = {}) => ({
    max,
    items: existing.map((photo) => ({ key: `e:${photo.id}`, url: photo.url, file: null })),
    busy: false,
    dragging: false,
    error: '',

    init() {
        this.$nextTick(() => this.sync());
    },

    get remaining() {
        return this.max - this.items.length;
    },

    async addFiles(fileList) {
        this.error = '';
        const files = Array.from(fileList || []).filter((file) => file.type.startsWith('image/'));

        if (files.length > this.remaining) {
            this.error = `Maksimal ${this.max} foto. ${files.length - this.remaining} foto tidak ditambahkan.`;
        }

        this.busy = true;

        for (const file of files.slice(0, Math.max(this.remaining, 0))) {
            const compressed = await compressImage(file);
            this.items.push({ key: null, url: URL.createObjectURL(compressed), file: compressed });
        }

        this.busy = false;
        this.$nextTick(() => this.sync());
    },

    remove(index) {
        const [removed] = this.items.splice(index, 1);

        if (removed?.file) {
            URL.revokeObjectURL(removed.url);
        }

        this.sync();
    },

    move(index, step) {
        const target = index + step;

        if (target < 0 || target >= this.items.length) {
            return;
        }

        [this.items[index], this.items[target]] = [this.items[target], this.items[index]];
        this.sync();
    },

    makeCover(index) {
        const [item] = this.items.splice(index, 1);
        this.items.unshift(item);
        this.sync();
    },

    sync() {
        const transfer = new DataTransfer();
        let newIndex = 0;

        this.items.forEach((item) => {
            if (item.file) {
                transfer.items.add(item.file);
                item.order = `n:${newIndex++}`;
            } else {
                item.order = item.key;
            }
        });

        this.$refs.upload.files = transfer.files;
        this.$refs.camera.value = '';
        this.$refs.gallery.value = '';
    },
}));

/** Notifikasi singkat (toast) di pojok layar. */
Alpine.data('toast', (message) => ({
    message,
    show: false,
    init() {
        if (!this.message) {
            return;
        }

        this.$nextTick(() => (this.show = true));
        setTimeout(() => (this.show = false), 4000);
    },
}));

/**
 * Tombol Bagikan barang (F15): bagikan tautan atau kartu gambar berlogo ke WA/IG.
 */
Alpine.data('shareProduct', (product) => ({
    open: false,
    busy: false,
    copied: false,
    cardUrl: null,
    cardBlob: null,

    get waLink() {
        return `https://wa.me/?text=${encodeURIComponent(`${product.name} – ${product.price} di Jualin Aja ITK. ${product.url}`)}`;
    },

    async prepareCard() {
        if (this.cardBlob || this.busy) {
            return;
        }

        this.busy = true;
        this.cardBlob = await renderShareCard(product);
        this.cardUrl = this.cardBlob ? URL.createObjectURL(this.cardBlob) : null;
        this.busy = false;
    },

    async openSheet() {
        this.open = true;
        await this.prepareCard();
    },

    async shareCard() {
        await this.prepareCard();

        const file = this.cardBlob ? new File([this.cardBlob], `jualin-${product.slug}.png`, { type: 'image/png' }) : null;

        if (file && navigator.canShare?.({ files: [file] })) {
            try {
                await navigator.share({ files: [file], text: `${product.name} – ${product.price}\n${product.url}` });
            } catch (error) {
                // Pengguna menutup dialog bagikan; tidak perlu pesan.
            }

            return;
        }

        this.$refs.download.click();
    },

    async copyLink() {
        try {
            await navigator.clipboard.writeText(product.url);
        } catch (error) {
            window.prompt('Salin tautan ini:', product.url);
        }

        this.copied = true;
        setTimeout(() => (this.copied = false), 2000);
    },
}));

window.Alpine = Alpine;

Alpine.start();
