<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    protected $fillable = ['project_id', 'name', 'color'];

    // Available colors with their Tailwind CSS classes
    public static array $colors = [
        'blue'   => ['bg' => 'bg-blue-100',   'text' => 'text-blue-700',   'dot' => 'bg-blue-500'],
        'red'    => ['bg' => 'bg-red-100',    'text' => 'text-red-700',    'dot' => 'bg-red-500'],
        'green'  => ['bg' => 'bg-green-100',  'text' => 'text-green-700',  'dot' => 'bg-green-500'],
        'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'dot' => 'bg-yellow-500'],
        'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'dot' => 'bg-orange-500'],
        'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'dot' => 'bg-purple-500'],
        'pink'   => ['bg' => 'bg-pink-100',   'text' => 'text-pink-700',   'dot' => 'bg-pink-500'],
        'gray'   => ['bg' => 'bg-gray-100',   'text' => 'text-gray-700',   'dot' => 'bg-gray-500'],
        'teal'   => ['bg' => 'bg-teal-100',   'text' => 'text-teal-700',   'dot' => 'bg-teal-500'],
        'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'dot' => 'bg-indigo-500'],
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_labels');
    }

    public function colorClasses(): array
    {
        return self::$colors[$this->color] ?? self::$colors['gray'];
    }
}
