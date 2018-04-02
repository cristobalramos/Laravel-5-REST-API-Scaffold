<?php

namespace Ramosmerino\Scaffold\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScaffoldFlushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scaffold:flush  {--F|force} {--full} {--db} {--mo|models} {--c|controllers} {--r|resources} {--f|factories} {--mi|migrations} {--s|seeders} {--t|tests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes anything created by scaffold:create.';

    private $connection;
    private $database;

    /**
     * List of all models. This determines the files to be removed.
     *
     * @var Collection
     */
    private $modelList;

    private $schema;
    private $tables;

    /**
     * Create a new command instance.

     */
    public function __construct()
    {
        parent::__construct();

        $this->connection = env('DB_CONNECTION');
        $this->database = env('DB_DATABASE');
    }

    private function getFunctionName($concept = 'tables')
    {
        return 'drop' . ucwords($this->connection) . ucwords($concept);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        extract($this->options());

        $this->populateModelList();

        if ($full) {
            $db = true;
            $models = true;
            $controllers = true;
            $resources = true;
            $factories = true;
            $migrations = true;
            $seeders = true;
            $tests = true;
        }

        if ($db) {
            if (!$force) {
                if (!$this->confirm("All tables, views, triggers and procedures will be deleted. Continue? [y|N]")) {
                    exit('DropTables command aborted.');
                }
            }

            $this->{'config' . ucwords($this->connection)}();

            DB::beginTransaction();
            $this->{$this->getFunctionName('tables')}();
            $this->comment(PHP_EOL . "Tables eliminated." . PHP_EOL);

            $this->{$this->getFunctionName('views')}();
            $this->comment(PHP_EOL . "Views eliminated." . PHP_EOL);

            $this->{$this->getFunctionName('triggers')}();
            $this->comment(PHP_EOL . "Triggers eliminated." . PHP_EOL);

            $this->{$this->getFunctionName('procedures')}();
            $this->comment(PHP_EOL . "Procedures eliminated." . PHP_EOL);
            DB::commit();
        }

        if ($models) {
            $this->removeModels();
            $this->info('Models removed');
        }
        if ($controllers) {
            $this->removeControllers();
        }
        if ($resources) {
            $this->removeResources();
        }
        if ($factories) {
            $this->removeFactories();
        }
        if ($migrations) {
            $this->removeMigrations();
        }
        if ($seeders) {
            $this->removeSeeds();
        }
        if ($tests) {
            $this->removeTests();
        }
    }

    /**
     * Get the list of model files.
     */
    protected function populateModelList()
    {
        $this->modelList = [];
        $files = new Collection(scandir($this->getModelsPath()));
        $this->modelList = $files->filter(function ($file) {
            return str_contains($file, '.php');
        })->map(function ($file) {
            return str_replace('.php', '', $file);
        });
    }

    /**
     * Get the default path where we store the models.
     *
     * @return string
     */
    protected function getModelsPath()
    {
        return base_path() . '/app/';
    }

    /**
     * Get the default path where we store the controllers.
     *
     * @return string
     */
    protected function getControllersPath()
    {
        return base_path() . '/app/Http/Controllers/';
    }

    /**
     * Get the default path where we store the resources.
     *
     * @return string
     */
    protected function getResourcesPath()
    {
        return base_path() . '/app/Http/Resources/';
    }

    /**
     * Get the default path where we store the factories.
     *
     * @return string
     */
    protected function getFactoriesPath()
    {
        return base_path() . '/database/factories/';
    }

    /**
     * Get the default path where we store the migrations.
     *
     * @return string
     */
    protected function getMigrationsPath()
    {
        return base_path() . '/database/migrations/';
    }

    /**
     * Get the default path where we store the seeds.
     *
     * @return string
     */
    protected function getSeedsPath()
    {
        return base_path() . '/database/seeds/';
    }

    /**
     * Get the default path where we store the tests.
     *
     * @return string
     */
    protected function getTestsPath()
    {
        return base_path() . '/tests/Feature';
    }

    protected function removeFile($file)
    {
        try {
            unlink($file);
        } catch (Exception $e) {
            $this->error($e);
        }
    }

    protected function removeModels()
    {
        $this->modelList->each(function ($file) {
            $this->removeFile($this->getModelsPath() . $file . '.php');
        });

    }

    protected function removeControllers()
    {
        $this->modelList->each(function ($file) {
            $this->removeFile($this->getControllersPath() . $file . 'Controller.php');
        });
    }

    protected function removeResources()
    {
        $this->modelList->each(function ($file) {
            $this->removeFile($this->getResourcesPath() . $file . 'Resource.php');
        });
    }

    protected function removeFactories()
    {
        $this->modelList->each(function ($file) {
            $this->removeFile($this->getFactoriesPath() . $file . 'Factory.php');
        });
    }

    protected function removeMigrations()
    {
        array_map('unlink', glob($this->getMigrationsPath() . '*.php'));
    }

    protected function removeSeeds()
    {
        $this->modelList->each(function ($file) {
            $this->removeFile($this->getSeedsPath() . $file . 'TableSeeder.php');
        });
    }

    protected function removeTests()
    {
        $this->modelList->each(function ($file) {
            $this->removeFile($this->getTestsPath() . $file . 'Test.php');
        });
    }

    private function dropMysqlTables()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        $colname = "Tables_in_{$this->database}";
        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $table) {
            $tablelist[] = $table->$colname;
        }
        $tablelist = implode(',', $tablelist);
        DB::statement("DROP TABLE {$tablelist}");
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

    }

    private function dropMysqlViews()
    {
        $colname = "Tables_in_{$this->database}";
        $views = DB::select("SHOW FULL TABLES IN {$this->database} WHERE TABLE_TYPE LIKE 'VIEW'");
        foreach ($views as $view) {
            $viewlist[] = $view->$colname;
        }
        $viewlist = implode(',', $viewlist);

        DB::statement("DROP VIEW {$viewlist}");
    }

    private function dropMysqlTriggers()
    {
        $colname = 'trigger';
        $triggers = DB::select("SHOW TRIGGERS");
        foreach ($triggers as $trigger) {
            DB::cstatement("DROP TRIGGER {$trigger->$colname}");
        }
    }

    private function dropMysqlProcedures()
    {
        $colname = 'Name';
        $procedures = DB::select("SHOW PROCEDURE STATUS WHERE db LIKE '{$this->database}'");
        foreach ($procedures as $procedure) {
            DB::statement("DROP PROCEDURE {$procedure->$colname};");
        }
    }

    private function configPgsql()
    {
        $tables = DB::table('pg_tables')->where('schemaname', 'public')->pluck('tablename');
        $this->tables = implode(',', $tables->toArray());
    }

    private function dropPgsqlTables()
    {
        DB::statement("DROP TABLE IF EXISTS {$this->tables} CASCADE");
    }

    private function dropPgsqlViews()
    {
        $views = DB::table('information_schema.views')->where([
          ['table_catalog', $this->database],
          ['table_schema', 'public']
        ])->pluck('table_name');
        if (empty($views)) {
            DB::statement("DROP VIEW {$views}");
        }
    }

    private function dropPgsqlTriggers()
    {
        $triggers = DB::table('information_schema.triggers')
          ->where('trigger_schema', 'public')
          ->pluck('trigger_name');
        if (empty($triggers)) {
            DB::statement("DROP TRIGGER {$triggers}");
        }
    }

    private function dropPgsqlProcedures()
    {
    }
}


