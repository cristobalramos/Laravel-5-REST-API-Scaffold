# Laravel 5 Extended Generators

[![Build Status](https://travis-ci.org/cristobalramos/Laravel-5-REST-API-Scaffold.svg?branch=master)](https://travis-ci.org/cristobalramos/Laravel-5-REST-API-Scaffold)

If you're familiar with [Laravel 5 Generators Extended](https://github.com/laracasts/Laravel-5-Generators-Extended), then this is basically the same thing but with only one command and some nice things.

## Commands
- `scaffold:create --model --plural --schema` Creates Migration, Seeder, Factory, Test, Model, Controller and Resources.
- `scaffold:flush` Drop Tables, Views, Triggers and Procedures without touching the Grants.

:warning: Only scaffold:create is full operative. Don't use scaffold:flush if you don't understand the code.

## Usage on Laravel 5.5

### Step 1: Install Through Composer

```
composer require ramosmerino/laravel-5-rest-api-scaffold --dev
```

### Step 2: Run Artisan

Run `php artisan` from the console, and you'll see the new commands in the `scaffold:*` namespace section.


## Example

```
php artisan scaffold:create --model=Dog --plural=Dogs --schema="name:string,age:tinyinteger"
```

In a fresh Laravel installation should return:

```
Model created successfully.
Controller created successfully.
Migration created successfully.
Seeder created successfully.
Resource created successfully.
Resource created successfully.
Factory created successfully.
Test created successfully.
```

app\Dog.php

```
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dog extends Model
{
    protected $fillable = [
        'name','age'
    ];
}
```

app\Http\Controllers\DogController.php
```
<?php

namespace App\Http\Controllers;

use App\Dog;
use Illuminate\Http\Request;

class DogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return new DogsResource(Dog::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $result = Dog::create($input);

        return response($result, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Dog $dog
     * @return \Illuminate\Http\Response
     * @internal param $id
     * @internal param \App\Dog $dog
     */
    public function show(Dog $dog)
    {
        return new DogResource($dog);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Dog             $dog
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Dog $dog)
    {
        $input = $request->all();
        $result = $dog->update($input);

        return response(['updated' => $result], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Dog $dog
     * @return \Illuminate\Http\Response
     */
    public function destroy(Dog $dog)
    {
        $result = $dog->delete();

        return response(['deleted' => $result], 202);
    }
}

```

