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

class ProcessBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Giving a 20 minutes timeout for the Job to process the file
    public int $timeout = 1200;

    public function __construct(private array $batch) {}

    public function handle(): void
    {
        // Using raw sql to get inserted data back, upsert only returns affected rows
        $sql = 'INSERT INTO surls (url, created_at, updated_at) VALUES ';
        foreach ($this->batch as $row) {
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
