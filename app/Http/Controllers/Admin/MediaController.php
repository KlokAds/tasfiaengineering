<?php

namespace App\Http\Controllers\Admin;

use App\Support\PerPage;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Support\MediaLibrary;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class MediaController extends Controller
{
    private const USAGE_CACHE = 'media.usage';

    public function index(Request $request)
    {
        $fresh = $request->boolean('refresh');
        $files = MediaLibrary::scan($fresh);
        if ($fresh) {
            Cache::forget(self::USAGE_CACHE);
        }
        $usage = Cache::remember(self::USAGE_CACHE, now()->addMinutes(3), fn () => MediaLibrary::usage());
        $meta = Media::whereIn('path', array_keys($files))->get()->keyBy('path');

        $all = collect($files)->map(fn ($info, $path) => [
            'path' => $path,
            'url' => '/' . implode('/', array_map('rawurlencode', explode('/', $path))),
            'name' => basename($path),
            'folder' => dirname($path),
            'ext' => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            'size' => $info[0],
            'modified' => date('c', $info[1]),
            'alt' => $meta[$path]->alt ?? null,
            'width' => $meta[$path]->width ?? null,
            'height' => $meta[$path]->height ?? null,
            'usages' => array_slice($usage[$path] ?? [], 0, 8),
            'usage_count' => count($usage[$path] ?? []),
        ])->values();

        $summary = [
            'total' => $all->count(),
            'total_size' => $all->sum('size'),
            'unused' => $all->where('usage_count', 0)->count(),
            'unused_size' => $all->where('usage_count', 0)->sum('size'),
            'missing' => count(array_diff_key($usage, $files)),
        ];

        $folders = $all->pluck('folder')->unique()->sort()->values();

        $filtered = $all
            ->when($request->input('status') === 'used', fn ($c) => $c->where('usage_count', '>', 0))
            ->when($request->input('status') === 'unused', fn ($c) => $c->where('usage_count', 0))
            ->when($request->filled('folder'), fn ($c) => $c->where('folder', $request->input('folder')))
            ->when($request->filled('search'), fn ($c) => $c->filter(fn ($f) => stripos($f['name'], $request->input('search')) !== false))
            ->sortByDesc(fn ($f) => $request->input('sort') === 'size' ? $f['size'] : $f['modified'])
            ->values();

        $perPage = PerPage::get($request, 50);
        $page = max(1, $request->integer('page', 1));

        return Inertia::render('Admin/Media/Index', [
            'files' => new LengthAwarePaginator($filtered->forPage($page, $perPage)->values(), $filtered->count(), $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]),
            'summary' => $summary,
            'folders' => $folders,
            'filters' => $request->only(['status', 'folder', 'search', 'sort', 'per_page']),
        ]);
    }

    /** JSON list of images for the editor's image picker. */
    public function browse(Request $request)
    {
        $files = collect(MediaLibrary::scan())
            ->filter(fn ($info, $path) => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), MediaLibrary::IMAGE_EXTENSIONS, true))
            ->when($request->filled('search'), fn ($c) => $c->filter(fn ($i, $path) => stripos(basename($path), $request->input('search')) !== false))
            ->sortByDesc(fn ($info) => $info[1]);

        $page = max(1, $request->integer('page', 1));
        $slice = $files->slice(($page - 1) * 40, 40);
        $meta = Media::whereIn('path', $slice->keys())->pluck('alt', 'path');

        return response()->json([
            'data' => $slice->map(fn ($info, $path) => [
                'path' => $path,
                'url' => '/' . implode('/', array_map('rawurlencode', explode('/', $path))),
                'name' => basename($path),
                'alt' => $meta[$path] ?? null,
            ])->values(),
            'has_more' => $files->count() > $page * 40,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'files' => 'required|array|max:20',
            'files.*' => 'file|max:8192|mimes:' . implode(',', MediaLibrary::ALLOWED_EXTENSIONS),
            'alt' => 'nullable|string|max:255',
        ], [
            'files.*.uploaded' => 'The file is larger than this server accepts (' . ini_get('upload_max_filesize') . '). Resize it, or raise upload_max_filesize in php.ini.',
            'files.*.max' => 'Files must be 8 MB or smaller.',
            'files.*.mimes' => 'Only JPG, PNG, WebP, GIF, AVIF and PDF files can be uploaded.',
        ]);

        $stored = [];
        foreach ($request->file('files') as $file) {
            $media = MediaLibrary::store($file, $request->user()->id, $request->input('alt'));
            $stored[] = [
                'path' => $media->path,
                'url' => '/' . implode('/', array_map('rawurlencode', explode('/', $media->path))),
                'alt' => $media->alt,
                'width' => $media->width,
                'height' => $media->height,
            ];
        }
        Cache::forget(self::USAGE_CACHE);

        if ($request->expectsJson()) {
            return response()->json(['files' => $stored]);
        }

        return redirect()->back()->with('success', count($stored) . ' file(s) uploaded.');
    }

    public function updateAlt(Request $request)
    {
        $data = $request->validate([
            'path' => 'required|string|max:512',
            'alt' => 'nullable|string|max:255',
        ]);
        $path = MediaLibrary::normalize($data['path']);
        abort_unless($path && array_key_exists($path, MediaLibrary::scan()), 404);

        Media::updateOrCreate(['path' => $path], ['alt' => $data['alt']]);

        return redirect()->back()->with('success', 'Alt text saved.');
    }

    public function destroy(Request $request)
    {
        $paths = $request->validate([
            'paths' => 'required|array|min:1|max:500',
            'paths.*' => 'string|max:512',
        ])['paths'];

        $usage = MediaLibrary::usage();
        $result = ['deleted' => 0, 'in_use' => 0, 'not_found' => 0];
        foreach ($paths as $path) {
            $result[MediaLibrary::delete($path, $usage)]++;
        }
        Cache::forget(self::USAGE_CACHE);

        $message = "{$result['deleted']} file(s) deleted.";
        if ($result['in_use']) {
            $message .= " {$result['in_use']} skipped because they are in use.";
        }

        return redirect()->back()->with('success', $message);
    }
}
