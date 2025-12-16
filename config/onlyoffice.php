<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OnlyOffice Document Server URL
    |--------------------------------------------------------------------------
    |
    | URL donde está corriendo el Document Server de OnlyOffice.
    | En desarrollo local con Docker, será http://localhost:8080
    |
    */
    'document_server_url' => env('ONLYOFFICE_SERVER_URL', 'http://localhost:8080'),

    /*
    |--------------------------------------------------------------------------
    | URL interna del Document Server
    |--------------------------------------------------------------------------
    |
    | URL que usa el servidor para comunicarse con OnlyOffice internamente.
    | En Docker, usa el nombre del servicio.
    |
    */
    'document_server_internal_url' => env('ONLYOFFICE_INTERNAL_URL', 'http://onlyoffice'),

    /*
    |--------------------------------------------------------------------------
    | URL de la aplicación para callbacks
    |--------------------------------------------------------------------------
    |
    | URL que OnlyOffice usa para llamar a tu aplicación (callbacks).
    | Debe ser accesible desde el contenedor de OnlyOffice.
    |
    */
    'callback_url' => env('ONLYOFFICE_CALLBACK_URL', 'http://app'),

    /*
    |--------------------------------------------------------------------------
    | JWT Secret (para producción)
    |--------------------------------------------------------------------------
    |
    | Clave secreta para firmar tokens JWT. Debe coincidir con la
    | configuración del Document Server.
    |
    */
    'jwt_secret' => env('ONLYOFFICE_JWT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | JWT habilitado
    |--------------------------------------------------------------------------
    |
    | En desarrollo se puede deshabilitar, pero en producción DEBE estar
    | habilitado por seguridad.
    |
    */
    'jwt_enabled' => env('ONLYOFFICE_JWT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Directorio de contratos
    |--------------------------------------------------------------------------
    |
    | Directorio donde se guardan los archivos de contratos.
    |
    */
    'storage_path' => storage_path('app/contratos'),

    /*
    |--------------------------------------------------------------------------
    | Formatos de documento permitidos
    |--------------------------------------------------------------------------
    */
    'allowed_formats' => ['docx', 'doc', 'odt', 'rtf', 'txt', 'html'],

    /*
    |--------------------------------------------------------------------------
    | Formato de salida por defecto
    |--------------------------------------------------------------------------
    */
    'default_format' => 'docx',
];
