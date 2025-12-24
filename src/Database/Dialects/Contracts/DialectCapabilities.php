<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Contracts;

/**
 * Defines the capabilities supported by a SQL dialect.
 * 
 * This immutable value object declares which features are supported
 * by a specific database driver, allowing graceful degradation or
 * error handling for unsupported features.
 */
final readonly class DialectCapabilities
{
    /**
     * @param bool $supportsLocks Whether the dialect supports row locks (FOR UPDATE, FOR SHARE)
     * @param bool $supportsUpsert Whether the dialect supports UPSERT operations (INSERT ... ON CONFLICT/DUPLICATE KEY)
     * @param bool $supportsReturning Whether the dialect supports RETURNING clause (PostgreSQL)
     * @param bool $supportsJsonOperators Whether the dialect supports JSON operators (->>, ->, etc.)
     * @param bool $supportsAdvancedLocks Whether the dialect supports advanced locks (FOR NO KEY UPDATE, etc.)
     * @param bool $supportsInsertIgnore Whether the dialect supports INSERT IGNORE syntax
     * @param bool $supportsFulltextSearch Whether the dialect supports full-text search
     * @param bool $supportsGeneratedColumns Whether the dialect supports generated/computed columns
     */
    public function __construct(
        public bool $supportsLocks = false,
        public bool $supportsUpsert = false,
        public bool $supportsReturning = false,
        public bool $supportsJsonOperators = false,
        public bool $supportsAdvancedLocks = false,
        public bool $supportsInsertIgnore = false,
        public bool $supportsFulltextSearch = false,
        public bool $supportsGeneratedColumns = false,
    ) {}

    /**
     * Check if a specific capability is supported.
     *
     * @param string $capability The capability name (e.g., 'locks', 'upsert')
     * @return bool
     */
    public function supports(string $capability): bool
    {
        $property = 'supports' . ucfirst($capability);
        
        return property_exists($this, $property) && $this->$property;
    }
}
