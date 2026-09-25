<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Colour modes a viewer can pick; "system" follows the device setting.
     *
     * @var list<string>
     */
    public const APPEARANCES = ['light', 'dark', 'system'];

    /**
     * Accent colour themes. Each has matching CSS variables in resources/css/app.css.
     *
     * @var list<string>
     */
    public const THEMES = ['purple', 'blue', 'teal', 'green', 'rose'];

    /**
     * Share the viewer's saved appearance and theme with the root view so the
     * first paint already uses the right colours.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = $request->cookie('appearance');
        $theme = $request->cookie('theme');

        View::share('appearance', in_array($appearance, self::APPEARANCES, true) ? $appearance : 'system');
        View::share('theme', in_array($theme, self::THEMES, true) ? $theme : 'purple');

        return $next($request);
    }
}
