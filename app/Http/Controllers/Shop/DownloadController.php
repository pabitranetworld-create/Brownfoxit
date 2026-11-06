<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\DownloadPermission;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Protected downloads: secret token + paid order + limit/expiry checks before every download. */
class DownloadController extends Controller
{
    public function download(string $token): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $perm = DownloadPermission::where('token', $token)->with(['order', 'product'])->first();
        abort_unless($perm, 404);
        if ($problem = $perm->problem()) {
            return redirect($perm->order ? $perm->order->link() : url('/'))->withErrors(['download' => $problem]);
        }
        $file = $perm->product?->file($perm->file_key);
        $root = realpath(storage_path('app/private'));
        $path = $file ? realpath(storage_path('app/private/'.ltrim($file['path'], '/'))) : false;
        if (! $path || ! $root || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR) || ! is_file($path)) {
            return redirect($perm->order->link())->withErrors(['download' => 'This file is not available right now. Please contact us.']);
        }
        DB::transaction(function () use ($perm) {
            $p = DownloadPermission::lockForUpdate()->find($perm->id);
            $p->download_count++;
            if ($p->downloads_remaining !== null) {
                $p->downloads_remaining = max(0, $p->downloads_remaining - 1);
            }
            $p->last_download_at = now();
            $p->save();
        });

        $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', $perm->file_name ?: basename($path));
        if (! pathinfo($name, PATHINFO_EXTENSION) && ($ext = pathinfo($path, PATHINFO_EXTENSION))) {
            $name .= '.'.$ext;
        }

        return response()->download($path, $name, ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex']);
    }
}
