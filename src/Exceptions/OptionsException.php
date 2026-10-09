<?php

declare(strict_types=1);

namespace SpitsOnline\Options\Exceptions;

/**
 * Every exception this package throws extends this one, so one `catch`
 * covers them all.
 */
class OptionsException extends \RuntimeException {}
