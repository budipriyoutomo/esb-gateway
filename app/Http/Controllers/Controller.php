<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

abstract class Controller
{
    /**
     * Flash a one-shot toast for the next Inertia page (use-flash-toast.ts).
     *
     * Moved here under ADR-015 once the purchase request decision, write and
     * import controllers all needed it.
     *
     * @param  'success'|'warning'|'error'  $type
     */
    protected function toast(string $type, string $message): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }
}
