<?php

namespace Ometra\Caronte\Support;

class CaronteApplicationContext
{
    public function __construct(
        public readonly string $appCn,
        public readonly string $appId,
        public readonly string $applicationToken,
    ) {
        //
    }
}
