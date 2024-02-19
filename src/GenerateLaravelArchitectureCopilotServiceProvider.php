<?php

namespace GuiadorDigital\GenerateLaravelArchitectureCopilot;

use Illuminate\Support\ServiceProvider;

class GenerateLaravelArchitectureCopilotServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \GuiadorDigital\GenerateLaravelArchitectureCopilot\Commands\MakeModule::class,
                \GuiadorDigital\GenerateLaravelArchitectureCopilot\Commands\MakeModelWithFillable::class,
                \GuiadorDigital\GenerateLaravelArchitectureCopilot\Commands\MakeCustomSeeder::class,
            ]);
        }
    }
}
