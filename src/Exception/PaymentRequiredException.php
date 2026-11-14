<?php

declare(strict_types=1);

namespace LifeAstroApi\Exception;

/** 402: out of credits, or the plan does not include this endpoint. */
class PaymentRequiredException extends LifeAstroException
{
}
