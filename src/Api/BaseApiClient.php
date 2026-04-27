<?php

namespace Ometra\Caronte\Api;

abstract class BaseApiClient
{
    protected static function http(): CaronteHttpClient
    {
        /** @var CaronteHttpClient $client */
        $client = app(CaronteHttpClient::class);

        return $client;
    }
}
