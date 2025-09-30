<?php

use Hanafalah\ModuleUser\{
    Commands as ModuleUserCommands,
};

return [
    'namespace' => 'Hanafalah\\ModuleUser',
    'app' => [
        'contracts' => [
            //ADD YOUR CONTRACTS HERE
        ],
    ],
    'libs' => [
        'model' => 'Models',
        'contract' => 'Contracts',
        'schema' => 'Schemas',
        'database' => 'Database',
        'data' => 'Data',
        'resource' => 'Resources',
        'migration' => '../assets/database/migrations'
    ],
    'database' => [
        'models' => [
            //ADD YOUR MODELS HERE
        ]
    ],
    'commands' => [
        ModuleUserCommands\InstallMakeCommand::class
    ],
    'user_reference_types' => [
        // 'employee' => [
        //     'schema' => 'Employee',
        // ]
    ],
    'reference'  => null, //ADD YOUR WORKSPACE MODEL HERE
    'workspace' => null //ADD YOUR WORKSPACE MODEL HERE
];
