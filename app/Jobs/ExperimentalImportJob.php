<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExperimentalImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Giving a 20 minutes timeout for the Job to process the file
    public int $timeout = 1200;

    public function __construct(private string $file) {}

    public function handle(): void
    {
        exec('cd '.base_path().'/gojob/ && ./import -f="'.$this->file.'"');
    }
}
