<?php
namespace Tabaoman\Translation;

use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([ __DIR__ . '/../Config/translation.php' => config_path('translation.php') ], 'translation');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/translation.php', 'translation');
    }
}