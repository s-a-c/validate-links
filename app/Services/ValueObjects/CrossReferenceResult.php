<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\ArrayKey;
use App\Enums\CrossReferenceStatus;
use InvalidArgumentException;

/**
 * Value object representing a cross-reference validation result.
 *
 * This class replaces generic array cross-reference results with strongly typed properties
 * for better type safety and validation.
 */
final readonly class CrossReferenceResult
{
    /**
     * @param string $url The URL being validated
     * @param CrossReferenceStatus $status The validation status
     * @param string|null $reason Optional reason for the status
     * @param string|null $targetFile The target file being referenced
     * @param string|null $targetAnchor The target anchor being referenced
     * @param float $validationTime Time taken for validation in seconds
     * @param array<string> $referenceChain Chain of references (for circular detection)
     */
    public function __construct(
        public string $url,
        public CrossReferenceStatus $status,
        public ?string $reason = null,
        public ?string $targetFile = null,
        public ?string $targetAnchor = null,
        public float $validationTime = 0.0,
        public array $referenceChain = [],
    ) {
        $this->validate();
    }

    /**
     * Create CrossReferenceResult from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $url = isset($data[ArrayKey::URL->value]) && is_string($data[ArrayKey::URL->value])
            ? $data[ArrayKey::URL->value]
            : '';

        $status = isset($data[ArrayKey::STATUS->value]) && is_string($data[ArrayKey::STATUS->value])
            ? CrossReferenceStatus::from($data[ArrayKey::STATUS->value])
            : CrossReferenceStatus::BROKEN;

        $reason = isset($data[ArrayKey::REASON->value]) && is_string($data[ArrayKey::REASON->value])
            ? $data[ArrayKey::REASON->value]
            : null;

        $targetFile = isset($data['target_file']) && is_string($data['target_file'])
            ? $data['target_file']
            : null;

        $targetAnchor = isset($data['target_anchor']) && is_string($data['target_anchor'])
            ? $data['target_anchor']
            : null;

        $validationTime = isset($data['validation_time']) && (is_float($data['validation_time']) || is_int($data['validation_time']))
            ? (float) $data['validation_time']
            : 0.0;

        $referenceChain = isset($data['reference_chain']) && is_array($data['reference_chain'])
            ? array_filter($data['reference_chain'], 'is_string')
            : [];

        return new self(
            url: $url,
            status: $status,
            reason: $reason,
            targetFile: $targetFile,
            targetAnchor: $targetAnchor,
            validationTime: $validationTime,
            referenceChain: $referenceChain,
        );
    }

    /**
     * Create a successful cross-reference result.
     */
    public static function success(
        string $url,
        ?string $targetFile = null,
        ?string $targetAnchor = null,
        float $validationTime = 0.0,
    ): self {
        return new self(
            url: $url,
            status: CrossReferenceStatus::VALID,
            reason: null,
            targetFile: $targetFile,
            targetAnchor: $targetAnchor,
            validationTime: $validationTime,
        );
    }

    /**
     * Create a failed cross-reference result.
     */
    public static function failure(
        string $url,
        CrossReferenceStatus $status,
        string $reason,
        ?string $targetFile = null,
        ?string $targetAnchor = null,
        float $validationTime = 0.0,
    ): self {
        return new self(
            url: $url,
            status: $status,
            reason: $reason,
            targetFile: $targetFile,
            targetAnchor: $targetAnchor,
            validationTime: $validationTime,
        );
    }

    /**
     * Create a file not found result.
     */
    public static function fileNotFound(
        string $url,
        string $targetFile,
        float $validationTime = 0.0,
    ): self {
        return new self(
            url: $url,
            status: CrossReferenceStatus::FILE_NOT_FOUND,
            reason: "Target file '{$targetFile}' not found",
            targetFile: $targetFile,
            validationTime: $validationTime,
        );
    }

    /**
     * Create an anchor not found result.
     */
    public static function anchorNotFound(
        string $url,
        string $targetFile,
        string $targetAnchor,
        float $validationTime = 0.0,
    ): self {
        return new self(
            url: $url,
            status: CrossReferenceStatus::ANCHOR_NOT_FOUND,
            reason: "Target anchor '{$targetAnchor}' not found in '{$targetFile}'",
            targetFile: $targetFile,
            targetAnchor: $targetAnchor,
            validationTime: $validationTime,
        );
    }

    /**
     * Create a circular reference result.
     *
     * @param array<string> $referenceChain
     */
    public static function circularReference(
        string $url,
        array $referenceChain,
        float $validationTime = 0.0,
    ): self {
        $chainString = implode(' -> ', $referenceChain);

        return new self(
            url: $url,
            status: CrossReferenceStatus::CIRCULAR_REFERENCE,
            reason: "Circular reference detected: {$chainString}",
            referenceChain: $referenceChain,
            validationTime: $validationTime,
        );
    }

    /**
     * Check if the cross-reference is valid.
     */
    public function isValid(): bool
    {
        return $this->status->isValid();
    }

    /**
     * Check if the cross-reference is broken.
     */
    public function isBroken(): bool
    {
        return $this->status->isBroken();
    }

    /**
     * Check if the issue is recoverable.
     */
    public function isRecoverable(): bool
    {
        return $this->status->isRecoverable();
    }

    /**
     * Get the full target reference (file + anchor).
     */
    public function getFullTarget(): string
    {
        if ($this->targetFile === null) {
            return '';
        }

        if ($this->targetAnchor === null) {
            return $this->targetFile;
        }

        return "{$this->targetFile}#{$this->targetAnchor}";
    }

    /**
     * Check if this is a circular reference.
     */
    public function isCircularReference(): bool
    {
        return $this->status === CrossReferenceStatus::CIRCULAR_REFERENCE;
    }

    /**
     * Get the depth of the reference chain.
     */
    public function getReferenceDepth(): int
    {
        return count($this->referenceChain);
    }

    /**
     * Convert to array format for backward compatibility.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ArrayKey::URL->value => $this->url,
            ArrayKey::STATUS->value => $this->status->value,
            ArrayKey::REASON->value => $this->reason,
            'target_file' => $this->targetFile,
            'target_anchor' => $this->targetAnchor,
            'validation_time' => $this->validationTime,
            'reference_chain' => $this->referenceChain,
            'is_valid' => $this->isValid(),
            'is_recoverable' => $this->isRecoverable(),
            'full_target' => $this->getFullTarget(),
        ];
    }

    /**
     * Get a human-readable description of the result.
     */
    public function getDescription(): string
    {
        $description = "Cross-reference validation for '{$this->url}': {$this->status->getDisplayName()}";

        if ($this->reason !== null) {
            $description .= " - {$this->reason}";
        }

        if ($this->targetFile !== null) {
            $description .= " (Target: {$this->getFullTarget()})";
        }

        return $description;
    }

    /**
     * Validate the cross-reference result values.
     */
    private function validate(): void
    {
        if ($this->url === '') {
            throw new InvalidArgumentException('URL cannot be empty');
        }

        if ($this->validationTime < 0.0) {
            throw new InvalidArgumentException('Validation time must be non-negative');
        }

        // Validate reference chain doesn't contain duplicates (except for circular references)
        if ($this->status !== CrossReferenceStatus::CIRCULAR_REFERENCE) {
            $uniqueChain = array_unique($this->referenceChain);
            if (count($uniqueChain) !== count($this->referenceChain)) {
                throw new InvalidArgumentException('Reference chain contains duplicates but status is not circular reference');
            }
        }
    }
}
