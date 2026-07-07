<?php

return [
    'base_url' => env('DOCHUB_BASE_URL', 'https://your-base-api-url'),
    'username' => env('DOCHUB_USERNAME', 'your_username'),
    'password' => env('DOCHUB_PASSWORD', 'your_password'),
    'company_id' => env('DOCHUB_COMPANY_ID', 164),

    'document' => [
        'type_id' => env('DOCHUB_DOCUMENT_TYPE_ID', 54),
        'department_id' => env('DOCHUB_DEPARTMENT_ID', 33),
        'file_path' => env('DOCHUB_DOCUMENT_FILE', storage_path('app/samples/sample.pdf')),
    ],

    'process' => [
        'user_code' => env('DOCHUB_PROCESS_USER_CODE', 'BAOTH'),
        'position' => env('DOCHUB_SIGN_POSITION', '14,478,206,568'),
        'page_sign' => env('DOCHUB_SIGN_PAGE', 1),
    ],

    'batch_import' => [
        'document_template_id' => env('DOCHUB_TEMPLATE_ID', 1141),
        'document_type_id' => env('DOCHUB_BATCH_DOCUMENT_TYPE_ID', 1089),
        'department_id' => env('DOCHUB_BATCH_DEPARTMENT_ID', 33),
        'user_code' => env('DOCHUB_BATCH_USER_CODE', 'baoth'),
        'rows' => env('DOCHUB_BATCH_ROWS', 2),
    ],
];
