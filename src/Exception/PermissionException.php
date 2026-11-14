<?php

declare(strict_types=1);

namespace LifeAstroApi\Exception;

/** 403: the key is valid but not allowed here (plan, IP allow-list, suspended account). */
class PermissionException extends LifeAstroException
{
}
