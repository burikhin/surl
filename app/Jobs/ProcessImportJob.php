<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Giving a 20 minutes timeout for the Job to process the file
    public int $timeout = 1200;

    // Size of the batches we will be processing
    public int $batchSize = 5000;

    public function __construct(private string $file) {}

    public function handle(): void
    {
        $fileStream = fopen($this->file, 'r');

        // Reading the header row
        $header = fgetcsv($fileStream);
        $currentCount = 0;
        $currentBatch = [];

        // Processing each row and importing the records
        while (($row = fgetcsv($fileStream)) !== false) {
            foreach ($row as $index => $col) {
                $rowMapped[$header[$index]] = Str::trim($col);
            }

            // Checking if we have col 'url' and if it is a valid url
            if (! empty($rowMapped['url']) && Str::isUrl($rowMapped['url'])) {
                $currentBatch[] = $rowMapped;
                $currentCount++;

                if ($currentCount >= $this->batchSize) {
                    ProcessBatchJob::dispatch($currentBatch);

                    $currentCount = 0;
                    $currentBatch = [];
                }
            }
        }

        // Finishing saving the last batch
        if (! empty($currentBatch)) {
            ProcessBatchJob::dispatch($currentBatch);
        }

        fclose($fileStream);

        unlink($this->file);
    }
}
