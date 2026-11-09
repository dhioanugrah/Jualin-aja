{{-- Buku Panduan versi cetak/PDF (F14). Mandiri tanpa Vite supaya bisa dirender Chrome headless dari file. --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Buku Panduan{{ $forAdmin ? ' (Admin)' : '' }} · Jualin Aja ITK</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,700,800&display=swap" rel="stylesheet">
<style>
    :root { --blue: #1E5AA8; --blue-700: #17457F; --blue-50: #EEF4FC; --yellow: #F5B700; --yellow-50: #FFF8E1; --ink: #0F172A; --ink-2: #334155; --muted: #64748B; --line: #E2E8F0; }
    @page { size: A4; margin: 16mm 16mm 18mm;
        @bottom-left { content: "Buku Panduan Jualin Aja ITK{{ $forAdmin ? ' · Versi Admin' : '' }}"; font-family: "Plus Jakarta Sans", sans-serif; font-size: 8pt; color: #94A3B8; }
        @bottom-right { content: "Halaman " counter(page) " dari " counter(pages); font-family: "Plus Jakarta Sans", sans-serif; font-size: 8pt; color: #94A3B8; } }
    @page :first { @bottom-left { content: none; } @bottom-right { content: none; } }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Plus Jakarta Sans", system-ui, sans-serif; font-size: 10.5pt; line-height: 1.6; color: var(--ink-2); }
    h1, h2, h3 { color: var(--ink); line-height: 1.25; margin: 0; break-after: avoid; }
    a { color: var(--blue); font-weight: 700; text-decoration: none; }
    strong { color: var(--ink); }
    ol, ul { padding-left: 6mm; margin: 0 0 3mm; } li { margin-bottom: 1mm; }
    p { margin: 0 0 3mm; }
    .screen-bar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: flex-end; padding: 10px 16px; background: #fff; border-bottom: 1px solid var(--line); }
    .screen-bar button, .screen-bar a { font: inherit; font-size: 10pt; border-radius: 999px; padding: 6px 16px; border: 1px solid var(--line); background: #fff; color: var(--ink); cursor: pointer; }
    .screen-bar button { background: var(--blue); color: #fff; border-color: var(--blue); font-weight: 700; }
    .page { max-width: 178mm; margin: 0 auto; padding: 0 0 8mm; }
    .cover { min-height: 255mm; display: flex; flex-direction: column; justify-content: space-between; padding-top: 6mm; }
    .cover .logo svg { height: 16mm; width: auto; }
    .cover h1 { font-size: 36pt; font-weight: 800; letter-spacing: -.03em; line-height: 1.08; margin: 14mm 0 5mm; }
    .cover h1 em { font-style: normal; color: var(--blue); }
    .cover .lead { font-size: 13pt; max-width: 140mm; }
    .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 3mm; margin-top: 8mm; }
    .meta div { border: 1px solid var(--line); border-radius: 12px; padding: 3mm 4mm; font-size: 10pt; }
    .meta small { display: block; color: var(--muted); font-size: 7.5pt; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
    .band { height: 10mm; border-radius: 12px; background: linear-gradient(90deg, var(--blue) 0 70%, var(--yellow) 70% 100%); }
    .toc { break-before: page; }
    .toc h2 { font-size: 20pt; font-weight: 800; margin-bottom: 5mm; }
    .toc ol { list-style: none; padding: 0; }
    .toc li { display: flex; gap: 4mm; padding: 2.5mm 0; border-bottom: 1px dashed var(--line); margin: 0; }
    .toc .n { color: var(--blue); font-weight: 800; width: 8mm; }
    .toc .s { color: var(--muted); font-size: 9pt; margin-left: auto; max-width: 95mm; text-align: right; }
    .chapter { break-before: page; }
    .chapter > header { border-bottom: 3px solid var(--blue); padding-bottom: 3mm; margin-bottom: 5mm; }
    .chapter > header small { color: var(--blue); font-weight: 800; text-transform: uppercase; letter-spacing: .08em; font-size: 8.5pt; }
    .chapter > header h2 { font-size: 22pt; font-weight: 800; letter-spacing: -.02em; }
    .chapter > header p { color: var(--muted); margin: 1mm 0 0; }
    .chapter h2 { font-size: 13pt; font-weight: 800; margin: 5mm 0 2mm; }
    .chapter h3 { font-size: 11pt; font-weight: 700; margin: 4mm 0 1mm; }
    .tip, .warn { border-radius: 10px; padding: 3mm 4mm; margin: 3mm 0; font-size: 9.8pt; break-inside: avoid; }
    .tip { background: var(--blue-50); border-left: 4px solid var(--blue); }
    .warn { background: var(--yellow-50); border-left: 4px solid var(--yellow); }
    .closing { margin-top: 8mm; padding: 4mm; border-radius: 12px; background: var(--blue-50); font-size: 9.8pt; break-inside: avoid; }
    @media print { .screen-bar { display: none; } .page { max-width: none; } }
</style>
</head>
<body>
@unless ($forFile)
    <div class="screen-bar">
        <a href="{{ route('guide.index') }}">Kembali ke panduan</a>
        <button type="button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
    </div>
@endunless

<div class="page">
    <section class="cover">
        <div>
            <div class="logo">{!! file_get_contents(public_path('brand/logo.svg')) !!}</div>
            <h1>Buku Panduan<br><em>Jualin Aja ITK</em></h1>
            <p class="lead">Cara jual-beli barang bekas antarmahasiswa ITK: dari daftar akun, menjual dan mencari barang, sampai COD aman dengan kode serah terima.</p>
            <div class="meta">
                <div><small>Versi</small><strong>{{ $forAdmin ? 'Admin' : 'Pengguna' }}</strong> · diperbarui {{ now()->translatedFormat('j F Y') }}</div>
                <div><small>Versi online</small>{{ route('guide.index') }}</div>
                <div><small>Isi</small>{{ $chapters->count() }} bab</div>
                <div><small>Kontak admin</small>{{ config('jualin.admin_email') }}</div>
            </div>
        </div>
        <div class="band"></div>
    </section>

    <section class="toc">
        <h2>Daftar isi</h2>
        <ol>
            @foreach ($chapters as $slug => $chapter)
                <li><span class="n">{{ $loop->iteration }}</span><strong>{{ $chapter['title'] }}</strong><span class="s">{{ $chapter['summary'] }}</span></li>
            @endforeach
        </ol>
    </section>

    @foreach ($chapters as $slug => $chapter)
        <section class="chapter" id="{{ $slug }}">
            <header>
                <small>Bab {{ $loop->iteration }}</small>
                <h2>{{ $chapter['title'] }}</h2>
                <p>{{ $chapter['summary'] }}</p>
            </header>
            @include("guide.chapters.{$slug}")
        </section>
    @endforeach

    <p class="closing">Ada yang belum terjawab? Buka versi online di <a href="{{ route('guide.index') }}">{{ route('guide.index') }}</a>, tekan <strong>Beri Masukan</strong> di web, atau hubungi admin di {{ config('jualin.admin_email') }}.</p>
</div>
</body>
</html>
