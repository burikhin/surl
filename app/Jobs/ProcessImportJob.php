<?php

namespace App\Jobs;

use App\Models\Surl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Utils\Base58;

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
                    $this->processUrlBatch($currentBatch);

                    $currentCount = 0;
                    $currentBatch = [];
                }
            }
        }

        // Finishing saving the last batch
        if (! empty($currentBatch)) {
            $this->processUrlBatch($currentBatch);
        }

        fclose($fileStream);

        unlink($this->file);
    }

    /**
     * Shorten and save urls with new tokens.
     */
    private function processUrlBatch(array $batch): void
    {
        // Using raw sql to get inserted data back, upsert only returns affected rows
        $sql = 'INSERT INTO surls (url, created_at, updated_at) VALUES ';
        foreach ($batch as $row) {
            $sql .= '('."'".$row['url']."'".', current_timestamp, current_timestamp'.'), ';
        }

        // Removing the last coma and space from generated string
        $sql = Str::substr($sql, 0, -2);
        $sql .= ' ON CONFLICT DO NOTHING RETURNING id, url';

        $surls = DB::select($sql);

        if (! empty($surls)) {
            // Generating unique tokens based on the db ids
            $updates = [];
            foreach ($surls as $surl) {
                $updates[] = [
                    'id' => $surl->id,
                    'url' => $surl->url,
                    'token' => Base58::encode($surl->id),
                ];
            }

            Surl::upsert($updates, uniqueBy: ['url'], update: ['token']);
        }
    }
}
