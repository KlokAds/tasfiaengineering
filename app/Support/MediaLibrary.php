<?php

namespace App\Support;

use App\Models\Media;
use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Files live under public/Admin. A file is "in use" when any image column or any
 * HTML body in the database points at it; those files can never be deleted here.
 */
class MediaLibrary
{
    public const ROOT = 'Admin';
    public const UPLOAD_DIR = 'Admin/Media';
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'pdf'];
    // Legacy SVGs are listed and protected, but new SVG uploads are refused (they can carry scripts).
    public const LISTED_EXTENSIONS = [...self::ALLOWED_EXTENSIONS, 'svg'];

    private const SCAN_CACHE = 'media.scan';

    /**
     * table => [label, edit url (id appended when it ends with "="), title column, image columns, html columns]
     */
    private const SOURCES = [
        'service_details' => ['Service', '/admin/services?edit=', 'name', ['image', 'bef_img', 'aft_img', 'og_image'], ['desc', 'short_summary']],
        'blog_details' => ['Article', '/admin/blogs?edit=', 'name', ['image', 'og_image'], ['desc', 'excerpt']],
        'service_categories' => ['Category', '/admin/service-categories?edit=', 'name', ['image'], ['intro', 'description']],
        'locations' => ['Location', '/admin/locations?edit=', 'name', ['image'], ['intro', 'description']],
        'project_details' => ['Project', '/admin/projects', 'name', ['image'], []],
        'home_heroes' => ['Hero slide', '/admin/hero', 'title', ['img'], ['short_desc']],
        'home_services' => ['Home service card', '/admin/home-static', 'name', ['img'], ['short_desc']],
        'home_statics' => ['Homepage section', '/admin/home-static', null, ['h_test_img'], []],
        'about_contents' => ['About page', '/admin/about', 'title', ['img_one', 'img_two', 'a_bread_img'], ['short_desc', 'subtitle']],
        'bread_cumbs' => ['Page header', '/admin/breadcrumbs', null, ['s_bread_image', 'p_bread_image', 'f_bread_image', 'b_bread_image'], []],
        'contact_contents' => ['Contact page', '/admin/settings/contact', null, ['c_bread_img'], []],
        'feed_back_contents' => ['Review', '/admin/reviews', 'name', ['img'], []],
        'partners' => ['Partner logo', '/admin/partners', null, ['image'], []],
        'footers' => ['Logo / footer', '/admin/settings/footer', null, ['main_logo', 'f_logo'], ['f_short_desc']],
        'faqs' => ['FAQ', '/admin/faqs', 'question', [], ['answer']],
    ];

    /** All files under public/Admin: [path => [size, modified]] */
    public static function scan(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::SCAN_CACHE);
        }

        return Cache::remember(self::SCAN_CACHE, now()->addMinutes(5), function () {
            $root = public_path(self::ROOT);
            $files = [];
            if (!is_dir($root)) {
                return $files;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (!$file->isFile() || !in_array(strtolower($file->getExtension()), self::LISTED_EXTENSIONS, true)) {
                    continue;
                }
                $relative = self::ROOT . '/' . str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                $files[$relative] = [$file->getSize(), $file->getMTime()];
            }

            return $files;
        });
    }

    /** path => list of usages. Always computed fresh; callers cache if needed. */
    public static function usage(): array
    {
        $map = [];
        $add = function (?string $raw, array $usage) use (&$map) {
            $path = self::normalize($raw);
            if ($path) {
                $map[$path][] = $usage;
            }
        };

        foreach (self::SOURCES as $table => [$label, $url, $titleCol, $imageCols, $htmlCols]) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $columns = array_values(array_filter(
                array_merge(['id', $titleCol], $imageCols, $htmlCols),
                fn ($c) => $c && Schema::hasColumn($table, $c)
            ));

            DB::table($table)->select($columns)->orderBy('id')->chunk(300, function ($rows) use ($label, $url, $titleCol, $imageCols, $htmlCols, $add) {
                foreach ($rows as $row) {
                    $usage = [
                        'label' => $label,
                        'title' => $titleCol ? Str::limit(strip_tags((string) ($row->{$titleCol} ?? '')), 70) : $label,
                        'url' => str_ends_with($url, '=') ? $url . $row->id : $url,
                    ];
                    foreach ($imageCols as $col) {
                        $add($row->{$col} ?? null, $usage + ['field' => $col]);
                    }
                    foreach ($htmlCols as $col) {
                        foreach (self::pathsInHtml($row->{$col} ?? null) as $p) {
                            $add($p, $usage + ['field' => $col . ' (in text)']);
                        }
                    }
                }
            });
        }

        try {
            $add(SiteSetting::get('seo.default_og_image'), ['label' => 'Settings', 'title' => 'Default share image', 'url' => '/admin/seo/settings', 'field' => 'seo.default_og_image']);
        } catch (\Throwable $e) {
        }

        return $map;
    }

    public static function isInUse(string $path, ?array $usage = null): bool
    {
        $usage ??= self::usage();

        return !empty($usage[self::normalize($path)]);
    }

    public static function store(UploadedFile $file, ?int $userId = null, ?string $alt = null): Media
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $dir = self::UPLOAD_DIR . '/' . now()->format('Y/m');
        $name = Str::limit($base, 80, '') . '-' . Str::lower(Str::random(5)) . '.' . $ext;

        $file->move(public_path($dir), $name);
        $path = $dir . '/' . $name;
        $full = public_path($path);
        $dims = in_array($ext, self::IMAGE_EXTENSIONS, true) ? @getimagesize($full) : false;

        Cache::forget(self::SCAN_CACHE);

        return Media::updateOrCreate(['path' => $path], [
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => filesize($full) ?: null,
            'width' => $dims[0] ?? null,
            'height' => $dims[1] ?? null,
            'alt' => $alt,
            'uploaded_by' => $userId,
        ]);
    }

    /** Deletes a file only if it is inside public/Admin and nothing references it. */
    public static function delete(string $path, ?array $usage = null): string
    {
        $path = self::normalize($path);
        $full = $path ? realpath(public_path($path)) : false;
        $root = realpath(public_path(self::ROOT));

        if (!$full || !$root || !str_starts_with($full, $root . DIRECTORY_SEPARATOR) || !is_file($full)) {
            return 'not_found';
        }
        if (self::isInUse($path, $usage)) {
            return 'in_use';
        }

        @unlink($full);
        Media::where('path', $path)->delete();
        Cache::forget(self::SCAN_CACHE);

        return 'deleted';
    }

    /** "/Admin/x%20y.jpg", "https://site/Admin/x y.jpg" and "Admin/x y.jpg" all become "Admin/x y.jpg". */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $raw = trim($raw);
        if (preg_match('#^https?://#i', $raw)) {
            $host = parse_url($raw, PHP_URL_HOST);
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            if ($host && $appHost && strcasecmp($host, $appHost) !== 0 && !str_contains($raw, '/' . self::ROOT . '/')) {
                return null;
            }
            $raw = (string) parse_url($raw, PHP_URL_PATH);
        }
        $raw = rawurldecode(strtok($raw, '?#'));
        $raw = ltrim(str_replace('\\', '/', $raw), '/');

        return str_starts_with($raw, self::ROOT . '/') ? $raw : null;
    }

    private static function pathsInHtml(?string $html): array
    {
        if (!$html || !str_contains($html, self::ROOT . '/')) {
            return [];
        }
        preg_match_all('#(?:src|href|srcset|data-src)\s*=\s*["\']([^"\']+)["\']#i', $html, $m);
        $paths = [];
        foreach ($m[1] as $value) {
            foreach (preg_split('/\s*,\s*/', $value) as $candidate) {
                $paths[] = explode(' ', trim($candidate))[0];
            }
        }
        preg_match_all('#url\(\s*["\']?([^"\')]+)#i', $html, $css);

        return array_merge($paths, $css[1]);
    }
}
