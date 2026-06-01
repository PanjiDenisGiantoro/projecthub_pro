<?php

namespace App\Telescope;

use Illuminate\Database\Eloquent\Model;
use Laravel\Telescope\FormatModel;
use Laravel\Telescope\Watchers\RequestWatcher as BaseRequestWatcher;

class RequestWatcher extends BaseRequestWatcher
{
    /**
     * Extract the data from the given view in array form.
     *
     * PHP 8.x throws an Error when method_exists() or get_class() is called on
     * an __PHP_Incomplete_Class object (a class that was unserialized before its
     * definition was loaded). Guard against this so Telescope doesn't crash.
     *
     * @param  \Illuminate\View\View  $view
     * @return array
     */
    protected function extractDataFromView($view)
    {
        return collect($view->getData())->map(function ($value) {
            if ($value instanceof Model) {
                return FormatModel::given($value);
            } elseif (is_object($value)) {
                try {
                    return [
                        'class' => get_class($value),
                        'properties' => method_exists($value, 'formatForTelescope')
                            ? $value->formatForTelescope()
                            : json_decode(json_encode($value), true),
                    ];
                } catch (\Throwable $e) {
                    return [
                        'class' => get_class($value) ?: 'Incomplete',
                        'properties' => [],
                    ];
                }
            } else {
                return json_decode(json_encode($value), true);
            }
        })->toArray();
    }
}
