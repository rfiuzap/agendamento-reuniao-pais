<?php

namespace App\Exceptions;

use RuntimeException;

/** Violation of a business rule; the message is safe to show to the user. */
class BusinessRuleException extends RuntimeException
{
}
