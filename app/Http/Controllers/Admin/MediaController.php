<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Support\ImageUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(): View
    {
        return view('admin.media.index', ['total' => Media::count()]);
    }

    /** JSON list for the library page and the picker. */
    public function list(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        $items = Media::query()
            ->when($q, fn ($query) => $query->where(fn ($w) => $w->where('original_name', 'like', "%{$q}%")->orWhere('alt', 'like', "%{$q}%")->orWhere('path', 'like', "%{$q}%")))
            ->latest('id')
            ->paginate(40);

        return response()->json($items);
    }

    public function store(Request $request, ImageUploader $uploader): JsonResponse
    {
        $files = $request->file('files', []);
        $files = is_array($files) ? $files : [$files];
        $saved = [];
        $errors = [];

        foreach ($files as $file) {
            if ($problem = $uploader->problem($file)) {
                $errors[] = $file->getClientOriginalName().': '.$problem;
                continue;
            }
            $path = $uploader->store($file);
            $saved[] = Media::where('path', $path)->first();
        }

        return response()->json(['ok' => $errors === [], 'items' => $saved, 'errors' => $errors], $saved || ! $errors ? 200 : 422);
    }

    public function update(Request $request, Media $media): JsonResponse
    {
        $data = $request->validate([
            'alt' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);
        $media->update($data);

        return response()->json(['ok' => true, 'item' => $media]);
    }

    public function destroy(Media $media): JsonResponse
    {
        $media->delete();

        return response()->json(['ok' => true]);
    }

    /** Add images already in public/uploads (e.g. copied from WordPress) to the library. */
    public function sync(): RedirectResponse
    {
        $dir = public_path('uploads');
        $added = 0;
        if (File::isDirectory($dir)) {
            $known = Media::pluck('path')->flip();
            foreach (File::allFiles($dir) as $file) {
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, ImageUploader::EXTENSIONS, true)) {
                    continue;
                }
                $path = 'uploads/'.str_replace('\\', '/', $file->getRelativePathname());
                if (isset($known[$path])) {
                    continue;
                }
                [$w, $h] = @getimagesize($file->getPathname()) ?: [null, null];
                Media::create([
                    'path' => $path,
                    'original_name' => $file->getFilename(),
                    'mime' => File::mimeType($file->getPathname()) ?: null,
                    'size' => $file->getSize(),
                    'width' => $w,
                    'height' => $h,
                    'alt' => Str::of($file->getFilenameWithoutExtension())->replaceMatches('/-\d+x\d+$/', '')->replace(['-', '_'], ' ')->squish()->title()->limit(120, '')->toString(),
                ]);
                $added++;
            }
        }

        return back()->with('status', "{$added}টা ছবি Media library-তে যোগ হয়েছে।");
    }
}
