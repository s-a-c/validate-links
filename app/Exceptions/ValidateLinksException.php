<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception class for all validate-links exceptions.
 */
abstract class ValidateLinksException extends Exception
{
    /**
     * @var Throwable
     */
    public $previous;

    /**
     * Create a new exception instance.
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  Throwable|null  $previous  The previous throwable used for exception chaining
     * @param  array<string, mixed>  $context  Additional context data
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, /**
     * Additional context data for the exception.
     *
     * @var array<string, mixed>
     */
        protected array $context = [])
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Serialize the exception.
     *
     * @return array<string, mixed>
     */
    final public function __serialize(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'previous' => $this->getPrevious(),
        ];
    }

    /**
     * Unserialize the exception.
     *
     * @param  array<string, mixed>  $data
     */
    final public function __unserialize(array $data): void
    {
        // Note: Cannot directly assign to Exception properties as they are readonly in PHP 8+
        // This method exists for interface compliance but has limitations
        $context = $data['context'] ?? [];
        if (is_array($context)) {
            // Ensure all keys are strings and values are mixed (which is acceptable for array<string, mixed>)
            $typedContext = [];
            foreach ($context as $key => $value) {
                $typedContext[(string) $key] = $value;
            }
            $this->context = $typedContext;
        } else {
            $this->context = [];
        }

        if (isset($data['previous']) && $data['previous'] instanceof Throwable) {
            $this->previous = $data['previous'];
        }
    }

    /**
     * String representation of the exception including error code.
     */
    public function __toString(): string
    {
        $errorCode = $this->getErrorCode();
        $className = static::class;
        $message = $this->getMessage();
        $file = $this->getFile();
        $line = $this->getLine();

        $result = "{$className} [{$errorCode}]: {$message} in {$file}:{$line}";

        return $result.("\nStack trace:\n".$this->getTraceAsString());
    }

    /**
     * Get the error code for this exception.
     */
    abstract public function getErrorCode(): string;

    /**
     * Get the severity level of this exception.
     */
    abstract public function getSeverity(): string;

    /**
     * Get additional context data.
     *
     * @return array<string, mixed>
     */
    final public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Convert exception to array format.
     *
     * @return array<string, mixed>
     */
    final public function toArray(): array
    {
        return [
            'error_code' => $this->getErrorCode(),
            'message' => $this->getMessage(),
            'severity' => $this->getSeverity(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->getContext(),
        ];
    }
}
