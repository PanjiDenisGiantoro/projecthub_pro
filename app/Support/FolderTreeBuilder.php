<?php

namespace App\Support;

use Illuminate\Support\Collection;

class FolderTreeBuilder
{
    /**
     * Ubah daftar path folder flat ("Docs", "Docs/Kontrak") jadi tree bersarang
     * untuk ditampilkan sebagai folder-di-dalam-folder di sidebar.
     */
    public static function build(Collection $paths): array
    {
        $tree = [];
        foreach ($paths as $path) {
            $node = &$tree;
            $currentPath = '';
            foreach (explode('/', $path) as $segment) {
                $currentPath = $currentPath === '' ? $segment : "{$currentPath}/{$segment}";
                $node[$segment] ??= ['path' => $currentPath, 'children' => []];
                $node = &$node[$segment]['children'];
            }
            unset($node);
        }

        return $tree;
    }
}
