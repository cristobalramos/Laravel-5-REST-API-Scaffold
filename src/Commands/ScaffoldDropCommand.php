<?php

namespace Ramosmerino\Scaffold\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ScaffoldDropCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scaffold:drop {--model=} {--plural=} {--drop="model,controller,resource,factory,migration,seeder,test"}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drops Migration, Seeder, Factory, Test, Model, Controller and Resources';

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
        if ($this->willDrop('model')) {
            $this->dropModel();
        }
        if ($this->willDrop('controller')) {
            $this->dropController();
        }
		if ($this->willDrop('resource')) {
			$this->dropResource();
		}
		if ($this->willDrop('factory')) {
			$this->dropFactory();
		}
		if ($this->willDrop('migration')) {
			$this->dropMigration();
		}
        if ($this->willDrop('seeder')) {
            $this->dropSeeder();
        }
		if ($this->willDrop('test')) {
			$this->dropTest();
		}
    }

    protected function willDrop($concept)
    {
        return str_contains($this->option('drop'), $concept);
    }
	
	protected function dropFile($type, ...$paths) {
		foreach($paths as $path) {
		if (!$this->files->exists($path)) {
            $this->error($type . ' don\'t exists!');
        } else {
        $this->files->delete($path);
        $this->info($type . ' drop successfully.');
        $this->composer->dumpAutoloads();
		}
		}
	}

    protected function dropModel()
    {
        $this->dropFile('Model', $this->getModelPath());
    }

    protected function dropController()
    {
        $this->dropFile('Controller', $this->getControllerPath());
    }
	
	protected function dropResource()
    {
        $this->dropFile('Resource', $this->getSingleResourcePath(), $this->getPluralResourcePath());
    }
	
	protected function dropFactory()
    {
        $this->dropFile('Factory', $this->getFactoryPath());
    }
	
	protected function dropMigration()
    {
        $this->dropFile('Migration', $this->getMigrationPath());
    }

    protected function dropSeeder()
    {
        $this->dropFile('Seeder', $this->getSeederPath());
    }
	
	protected function dropTest()
    {
        $this->dropFile('Test', $this->getTestPath());
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
     * @param  string $stub
     * @return $this
     */
    protected function getSingleResourcePath()
    {
		 return base_path() . '/app/Http/Resources/' . ucwords(camel_case($this->option('model') . 'Resource.php'));
    }
	
	protected function getPluralResourcePath()
    {
		return base_path() . '/app/Http/Resources/' . ucwords(camel_case($this->option('plural') . 'Resource.php'));
	}
	
	protected function getFactoryPath()
    {
		return base_path() . '/database/factories/' . ucwords($this->option('model') . 'Factory.php');
	}
	
	protected function getMigrationPath()
    {
		$path = base_path() . '/database/migrations/';
		$files = glob($path . '*.php');
		
		return ucwords($this->option('model') . 'Factory.php');
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
	
	protected function getTestPath()
    {
		return base_path() . '/tests/Feature/' . ucwords($this->option('model') . 'Test.php');
	}
}
