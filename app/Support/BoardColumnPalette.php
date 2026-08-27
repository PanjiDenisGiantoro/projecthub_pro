<?php

namespace App\Support;

class BoardColumnPalette
{
    /**
     * Whitelisted color keys for board columns. Tailwind v4 statically scans
     * source files for literal class names (no runtime JIT), so a column's
     * color must resolve to one of these pre-written entries rather than
     * being interpolated into a class string at render time.
     */
    private const MAP = [
        'gray' => [
            'dot' => 'bg-gray-400',
            'hdr' => 'bg-gray-50 border-gray-200',
            'badge' => 'bg-gray-100 text-gray-600',
        ],
        'blue' => [
            'dot' => 'bg-blue-500',
            'hdr' => 'bg-blue-50 border-blue-100',
            'badge' => 'bg-blue-100 text-blue-700',
        ],
        'purple' => [
            'dot' => 'bg-purple-500',
            'hdr' => 'bg-purple-50 border-purple-100',
            'badge' => 'bg-purple-100 text-purple-700',
        ],
        'green' => [
            'dot' => 'bg-green-500',
            'hdr' => 'bg-green-50 border-green-100',
            'badge' => 'bg-green-100 text-green-700',
        ],
        'red' => [
            'dot' => 'bg-red-500',
            'hdr' => 'bg-red-50 border-red-100',
            'badge' => 'bg-red-100 text-red-700',
        ],
        'amber' => [
            'dot' => 'bg-amber-500',
            'hdr' => 'bg-amber-50 border-amber-100',
            'badge' => 'bg-amber-100 text-amber-700',
        ],
        'pink' => [
            'dot' => 'bg-pink-500',
            'hdr' => 'bg-pink-50 border-pink-100',
            'badge' => 'bg-pink-100 text-pink-700',
        ],
        'indigo' => [
            'dot' => 'bg-indigo-500',
            'hdr' => 'bg-indigo-50 border-indigo-100',
            'badge' => 'bg-indigo-100 text-indigo-700',
        ],
        'teal' => [
            'dot' => 'bg-teal-500',
            'hdr' => 'bg-teal-50 border-teal-100',
            'badge' => 'bg-teal-100 text-teal-700',
        ],
        'slate' => [
            'dot' => 'bg-slate-500',
            'hdr' => 'bg-slate-50 border-slate-100',
            'badge' => 'bg-slate-100 text-slate-700',
        ],
    ];

    public static function keys(): array
    {
        return array_keys(self::MAP);
    }

    public static function dot(?string $color): string
    {
        return self::MAP[$color]['dot'] ?? self::MAP['gray']['dot'];
    }

    public static function header(?string $color): string
    {
        return self::MAP[$color]['hdr'] ?? self::MAP['gray']['hdr'];
    }

    public static function badge(?string $color): string
    {
        return self::MAP[$color]['badge'] ?? self::MAP['gray']['badge'];
    }
}
