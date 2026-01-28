<?php

return [
    // Add doctor names here to populate the dropdown.
    'doctors' => [
        'Dr. Floren Kavaja',
        'Dr. Besnik Bicaj',
        'Dr. Hajdin Thaqi',
    ],

    // Room configuration per floor
    // beds: number of beds in each room
    'floors' => [
        1 => [
            'name' => 'Kati 1',
            'rooms' => [
                1 => ['beds' => 2],
                2 => ['beds' => 2],
                3 => ['beds' => 2],
                4 => ['beds' => 2],
                5 => ['beds' => 2],
                6 => ['beds' => 2],
                7 => ['beds' => 2],
            ],
        ],
        2 => [
            'name' => 'Kati 2',
            'rooms' => [
                1 => ['beds' => 2],
                2 => ['beds' => 2],
                3 => ['beds' => 2],
                4 => ['beds' => 2],
                5 => ['beds' => 2],
                6 => ['beds' => 1],
                7 => ['beds' => 3],
            ],
        ],
    ],
];
