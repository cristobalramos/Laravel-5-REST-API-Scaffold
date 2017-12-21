<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laracasts\Generators\Migrations\SchemaParser;

class ScaffoldCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scaffold:create {--model=} {--plural=} {--schema=} {--migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the Migration, Seeder, Factory, Test, Model, Controller and Resources';

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * Create a new command instance.
     *
     * @param \App\Console\Commands\Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = app()['composer'];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        extract($this->options());

        $this->makeModel();
        $this->makeController();

        $this->call('make:migration:schema',
          ['name' => 'create_' . strtolower($plural) . '_table', '--model' => ucwords($model), '--schema' => $schema]);
        $this->call('make:seeder', ['name' => ucwords($model) . 'TableSeeder']);
        $this->call('make:resource', ['name' => ucwords($model) . 'Resource']);
        $this->call('make:resource', ['name' => ucwords($plural) . 'Resource', '--collection']);
        $this->call('make:factory', ['name' => ucwords($model) . 'Factory', '--model' => ucwords($model)]);
        $this->call('make:test', ['name' => ucwords($model) . 'Test']);

        if ($migrate) {
            $this->call('migrate');
        }
    }

    protected function makeModel()
    {
        $path = $this->getModelPath();
        if ($this->files->exists($path)) {
            return $this->error('Model already exists!');
        }

        $this->files->put($path, $this->compileModelStub());
        $this->info('Model created successfully.');
        $this->composer->dumpAutoloads();
    }

    protected function makeController()
    {
        $path = $this->getControllerPath();
        if ($this->files->exists($path)) {
            return $this->error('Controller already exists!');
        }

        $this->files->put($path, $this->compileControllerStub());
        $this->info('Controller created successfully.');
        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the controller.
     *
     * @return string
     */
    protected function getModelPath()
    {
        return base_path() . '/app/' . ucwords($this->option('model')) . '.php';
    }

    /**
     * Get the path to where we should store the controller.
     *
     * @return string
     */
    protected function getControllerPath()
    {
        return base_path() . '/app/Http/Controllers/' . ucwords($this->option('model')) . 'Controller.php';
    }

    /**
     * Compile the model stub.
     *
     * @return string
     */
    protected function compileModelStub()
    {
        $stub = $this->files->get(__DIR__ . '/stubs/model.stub');
        $this->replaceModel($stub)
          ->fillFields($stub);

        return $stub;
    }

    /**
     * Compile the controller stub.
     *
     * @return string
     */
    protected function compileControllerStub()
    {
        $stub = $this->files->get(__DIR__ . '/stubs/controller.stub');
        $this->replaceController($stub)
          ->replaceModel($stub)
          ->replaceResource($stub);

        return $stub;
    }

    /**
     * Replace the class name in the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceController(&$stub)
    {
        $controllerClass = ucwords(camel_case($this->option('model') . 'Controller'));
        $stub = str_replace('{{controllerClass}}', $controllerClass, $stub);

        return $this;
    }

    /**
     * @param  string $stub
     * @return $this
     */
    protected function replaceModel(&$stub)
    {
        $modelClass = ucwords(camel_case($this->option('model')));
        $modelVar = strtolower($this->option('model'));
        $stub = str_replace('{{modelClass}}', $modelClass, $stub);
        $stub = str_replace('{{modelVar}}', $modelVar, $stub);

        return $this;
    }

    /**
     * @param  string $stub
     * @return $this
     */
    protected function replaceResource(&$stub)
    {
        $pluralResourceClass = ucwords(camel_case($this->option('plural') . 'Resource'));
        $singleResourceClass = ucwords(camel_case($this->option('model') . 'Resource'));
        $stub = str_replace('{{pluralResourceClass}}', $pluralResourceClass, $stub);
        $stub = str_replace('{{singleResourceClass}}', $singleResourceClass, $stub);

        return $this;
    }

    protected function fillFields(&$stub)
    {
        $schema = (new SchemaParser)->parse($this->option('schema'));
        $fields = array_map(function ($field) {
            return '\'' . $field . '\'';
        }, array_column($schema, 'name'));


        $filteredFields = array_filter($fields, function ($item) {
            return !str_contains($item, ['_id', 'deleted_at', 'created_at', 'updated_at']);
        });

        $stub = str_replace('{{fields}}', implode(',', $filteredFields), $stub);

        return $this;
    }
}
