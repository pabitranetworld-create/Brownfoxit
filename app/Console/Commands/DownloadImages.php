<?php

namespace App\Console\Commands;

use App\Support\ImageSync;
use Illuminate\Console\Command;

/**
 *   php artisan site:download-images            download missing images
 *   php artisan site:download-images --force    download all images again
 *   php artisan site:download-images --list     only print the list
 */
class DownloadImages extends Command
{
    protected $signature = 'site:download-images
                            {--source= : Base URL to download from (default: SITE_IMAGE_SOURCE in .env)}
                            {--force : Download again even if the file already exists}
                            {--list : Only list the images, do not download}';

    protected $description = 'Download the images used by the site from the old WordPress site into public/uploads';

    public function handle(): int
    {
        $sync = new ImageSync($this->option('source'));
        $all = $sync->all();
        $todo = $this->option('force') ? $all : $sync->missing();

        $this->info(count($all).' images used, '.count($todo).' to download.');

        if ($this->option('list')) {
            foreach ($all as $path => $url) {
                $this->line((isset($todo[$path]) ? '  [missing] ' : '  [ok]      ').$path);
            }

            return self::SUCCESS;
        }

        $failed = [];
        $bar = $this->output->createProgressBar(count($todo));
        $bar->start();

        foreach ($todo as $path => $url) {
            if ($error = $sync->download($path, $url)) {
                $failed[] = "{$path}  ({$error})";
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Downloaded: '.(count($todo) - count($failed)).'   Failed: '.count($failed));

        foreach ($failed as $line) {
            $this->warn('  x '.$line);
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
