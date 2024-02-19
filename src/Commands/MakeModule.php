<?php

namespace GuiadorDigital\GenerateLaravelArchitectureCopilot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name} {model?} {--fields= : Campos separados por vírgula}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a complete CRUD';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $name = $this->argument('name');

        if ($this->argument('model') == null) {
            $model = $this->argument('name');
        }

        $fields = $this->option('fields');

        $this->info("Começando a criação dos arquivos");

        // Model
        $command = "make:model-fields {$name} {$fields}";
        Artisan::call($command);
        $this->info("Model criada com sucesso");

        // Seeder
        $command = "make:custom-seeder {$name} {$fields}";
        Artisan::call($command);
        $this->info("Seeder criada com sucesso");
    }
}