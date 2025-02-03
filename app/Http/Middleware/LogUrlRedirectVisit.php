<?php

namespace App\Http\Middleware;

use App\Models\Surl;
use App\Models\Visit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LogUrlRedirectVisit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $surl = Surl::where('token', '=', $request->route()->parameter('token'))->first();

        if (! $surl) {
            throw new HttpException(404, 'Surl was not found for a given token.');
        }

        $visit = new Visit;
        $visit->surl_id = $surl->id;
        $visit->ip = $request->ip();
        $visit->useragent = $request->server('HTTP_USER_AGENT');
        $visit->save();

        return $next($request);
    }
}
