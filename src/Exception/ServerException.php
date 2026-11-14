<?php

declare(strict_types=1);

namespace LifeAstroApi\Exception;

/** 5xx: the API failed on its side. Retried automatically before it is thrown. */
class ServerException extends LifeAstroException
{
}
