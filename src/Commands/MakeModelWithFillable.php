<?php

namespace GuiadorDigital\GenerateLaravelArchitectureCopilot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MakeModelWithFillable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:custom-seeder {name} {fields}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $fields = $this->argument('fields');

        $command = "make:seeder {$name}Seeder";
        Artisan::call($command);

        $filePath = base_path("database/seeders/{$name}Seeder.php");

        // Verifique se o arquivo existe
        if (file_exists($filePath)) {
            // Ler o conteúdo do arquivo
            $content = file_get_contents($filePath);

            // Percorre e trata os fields
            $fillable = $fields ? explode(',', $fields) : [];

            $codeToAdd = "";
            if ($fields) {
                foreach ($fillable as $value) {
                    $value = str_replace('*', '', $value);
                    $value = explode(':', $value)[0];
                    $codeToAdd .= "'{$value}' => '',\n            ";
                }
            }


            // Adiciona a Model na Seeder
            $content = str_replace('use Illuminate\Database\Seeder;', "use Illuminate\Database\Seeder;\nuse App\Models\\$name;", $content);

            // Substituir a primeira ocorrência de // pelos campos
            $contentField = "$name::create([
            {$codeToAdd}]);\n\n";
            $content = preg_replace('/\/\//', $contentField, $content, 1);

            // Add a chamada da factory
            // Encontrar todas as posições das chaves }
            preg_match_all('/}/', $content, $matches, PREG_OFFSET_CAPTURE);

            // Pegar a penúltima posição da chave }
            if (count($matches[0]) >= 2) {
                $lastBracketPos = $matches[0][count($matches[0]) - 2][1];

                // Inserir o conteúdo antes da penúltima chave }
                $content = substr_replace($content, "\n        $name::factory()->count(50)->create();\n    ", $lastBracketPos, 0);

                // Escrever o conteúdo de volta no arquivo
                file_put_contents($filePath, $content);

            } else {
                echo "Não foi possível encontrar a penúltima chave }.";
            }

            // Escrever o conteúdo de volta no arquivo
            file_put_contents($filePath, $content);

            // Add in DatabaseSeeder
            $databaseSeederPath = base_path('database/seeders/DatabaseSeeder.php');
            $nameSeeder = $name . 'Seeder';

            if (file_exists($databaseSeederPath)) {
                // Ler o conteúdo do arquivo
                $content = file_get_contents($databaseSeederPath);

                // Encontrar a posição do comentário
                $commentPos = strpos($content, '// Add new here');

                if ($commentPos !== false) {
                    // Inserir a linha antes do comentário
                    $content = substr_replace($content, "$nameSeeder::class,\n            ", $commentPos, 0);

                    // Escrever o conteúdo de volta no arquivo
                    file_put_contents($databaseSeederPath, $content);
                } else {
                    echo "Comentário não encontrado no arquivo DatabaseSeeder.";
                }
            } else {
                echo "O arquivo DatabaseSeeder.php não foi encontrado.";
            }
        } else {
            echo "Seeder não foi gerada.";
        }

    }
}