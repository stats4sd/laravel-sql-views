<?php

return [
    'mysql_views_path' => 'database/views',

    'folder' => [
        'create' => [
            'views' => env('SQL_VIEWS_REGISTER_VIEWS_FOLDER', true),
            'procedures' => env('SQL_VIEWS_REGISTER_PROCEDURES_FOLDER', true)
        ]
    ]
];
