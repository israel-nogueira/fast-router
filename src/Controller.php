<?php

namespace IsraelNogueira\fastRouter;

use IsraelNogueira\fastRouter\ProvidesControllerMiddlewareTrait;

abstract class Controller implements ProvidesControllerMiddleware
{
    use ProvidesControllerMiddlewareTrait;
}
