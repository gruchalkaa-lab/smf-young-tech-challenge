<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Symfonia Young Tech Challenge API',
    version: '1.0.0',
    description: 'API do zarządzania fakturami z automatycznym OCR i ekstrakcją danych przez prostego agenta regułowego.'
)]
#[OA\Server(
    url: '/api',
    description: 'Serwer lokalny'
)]
class OpenApiSpec
{
    //
}