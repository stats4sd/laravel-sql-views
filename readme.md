# SqlViews

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![StyleCI][ico-styleci]][link-styleci]

A small package to help automate the creation and updating of MySQL views and procedures.

This package lets you store the query definition for every MySQL view and stored procedure you need within your Laravel codebase, and lets you edit the SQL query strings directly instead of using migration files. This is useful when:

  - You regularly create or update SQL views with real data, e.g. as part of a data analysis workflow.
  - You have a set of complex queries that would involve you basically writing out the raw query within a migration file anyway.


## Installation

Via Composer

``` bash
$ composer require stats4sd/laravel-sql-views
```

## Usage

The package includes a single console command `php artisan updatesql`. This command does 2 things: 

1. It will create or update the SQL views in your database based on the files within your `database/views` directory. It will create a view for every `.sql` file it finds, using the filename as the view name and the contents as the query definition.
2. It will create or update SQL procedures in your database based on the files within your `database/procedures` directory. It will run all `.sql` files in that directly verbatim. The intention is that you would include scripts to create the required procedures, but in theory you could include any SQL code that you want to run on a regular basis. 


To use:

1. Place your query definitions within .sql files inside your `database/views` directory. You need 1 file per view. Do not include the "CREATE OR REPLACE VIEW" segment, just include the query definition itself.
2. Place any additional SQL scripts you want to run, e.g. to create stored procedures, into the `database/procedures` folder. 
3. Run the command with `php artisan updatesql`.


It will search the folders recursively, so you can organise your views into subfolders if you wish.

The packge comes with a tiny config file.
```
php artisan vendor:publish --tag=sqlviews.config
```

To disable the automatic creation of either the `database/views` and/or `database/procedures` folder(s) you can add the following `.env` variables :-
```
SQL_VIEWS_REGISTER_VIEWS_FOLDER=false
SQL_VIEWS_REGISTER_PROCEDURES_FOLDER=false
```
Please note that disabling folder creation without a good understanding of this package will cause you issues.

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.


## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [Dave Mills][https://github.com/dave-mills]
- [Stats4SD][link-contributors]

## License

MIT license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/stats4sd/laravel-sql-views?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/stats4sd/laravel-sql-views?style=flat-square

[ico-styleci]: https://github.styleci.io/repos/248978421/shield

[link-packagist]: https://packagist.org/packages/stats4sd/laravel-sql-views
[link-downloads]: https://packagist.org/packages/stats4sd/laravel-sql-views
[link-styleci]: https://github.styleci.io/repos/248978421
[link-author]: https://github.com/stats4sd
[link-contributors]: https://github.com/stats4sd
