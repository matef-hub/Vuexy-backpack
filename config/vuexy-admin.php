<?php

return [
    // Use our Vuexy-styled layout by default
    'layout' => 'default',

    // Auth layout (if Backpack auth views request it)
    'auth_layout' => 'default',

    // Theme options (kept minimal to avoid tabler-specific behaviors)
    'options' => [
        'showColorModeSwitcher' => false,
        'useFluidContainers' => false,
    ],

    'classes' => [
        'body' => null,
        'table' => 'table table-hover border-top',
        'tableWrapper' => 'table-responsive',
    ],
];
