<?php

namespace App\Console\Commands;

use App\Support\WordPressImporter;
use Illuminate\Console\Command;

class ImportWordPress extends Command
{
    protected $signature = 'content:import-wp {site : e.g. https://brownfoxit.com} {--no-products : skip WooCommerce products}';

    protected $description = 'Copy blog posts, portfolio items and (draft) products from a WordPress site';

    public function handle(): int
    {
        $r = (new WordPressImporter($this->argument('site')))->all(! $this->option('no-products'), 3600);
        $this->line(json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
