<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

class Crud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {model} {--fields=} {--relations=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD for a model with migrations, controller, views and so on';

    private Filesystem $files;
    private string $model;
    private string $fields;
    private string $relations;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->model = Str::studly($this->argument('model'));
            $this->fields = $this->option('fields');
            $this->relations = $this->option('relations');

            $this->_makeModel();
            $this->_makeController();
            $this->_makeFormRequest();
            $this->_makeRoutes();
            $this->_makeView();
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }

        $this->info("CRUD generation completed.");
    }

    private function _makeModel()
    {
        // Create the model and migration with Artisan
        Artisan::call("make:model {$this->model} -mf");

        // Paths to model file
        $modelPath = app_path("Models/{$this->model}.php");
        
        // Get the most recent migration file from the migrations folder
        $fileName = '*_create_' . strtolower($this->model);
        $migrationFiles = collect(glob(database_path("migrations/{$fileName}s_table.php")))
        ->sortByDesc(fn($file) => filemtime($file))
        ->first();

        // Path to the most recent migration file
        $migrationPath = $migrationFiles;

        // Parse fields and relations
        $fieldArray = $this->parseFields();
        $relationArray = $this->parseRelations();

        // Prepare the $fillable property for the model
        $fillable = "protected \$fillable = [" . implode(', ', array_map(fn($f) => "'{$f['name']}'", $fieldArray)) . "];";

        // Prepare the relationship methods for the model
        $relationMethods = '';
        foreach ($relationArray as $rel) {
            if ($rel['type'] === 'hasMany') {
                $relationMethods .= "\n    public function {$rel['name']}()\n    {\n        return \$this->hasMany(\\App\\Models\\{$rel['class']}::class);\n    }\n";
            } elseif ($rel['type'] === 'belongsTo') {
                $relationMethods .= "\n    public function {$rel['name']}()\n    {\n        return \$this->belongsTo(\\App\\Models\\{$rel['class']}::class);\n    }\n";
            }
        }

        // Read the existing model content and update it
        $content = $this->files->get($modelPath);
        $content = str_replace('use HasFactory;', "use HasFactory;\n\n  $fillable\n\n$relationMethods", $content);

        // Save the updated model file content
        $this->files->put($modelPath, $content);

        // Read the generated migration content
        $migrationContent = $this->files->get($migrationPath);

        // Prepare the schema for the migration
        $tableColumns = '';
        foreach ($fieldArray as $field) {
            if ($field['type'] === 'string') {
                $tableColumns .= "\$table->string('{$field['name']}');\n";
            } elseif ($field['type'] === 'text') {
                $tableColumns .= "\$table->text('{$field['name']}');\n";
            } elseif (strpos('enum(open,closed)', 'enum') !== false) {
                // Perform regex match to extract the values within the parentheses
                if (preg_match('/\((.*?)\)/', $field['type'], $matches)) {
                    // Split the matched values by comma and trim any surrounding spaces
                    $enumValues = array_map('trim', explode(',', $matches[1]));
            
                    // Now use the array of values in the enum field
                    $tableColumns .= "\$table->enum('{$field['name']}', " . var_export($enumValues, true) . ");\n";
                }
            } 
        }
        
        // Replace the $table->id() line with the new columns
        $migrationContent = str_replace('$table->id();', '$table->id();' . "\n            $tableColumns", $migrationContent);

        // Save the updated migration content
        $this->files->put($migrationPath, $migrationContent);
    }

    private function _makeController(): void
    {
        $modelName = Str::studly($this->model); // e.g., 'Project'
        $controllerName = "{$modelName}Controller"; // e.g., 'ProjectController'
        $modelVar = Str::camel($this->model); // e.g., 'project'
        $requestName = "{$modelName}Request"; // e.g., 'ProjectRequest'

        $controllerTemplate = <<<EOT
    <?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\\$modelName;
    use App\Http\Requests\\$requestName;
    use Illuminate\Http\Request;

    class $controllerName extends Controller
    {
        public function index()
        {
            return {$modelName}::all();
        }

        public function store({$requestName} \$request)
        {
            \${$modelVar} = {$modelName}::create(\$request->validated());
            return response()->json(\${$modelVar}, 201);
        }

        public function show({$modelName} \${$modelVar})
        {
            return response()->json(\${$modelVar});
        }

        public function update({$requestName} \$request, {$modelName} \${$modelVar})
        {
            \${$modelVar}->update(\$request->validated());
            return response()->json(\${$modelVar});
        }

        public function destroy({$modelName} \${$modelVar})
        {
            \${$modelVar}->delete();
            return response()->json(null, 204);
        }
    }
    EOT;

        $path = app_path("Http/Controllers/Api/{$controllerName}.php");
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $controllerTemplate);
    }


    private function _makeFormRequest(): void
    {
        $requestName = $this->model . 'Request';
        Artisan::call("make:request $requestName");

        $rules = collect($this->parseFields())
            ->map(function ($field) {
                return "'{$field['name']}' => '{$this->getValidationRule($field['type'])}'";
            })->implode(",\n            ");

        $path = app_path("Http/Requests/{$requestName}.php");
        $content = $this->files->get($path);
        $content = str_replace('return [', "return [\n            $rules", $content);
        $this->files->put($path, $content);
    }

    private function _makeRoutes(): void
    {
        $route = strtolower(Str::pluralStudly($this->model));
        $controller = "App\\Http\\Controllers\\Api\\{$this->model}Controller::class";
        $routesPath = base_path('routes/api.php');

        $routes = "\nRoute::apiResource('$route', $controller);";
        $this->files->append($routesPath, $routes);
    }

    private function _makeView(): void
    {
        $views = ['index', 'create', 'edit', 'show'];
        $folder = resource_path('views/' . strtolower(Str::pluralStudly($this->model)));
        $this->files->makeDirectory($folder, 0777, true, true);

        foreach ($views as $view) {
            $path = "$folder/$view.blade.php";
            $this->files->put($path, "<x-layout>\n<!-- $view view for {$this->model} -->\n</x-layout>");
        }
    }

    protected function parseFields(): array
    {
        return collect(explode(', ', $this->fields))->map(function ($field) {
            [$name, $type] = explode(':', trim($field));
            return ['name' => $name, 'type' => $type];
        })->toArray();
    }

    protected function parseRelations(): array
    {
        if (empty($this->relations)) return [];

        return collect(explode(',', $this->relations))->map(function ($rel) {
            [$name, $type] = explode(':', trim($rel));
            return [
                'name' => $name,
                'type' => $type,
                'class' => Str::studly(Str::singular($name)),
            ];
        })->toArray();
    }

    protected function getValidationRule(string $type): string
    {
        return match ($type) {
            'string' => 'required|string|max:255',
            'text' => 'nullable|string',
            'enum(open,closed)' => 'required|in:open,closed',
            'integer' => 'required|integer',
            'boolean' => 'nullable|boolean',
            default => 'nullable',
        };
    }
}
