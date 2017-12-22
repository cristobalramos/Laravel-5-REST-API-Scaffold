<?php

namespace Ramosmerino\Scaffold\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScaffoldFlushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scaffold:flush {--full} {--flushdb} {--models=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes anything created by scaffold:create.';

    private $connection;
    private $database;

    /**
     * Create a new command instance.

     */
    public function __construct()
    {
        parent::__construct();
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
        $this->connection = env('DB_CONNECTION');
        $this->database = env('DB_DATABASE');

        if (!$this->confirm("All tables, views, triggers and procedures will be deleted. Continue? [y|N]")) {
            exit('DropTables command aborted.');
        }

        DB::beginTransaction();
        if (!$this->option('full')) {
            $this->{$this->getFunctionName('tables')}();
            $this->comment(PHP_EOL . "Tables eliminated." . PHP_EOL);
        } else {
            $this->{$this->getFunctionName('views')}();
            $this->comment(PHP_EOL . "Views eliminated." . PHP_EOL);

            $this->{$this->getFunctionName('triggers')}();
            $this->comment(PHP_EOL . "Triggers eliminated." . PHP_EOL);

            $this->{$this->getFunctionName('procedures')}();
            $this->comment(PHP_EOL . "Procedures eliminated." . PHP_EOL);
        }

        DB::commit();

        $this->comment(PHP_EOL . "If no errors showed up, all tables were dropped." . PHP_EOL);
    }

    private function dropMysqlTables()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        $colname = "Tables_in_{$this->database}";
        $tables = DB::select('SHOW TABLES');

        // If there's no tables
        if (!$tables) {
            return;
        }
        foreach ($tables as $table) {
            $tablelist[] = $table->$colname;
        }
        $tablelist = implode(',', $tablelist);
        DB::connection()->getPdo()->exec("DROP TABLE {$tablelist}");
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

    }

    private function dropMysqlViews()
    {
        $colname = "Tables_in_{$this->database}";
        $views = DB::select("SHOW FULL TABLES IN {$this->database} WHERE TABLE_TYPE LIKE 'VIEW'");
        // If there's no views
        if (!$views) {
            return;
        }
        foreach ($views as $view) {
            $viewlist[] = $view->$colname;
        }
        $viewlist = implode(',', $viewlist);

        DB::connection()->getPdo()->exec("DROP VIEW {$viewlist}");
    }

    private function dropMysqlTriggers()
    {
        $colname = 'trigger';
        $triggers = DB::select("SHOW TRIGGERS");
        // If there's no triggers
        if (!$triggers) {
            return;
        }
        foreach ($triggers as $trigger) {
            DB::connection()->getPdo()->exec("DROP TRIGGER {$trigger->$colname}");
        }
    }

    private function dropMysqlProcedures()
    {
        $colname = 'Name';
        $procedures = DB::select("SHOW PROCEDURE STATUS WHERE db LIKE '{$this->database}'");
        if (!$procedures) {
            return;
        }
        foreach ($procedures as $procedure) {
            DB::connection()->getPdo()->exec("DROP PROCEDURE {$procedure->$colname};");
        }
    }

    private function dropPgsqlTables()
    {
        $schemas = DB::table('information_schema.schemata')->pluck('schema_name')->filter(function ($item) {
            return ! str_contains($item, ['pg_', 'information_schema']);
        })->values();

        $tables = DB::table('pg_tables')->whereIn('schemaname', $schemas)->pluck('tablename');

        if (!$tables) {
            return;
        }

        $tablelist = implode(',', $tables->toArray());
        DB::connection()->getPdo()->exec("DROP TABLE IF EXISTS {$tablelist} CASCADE");
    }
}

