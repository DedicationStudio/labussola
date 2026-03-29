<?php

return [
    'binary' => env('PUPPETEER_EXECUTABLE_PATH', '/home/u893663406/.cache/puppeteer/chrome/linux-140.0.7339.82/chrome-linux64/chrome'),

    'browsershot' => [
        'noSandbox' => true,
        'node_binary' => env('LARAVEL_PDF_NODE_BINARY'),
        'npm_binary' => env('LARAVEL_PDF_NPM_BINARY'),
        'chrome_path' => env('PUPPETEER_EXECUTABLE_PATH'),
    ],
];

