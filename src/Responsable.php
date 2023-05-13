<?php

namespace IsraelNogueira\fastRouter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Responsable
{
    public function toResponse(RequestInterface $request) : ResponseInterface;
}
