<?php

$name = 'Postmark secret';

return [
    'plugin_description' => 'Postmark mail driver plugin',

    'fields' => [
        'postmark_secret' => [
            'label' => $name,
            'comment' => 'Enter your ' . $name,
        ],
    ],
];
