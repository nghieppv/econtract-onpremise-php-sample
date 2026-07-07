<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('dochub:about', function (): void {
    $this->info('eContract On-Premise Laravel sample. Run: php artisan dochub:sample');
})->purpose('Show DocHub sample information');
