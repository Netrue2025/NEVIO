<?php

return [
    'template_path' => storage_path('app/public/templates/birthday-template.png'),

    'canvas' => [
        'width' => 1080,
        'height' => 1080,
    ],

    'photo' => [
        // Centered inside the gold circle
        'x' => 254,
        'y' => 283,
        'width' => 515,
        'height' => 515,
        'border_radius' => 50,
        'border_width' => 0,
        'border_color' => '#ffffff',
    ],

    'name' => [
        // Below the circle, still inside card
        'x' => 520,
        'y' => 850,
        'font_size' => 64,
        'font_path' => public_path('fonts/Montserrat-Bold.ttf'),
        'color' => '#000000',
        'align' => 'center',
        'max_width' => 900,
    ],

    'age' => [
        // Now used for DATE at top-left before ribbon
        'x' => 10,
        'y' => 50,
        'font_size' => 38,
        'font_path' => public_path('fonts/Montserrat-Bold.ttf'),
        'color' => '#bf6c0e',
        'align' => 'left',
        'prefix' => '',
        'suffix' => '',
    ],

    // Message removed (no text will render)
];
