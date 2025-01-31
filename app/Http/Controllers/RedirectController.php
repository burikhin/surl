<?php

namespace App\Http\Controllers;

use App\Models\Surl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RedirectController extends Controller
{
    /**
     * Redirect user to an original url with the given token.
     */
    public function redirect(string $token, Request $request): RedirectResponse
    {
        $surl = Surl::where('token', '=', $token)->first();

        if (! $surl) {
            throw new HttpException(404, 'Surl was not found for a given token.');
        }

        return Redirect::to($surl->url);
    }
}
