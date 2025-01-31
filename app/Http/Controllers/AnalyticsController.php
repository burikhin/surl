<?php

namespace App\Http\Controllers;

use App\Models\Surl;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AnalyticsController extends Controller
{
    /**
     * Show the shorten url analytics for a given id.
     */
    public function getUrlAnalytics(int $id, Request $request): Surl
    {
        $surl = Surl::with('visits')->withCount('visits')->find($id);

        if (! $surl) {
            throw new HttpException(404, 'Surl was not found for a given id.');
        }

        return $surl;
    }

    /**
     * Show the shorten url analytics for a given token.
     */
    public function getUrlAnalyticsByToken(string $token, Request $request): ?Surl
    {
        $surl = Surl::where('token', '=', $token)->with('visits')->withCount('visits')->first();

        if (! $surl) {
            throw new HttpException(404, 'Surl was not found for a given token.');
        }

        return $surl;
    }
}
