<?php

namespace Ramosmerino\Scaffold\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laracasts\Generators\Migrations\SchemaParser;

class ScaffoldCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scaffold:create {--model=} {--table=} {--schema=} {--single} {--json} {--flush} {--migrate} {--create="model,controller,resource,factory,migration,seeder,test"}';

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
     * @var
     */
    private $schema;

    /**
     * @var Array
     */
    private $fields;

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

        $this->schema = (new SchemaParser)->parse($schema);
        $this->fields = array_values(array_map(function ($field) {
            return '\'' . $field . '\'';
        }, array_filter(
            array_column($this->schema, 'name'),

            function ($item) {
                return !str_contains($item, ['_id', 'deleted_at', 'created_at', 'updated_at']);
            }
        )));

        if (!$table) {
            $table = $model;
        }

        if ($this->willCreate('model')) {
            $this->makeModel();
        }
        if ($this->willCreate('controller')) {
            $this->makeController();
        }
        if ($this->willCreate('seeder')) {
            $this->makeSeeder();
        }

        if ($this->willCreate('migration')) {
            $this->call(
                'make:migration:schema',
                [
                    'name' => 'create_' . strtolower($table) . '_table',
                    '--schema' => $schema
                ]
            );
        }
        if ($this->willCreate('resource')) {
            $this->call('make:resource', ['name' => ucwords($model) . 'Resource']);
        }
        if ($this->willCreate('factory')) {
            $this->call('make:factory', ['name' => ucwords($model) . 'Factory', '--model' => ucwords($model)]);
        }
        if ($this->willCreate('test')) {
            $this->call('make:test', ['name' => ucwords($model) . 'Test']);
        }

        if ($migrate) {
            $this->call('migrate');
        }
    }

    protected function willCreate($concept)
    {
        return str_contains($this->option('create'), $concept);
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

    protected function makeSeeder()
    {
        $path = $this->getSeederPath();
        if ($this->files->exists($path)) {
            return $this->error('Seed already exists!');
        }

        $this->files->put($path, $this->compileSeederStub());
        $this->info('Seeder created successfully.');
        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the model.
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
     * Get the path to where we should store the seeder.
     *
     * @return string
     */
    protected function getSeederPath()
    {
        return base_path() . '/database/seeds/' . ucwords($this->option('model')) . 'TableSeeder.php';
    }

    /**
     * Compile the model stub.
     *
     * @return string
     */
    protected function compileModelStub()
    {
        $stub = $this->files->get(__DIR__ . '/../stubs/model.stub');
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
        $stub = $this->files->get(__DIR__ . '/../stubs/controller.stub');
        $this->replaceController($stub)
            ->replaceModel($stub)
            ->replaceResource($stub);

        return $stub;
    }

    /**
     * Compile the seeder stub.
     *
     * @return string
     */
    protected function compileSeederStub()
    {
        if ($this->option('json')) {
            $stub = $this->files->get(__DIR__ . '/../stubs/seeder_json.stub');
            $this->replaceModel($stub)
                ->replaceTable($stub);
        } else {
            $stub = $this->files->get(__DIR__ . '/../stubs/seeder.stub');
            $this->replaceModel($stub)
                ->replaceTable($stub)
                ->fillFields($stub);
        }

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
    protected function replaceTable(&$stub)
    {
        $table = strtolower($this->option('table'));
        $stub = str_replace('{{table}}', $table, $stub);

        return $this;
    }

    /**
     * @param  string $stub
     * @return $this
     */
    protected function replaceResource(&$stub)
    {
        $resourceClass = ucwords(camel_case($this->option('model') . 'Resource'));
        $stub = str_replace('{{resourceClass}}', $resourceClass, $stub);

        return $this;
    }

    protected function fillFields(&$stub)
    {
        $stub = str_replace('{{fields}}', implode(',', $this->fields), $stub);

        return $this;
    }
}
