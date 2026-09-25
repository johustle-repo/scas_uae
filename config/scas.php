<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded Admin Account
    |--------------------------------------------------------------------------
    |
    | Used by DatabaseSeeder to create (or update) the first SCAS admin
    | account so the app is usable immediately after a fresh migrate/seed.
    |
    */

    'admin' => [
        'name' => env('SCAS_ADMIN_NAME', 'Sofia Admin'),
        'email' => env('SCAS_ADMIN_EMAIL', 'sofia@scas.ae'),
        'password' => env('SCAS_ADMIN_PASSWORD', 'password123'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Adoption Enquiries
    |--------------------------------------------------------------------------
    |
    | How visitors to the public landing page can ask about adopting a dog.
    | Any value left empty is hidden on the page.
    |
    */

    'adoption_contact' => [
        'email' => env('SCAS_ADOPTION_EMAIL'),
        'phone' => env('SCAS_ADOPTION_PHONE'),
        'whatsapp' => env('SCAS_ADOPTION_WHATSAPP'),
        'instagram' => env('SCAS_ADOPTION_INSTAGRAM'),
    ],

];
