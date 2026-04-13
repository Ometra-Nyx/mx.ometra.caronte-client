<?php

/**
 * Exception thrown when tenant information is missing from Caronte token claims.
 *
 * PHP 8.1+
 *
 * @package Ometra\Caronte\Exceptions
 */

namespace Ometra\Caronte\Exceptions;

use Exception;

class TenantMissingException extends Exception {}
