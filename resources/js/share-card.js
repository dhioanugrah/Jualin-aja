/**
 * Kartu gambar berlogo untuk tombol Bagikan (F15). Digambar di browser (canvas),
 * karena server tidak punya ekstensi GD. Ukuran 1080x1350 cocok untuk status WA/IG.
 */
const W = 1080;
const H = 1350;
const BRAND = '#1E5AA8';
const ACCENT = '#F5B700';
const INK = '#0F172A';

function loadImage(src) {
    return new Promise((resolve) => {
        if (!src) {
            resolve(null);
            return;
        }

        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => resolve(image);
        image.onerror = () => resolve(null);
        image.src = src;
    });
}

function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

/** Potong teks menjadi beberapa baris sesuai lebar. */
function wrap(ctx, text, maxWidth, maxLines) {
    const words = text.split(/\s+/);
    const lines = [];
    let line = '';

    for (const word of words) {
        const test = line ? `${line} ${word}` : word;

        if (ctx.measureText(test).width > maxWidth && line) {
            lines.push(line);
            line = word;
        } else {
            line = test;
        }
    }

    lines.push(line);

    if (lines.length > maxLines) {
        const kept = lines.slice(0, maxLines);
        kept[maxLines - 1] = `${kept[maxLines - 1].replace(/\s+\S*$/, '')}…`;

        return kept;
    }

    return lines;
}

/** Gambar foto dengan mode "cover" di dalam kotak membulat. */
function drawCover(ctx, image, x, y, w, h, r) {
    ctx.save();
    roundRect(ctx, x, y, w, h, r);
    ctx.clip();
    ctx.fillStyle = '#E2E8F0';
    ctx.fillRect(x, y, w, h);

    if (image) {
        const scale = Math.max(w / image.width, h / image.height);
        const sw = w / scale;
        const sh = h / scale;
        ctx.drawImage(image, (image.width - sw) / 2, (image.height - sh) / 2, sw, sh, x, y, w, h);
    }

    ctx.restore();
}

export async function renderShareCard({ name, price, image, seller, area, url, logo }) {
    await document.fonts?.ready;

    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');
    const font = (weight, size) => `${weight} ${size}px "Plus Jakarta Sans", system-ui, sans-serif`;

    ctx.fillStyle = '#F8FAFC';
    ctx.fillRect(0, 0, W, H);

    // Lingkaran kuning khas logo di pojok kanan atas.
    ctx.fillStyle = ACCENT;
    ctx.beginPath();
    ctx.arc(W - 40, 40, 150, 0, Math.PI * 2);
    ctx.fill();

    const [photo, logoImage] = await Promise.all([loadImage(image), loadImage(logo)]);

    if (logoImage) {
        const logoHeight = 72;
        ctx.drawImage(logoImage, 72, 64, (logoImage.width / logoImage.height) * logoHeight, logoHeight);
    } else {
        ctx.fillStyle = BRAND;
        ctx.font = font(800, 56);
        ctx.fillText('Jualin Aja ITK', 72, 120);
    }

    drawCover(ctx, photo, 72, 180, W - 144, 720, 48);

    ctx.fillStyle = INK;
    ctx.font = font(800, 58);
    const lines = wrap(ctx, name, W - 144, 2);
    lines.forEach((line, index) => ctx.fillText(line, 72, 990 + index * 70));

    const priceY = 990 + lines.length * 70 + 20;
    ctx.fillStyle = BRAND;
    ctx.font = font(800, 64);
    ctx.fillText(price, 72, priceY);

    ctx.fillStyle = '#64748B';
    ctx.font = font(500, 32);
    ctx.fillText(`${seller} · COD ${area}`, 72, priceY + 58);

    // Pita bawah biru berisi alamat web.
    ctx.fillStyle = BRAND;
    ctx.fillRect(0, H - 120, W, 120);
    ctx.fillStyle = ACCENT;
    ctx.fillRect(W * 0.7, H - 120, W * 0.3, 120);
    ctx.fillStyle = '#FFFFFF';
    ctx.font = font(700, 34);
    ctx.fillText(url.replace(/^https?:\/\//, '').slice(0, 42), 72, H - 48);
    ctx.fillStyle = INK;
    ctx.font = font(800, 34);
    ctx.textAlign = 'center';
    ctx.fillText('Cek di web', W * 0.85, H - 48);

    return new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
}
