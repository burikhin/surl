<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUrlsFromFileRequest;
use App\Jobs\ExperimentalImportJob;
use App\Jobs\ProcessImportJob;
use App\Models\Surl;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SurlController extends Controller
{
    /**
     * Create new short urls from a csv file.
     */
    public function createUrlsFromFile(CreateUrlsFromFileRequest $request): array
    {
        $file = $request->file('urls');

        // Saving the file to storage for reading it as CSV
        $file = $file->store('', ['disk' => 'public']);

        if ($request->get('experimental')) {
            // Starting an experimental job to process file with a golang utility
            ExperimentalImportJob::dispatch(storage_path('app/public/'.$file));
        } else {
            // Starting a job to process file with a queue worker
            ProcessImportJob::dispatch(storage_path('app/public/'.$file));
        }

        $result = [
            'message' => 'File uploaded successfully! Import is starting in the background.',
        ];

        return $result;
    }

    /**
     * Show full list of saved urls.
     */
    public function getUrlList(Request $request): LengthAwarePaginator
    {
        return Surl::paginate(15);
    }

    /**
     * Show saved url.
     */
    public function getUrl(int $id, Request $request): Surl
    {
        $surl = Surl::find($id);

        if (! $surl) {
            throw new HttpException(404, 'Surl was not found for a given id.');
        }

        return $surl;
    }

    /**
     * Delete saved url.
     */
    public function deleteUrl(int $id, Request $request): int
    {
        return Surl::destroy($id);
    }
}
