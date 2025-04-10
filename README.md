## About Laravel Crud Generator

Get a clone from git repository: https://github.com/os-salahuddin/crud.git
update environment with database such as driver:mysql, dbuser, dbpassword, dbName in .env file.

Run command to see Crud generation in action:
sudo php artisan make:crud Project --fields="name:string, status:enum(open,closed)" --relations="tasks:hasMany

This command will create ProjectContoller with resource route inside Api directory, Update api.php route file, add Model, Factory, migration, and view.
