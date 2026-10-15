<?php

namespace App\Exceptions;

/** A required server setting is missing or unsafe (raised at startup of a request, never by user input). */
class ConfigurationException extends \RuntimeException
{
}