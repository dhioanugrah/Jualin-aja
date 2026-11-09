<?php

namespace App\Console\Commands;

use App\Support\Guide;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Membuat Buku Panduan versi PDF (F14) dari halaman cetak, versi pengguna dan versi admin.
 * Dijalankan ulang setiap isi panduan berubah, atau otomatis lewat scheduler.
 */
#[Signature('guide:pdf')]
#[Description('Buat ulang Buku Panduan versi PDF (pengguna dan admin) dengan Chrome headless')]
class GenerateGuidePdf extends Command
{
    public function handle(): int
    {
        $chrome = $this->findChrome();

        if ($chrome === null) {
            $this->error('Chrome/Chromium tidak ditemukan. Isi JUALIN_CHROME_PATH di .env dengan lokasi chrome atau chrome-headless-shell.');

            return self::FAILURE;
        }

        $disk = Storage::disk('local');
        $disk->makeDirectory('guide');

        foreach ([false, true] as $forAdmin) {
            $html = view('guide.print', [
                'chapters' => Guide::chapters($forAdmin),
                'forAdmin' => $forAdmin,
                'forFile' => true,
            ])->render();

            $source = $disk->path('guide/cetak-'.($forAdmin ? 'admin' : 'pengguna').'.html');
            $target = $disk->path(Guide::pdfPath($forAdmin));
            $temporary = $target.'.tmp';
            File::put($source, $html);

            $result = Process::timeout(120)->run([
                $chrome, '--headless', '--disable-gpu', '--no-sandbox', '--no-pdf-header-footer',
                '--print-to-pdf='.$temporary, 'file://'.$source,
            ]);

            File::delete($source);

            if (! File::exists($temporary) || ! str_starts_with((string) file_get_contents($temporary, false, null, 0, 4), '%PDF')) {
                File::delete($temporary);
                $this->error('Gagal membuat PDF: '.trim($result->errorOutput() ?: $result->output()));

                return self::FAILURE;
            }

            File::move($temporary, $target);
            $this->info(sprintf('%s → %s (%d KB)', $forAdmin ? 'Versi admin' : 'Versi pengguna', Guide::pdfPath($forAdmin), (int) ceil(File::size($target) / 1024)));
        }

        return self::SUCCESS;
    }

    private function findChrome(): ?string
    {
        $configured = config('jualin.chrome_path');

        if (filled($configured)) {
            return is_executable($configured) ? $configured : null;
        }

        foreach (['chromium', 'chromium-browser', 'google-chrome', 'google-chrome-stable', 'chrome-headless-shell'] as $binary) {
            $path = trim(Process::run(['sh', '-c', 'command -v '.$binary])->output());

            if ($path !== '') {
                return $path;
            }
        }

        $home = getenv('HOME') ?: '';
        $candidates = array_merge(
            glob($home.'/.cache/ms-playwright/chromium_headless_shell-*/*/chrome-headless-shell') ?: [],
            glob($home.'/.cache/ms-playwright/chromium-*/chrome-linux64/chrome') ?: [],
        );

        return collect($candidates)->first(fn (string $path): bool => is_executable($path));
    }
}
