<?php

namespace Stats4SD\SqlViews\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A CLI tool that searches through the database/views folder, finds all .sql files and inserts them as new SQL views into the database.
 * Uses the filename as the View name.
 */
class UpdateSqlViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updatesql';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates / Replaces all mySQL Views in the database with those in the database/views folder, and runs any additional functions in the procedures folder.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (config('sqlviews.folder.create.procedures')) {
            $countProcs = $this->processProcsDir(base_path('database/procedures'));
            $this->info($countProcs . ' procedures run');
        }

        if (config('sqlviews.folder.create.views')) {
            $countViews = $this->processDir(base_path('database/views'));
            $this->info($countViews . ' views created');
        }
    }

    public function processProcsDir(string $dir_path)
    {
        $files = scandir($dir_path);
        $countProcs = 0;

        //iterate through subfolders and add to main set of files to check
        foreach ($files as $file) {
            if (is_dir("{$dir_path}/{$file}") && $file != '.' && $file != '..') {
                $folder_files = scandir("{$dir_path}/{$file}");

                // engage recursion...
                $this->processDir("{$dir_path}/{$file}");
            }
        }

        foreach ($files as $file) {
            if (Str::endsWith($file, '.sql')) {
                $query = file_get_contents("{$dir_path}/{$file}");

                $done = DB::unprepared($query);

                if ($done) {
                    $countProcs++;
                }
            }
        }

        return $countProcs;
    }


    /**
     * Takes a directory path and scans it (and all subfolders) for .sql files to turn into MySQL Views.
     * @param  string $dir_path path/to/mysql_view/storage
     * @return int      Count of views created or updated;
     */
    public function processDir(string $dir_path)
    {
        $files = scandir($dir_path);
        $countViews = 0;

        //iterate through subfolders and add to main set of files to check
        foreach ($files as $file) {
            if (is_dir("{$dir_path}/{$file}") && $file != '.' && $file != '..') {
                $folder_files = scandir("{$dir_path}/{$file}");

                // engage recursion...
                $this->processDir("{$dir_path}/{$file}");
            }
        }

        //reset all views to "placeholder" views - to avoid problems when a view relies on another view that is not yet created;
        foreach ($files as $file) {
            if (Str::endsWith($file, '.sql')) {
                $query = file_get_contents("{$dir_path}/{$file}");
                $name = Str::replaceLast('.sql', '', $file);

                $query = $this->makePlaceholderView($name, $query);
                $this->makeView($name, $query);
            }
        }

        //Need to iterate through all the files twice, to avoid creating a "final" view before all "placeholder" views are created;
        foreach ($files as $file) {
            if (Str::endsWith($file, '.sql')) {
                $query = file_get_contents("{$dir_path}/{$file}");
                $name = Str::replaceLast('.sql', '', $file);

                $done = $this->makeView($name, $query);
                if ($done) {
                    $countViews++;
                }
            }
        }

        return $countViews;
    }

    /**
     * Creates or updates the mySQL View.
     * @param  string $name  Name of the view
     * @param  string $query Query to use for the View
     * @return bool       Successfully added View
     */
    public function makeView(string $name, string $query)
    {
        $view = "CREATE OR REPLACE VIEW {$name} AS \n{$query}";

        return DB::statement($view);
    }

    /**
     * Craetes a "placeholder" MySQL View. This placeholder is in the same format as used by mysqldump, e.g:
     *     SELECT
     *         1 as column_one_name,
     *         1 as column_two_name,
     *         ...
     * This means it requires no other tables or views to exist;.
     * @param  string $name  Name for the MySQL View
     * @param  string $query Query for the real MySQL View
     * @return string        Query for "placeholder" MySQL View
     */
    public function makePlaceholderView(string $name, string $query)
    {
        $query = strtolower($query);

        //Determine if subqueries are used anywhere:
        //(Subqueries after the main
        if (substr_count($query, 'select') > 1) {
            $test = explode('from', $query);

            // if there is a second SELECT before the first FROM, the query includes subqueries in the main segment:
            if (substr_count($test[0], 'select') > 1) {
                $pos = 0;
                $selectCount = substr_count($test[$pos], 'select');

                $query = '';

                while ($selectCount > 0) {
                    if (!isset($test[$pos])) {
                        dd($file, $test);
                    }
                    $query .= $test[$pos];
                    $selectCount = substr_count($test[$pos], 'select');
                    $pos++;
                }
            }

            //subqueries are only used in JOINs on the main query...
            else {
                $query = $test[0];
            }

            //add back "FROM" so regex below will still work fine:
            $query .= ' FROM';
        }

        //strip everything FROM onward
        preg_match_all('/SELECT(.*)FROM/is', $query, $stripped);

        //remove excess spaces and newlines
        $qstr = trim(preg_replace('/\s+/', ' ', $stripped[1][0]));

        //Split by commas, but only ones not inside a function (within brackets)
        $qarray = $this->parseBrackets($qstr, ',');

        $columnNames = array_map(function ($item) {
            if (Str::contains($item, 'as ')) {
                $name = Str::afterLast($item, 'as ');
            } else {
                $segments = preg_split('/\.(?![^\(]*\))/s', $item);
                $name = Arr::last($segments);
            }

            //if backticks are used, just grab everything between the backticks
            //...but ignore anything inside brackets, as that denotes a mysql function...
            if (preg_match('/\`(?![^\(]*\))/s', $name)) {
                preg_match('/\`(?![^\(]*\))(.+)\`(?![^\(]*\))/', $name, $colName);

                return $colName[1];
            }

            //strip spaces
            $colName = preg_replace('/\s+/', '', $name);

            return $colName;
        }, $qarray);

        //now, define a placeholder view using the exact column-names to be used in the final view:
        $tempQuery = array_map(function ($item) {
            return "1 AS `{$item}`";
        }, $columnNames);

        $tempQueryStr = implode(', ', $tempQuery) . ';';

        return 'SELECT ' . $tempQueryStr;
    }

    /**
     * Function to split a string by substring, but ONLY when the substring is not within brackets.
     *         // Taken from Gumbo's answer at Stack Overflow:
     *         https://stackoverflow.com/questions/1084764/php-and-regex-split-a-string-by-commas-that-are-not-inside-brackets-and-also-n.
     * @param  string $str The haystack - the string to search within
     * @param  string $substr The needle - the string to split by
     * @return array     An array containing the results of the split
     */
    public function parseBrackets(string $str, $substr)
    {
        $buffer = '';
        $stack = [];
        $depth = 0;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $char = $str[$i];
            switch ($char) {
                case '(':
                    $depth++;
                    break;
                case $substr:
                    if (!$depth) {
                        if ($buffer !== '') {
                            $stack[] = $buffer;
                            $buffer = '';
                        }
                        continue 2;
                    }
                    break;
                case ')':
                    if ($depth) {
                        $depth--;
                    } else {
                        $stack[] = $buffer . $char;
                        $buffer = '';
                        continue 2;
                    }
                    break;
            }
            $buffer .= $char;
        }
        if ($buffer !== '') {
            $stack[] = $buffer;
        }

        return $stack;
    }
}
