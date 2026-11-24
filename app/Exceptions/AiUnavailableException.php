<?php
namespace App\Exceptions;
use Exception;
/** Thrown when Gemini is unreachable, keyless, slow, or returns bad JSON. */
class AiUnavailableException extends Exception {}
