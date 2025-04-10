## About Laravel Crud Generator

Get a clone from git repository: https://github.com/os-salahuddin/crud.git
update environment with database such as driver:mysql, dbuser, dbpassword, dbName in .env file.

Run command to see Crud generation in action:
sudo php artisan make:crud Customer --fields="name:string, status:enum(active,inactive)" --relations="orders:hasMany"

This command will create CustomerContoller with resource route inside Api directory, Update api.php route file, add Model, Factory, migration, and view.
