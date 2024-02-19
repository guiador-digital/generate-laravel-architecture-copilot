<?php

namespace GuiadorDigital\GenerateLaravelArchitectureCopilot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MakeModelWithFillable extends Command
{
    protected $signature = 'make:model-fields {name} {fields}';

    protected $description = 'Create model with fields';

    public function handle()
    {
        $name = $this->argument('name');
        $fields = $this->argument('fields');

        $fieldDefinitions = $this->parseFieldDefinitions($fields);

        // Executa o comando 'make:model' para criar a model e a migration
        Artisan::call("make:model {$name} -m");

        // Obter o nome da migration gerada
        $migrationName = 'create_' . Str::snake(Str::pluralStudly($name)) . '_table';
        $migrationFiles = glob(database_path('migrations/*' . $migrationName . '.php'));
        $migrationFile = count($migrationFiles) > 0 ? $migrationFiles[0] : null;

        if ($migrationFile) {
            // Abre o arquivo da migration
            $migrationContents = file_get_contents($migrationFile);

            // Encontra a posição do fechamento do método up()
            $pos = strpos($migrationContents, 'id();');
            if ($pos !== false) {
                // Insere os campos fillable após o ID na migration
                $fillableColumns = collect($fieldDefinitions)->map(function ($definition, $column) {
                    $commandColumnLine = '';

                    // Verifica se o campo é obrigatório
                    $isRequired = false;
                    $posRequired = strpos($column, '*');
                    if ($posRequired != false) {
                        $isRequired = true;
                    }

                    // Trata se o campo for foreign key
                    if ($definition['type'] == 'fk') {
                        $column = str_replace('*', '', $column);
                        $commandColumnLine .= "foreignId('{$column}')";

                        if ($isRequired == false) {
                            $commandColumnLine .= "->nullable()";
                        }

                        $commandColumnLine .= "->constrained('table')->onUpdate('cascade')->onDelete('cascade');";
                        return "    \$table->{$commandColumnLine};";
                    }

                    $column = str_replace('*', '', $column);
                    $commandColumnLine .= "{$definition['type']}('{$column}')";
                    if ($isRequired == false) {
                        $commandColumnLine .= "->nullable()";
                    }

                    return "    \$table->{$commandColumnLine};";
                })->implode("\n        ");

                $migrationContents = substr_replace($migrationContents, "id();\n        {$fillableColumns}\n\n        ", $pos, 5);

                $pos = strpos($migrationContents, 'timestamps();');
                $migrationContents = substr_replace($migrationContents, "timestamps();\n            \$table->softDeletes();", $pos, 13);
            }

            // Salva o arquivo com os campos fillable atualizados
            file_put_contents($migrationFile, $migrationContents);

            $this->info("Campos adicionados com sucesso à migration {$migrationName}.");
        } else {
            $this->error("Não foi possível encontrar a migration para adicionar os campos.");
        }

        /*
        |--------------------------------------------------------------------------
        | MODEL
        |--------------------------------------------------------------------------
        */

        // Abre o arquivo da model recém-criada
        $modelFilePath = app_path("Models/{$name}.php");
        $modelContents = file_get_contents($modelFilePath);

        $modelContents = Str::replaceFirst('class', "use Illuminate\Database\Eloquent\SoftDeletes;\n\nclass", $modelContents);

        // Adiciona os campos fillable na model
        $fillableDeclaration = !empty($fieldDefinitions) ? "\n    protected \$guarded = ['id'];\n" : "";
        $fillableDeclaration = str_replace('*', '', $fillableDeclaration);
        // dd($fieldDefinitions);

        $fks = [];

        foreach ($fieldDefinitions as $key => $value) {
            if ($value["type"] == 'fk') {
                array_push($fks, str_replace("*", "", $key));
            }
        }

        $todoRememberRelationshipDeclaration = "";
        foreach ($fks as $value) {
            $todoRememberRelationshipDeclaration .= "   // TODO fazer relacionamento de {$value}\n";
        }

        $modelContents = Str::replaceFirst('use HasFactory;', "use HasFactory;\n {$fillableDeclaration}\n\n{$todoRememberRelationshipDeclaration}", $modelContents);

        // Adiciona o uso do SoftDeletes e o softDelete na model
        $modelContents = Str::replaceFirst('class', "class", $modelContents);
        $modelContents = Str::replaceFirst('{', "{\n    use SoftDeletes;", $modelContents);

        // Salva o arquivo com os campos fillable atualizados
        file_put_contents($modelFilePath, $modelContents);

        $this->info("Model $name criada com sucesso.");
    }

    protected function parseFieldDefinitions($fields)
    {
        $definitions = [];
        $fieldList = explode(',', $fields);

        foreach ($fieldList as $field) {
            $parts = explode(':', $field, 2);
            $columnName = $parts[0];
            $columnType = count($parts) > 1 ? $parts[1] : 'string';
            $nullable = strpos($columnType, '*') === false ? true : false;
            $columnType = str_replace('*', '', $columnType); // Remove o *
            $definitions[$columnName] = ['type' => $columnType, 'nullable' => $nullable];
        }

        return $definitions;
    }
}