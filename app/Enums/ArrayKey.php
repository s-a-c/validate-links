<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for commonly used array keys throughout the application.
 *
 * This enum provides type-safe constants for array keys used in data structures,
 * configuration arrays, and API responses to prevent typos and improve maintainability.
 */
enum ArrayKey: string
{
    // Link-related keys
    case URL = 'url';
    case TEXT = 'text';
    case LINE = 'line';
    case ANCHOR = 'anchor';
    case FILE_PATH = 'file_path';
    case ERROR = 'error';
    case STATUS = 'status';
    case SCOPE = 'scope';
    case TIMESTAMP = 'timestamp';
    case REASON = 'reason';

    // Validation result keys
    case TOTAL_LINKS = 'total_links';
    case BROKEN_LINKS = 'broken_links';
    case VALID_LINKS = 'valid_links';
    case SUCCESS_RATE = 'success_rate';
    case EXECUTION_TIME = 'execution_time';
    case FILES_PROCESSED = 'files_processed';
    case STATUS_BREAKDOWN = 'status_breakdown';

    // Statistics keys
    case TOTAL = 'total';
    case VALID = 'valid';
    case BROKEN = 'broken';
    case INVALID = 'invalid';
    case TIMEOUTS = 'timeouts';
    case SECURITY_RISKS = 'security_risks';
    case REDIRECTS = 'redirects';

    // Configuration keys
    case FORMAT = 'format';
    case OUTPUT = 'output';
    case TIMEOUT = 'timeout';
    case MAX_REDIRECTS = 'max_redirects';
    case CONCURRENT_REQUESTS = 'concurrent_requests';
    case CACHE_ENABLED = 'cache_enabled';
    case FOLLOW_REDIRECTS = 'follow_redirects';
    case USER_AGENT = 'user_agent';
    case OUTPUT_FORMAT = 'output_format';
    case ADDITIONAL_OPTIONS = 'additional_options';
    case INTERACTIVE = 'interactive';
    case DRY_RUN = 'dry_run';

    // File and metadata keys
    case FILE = 'file';
    case LINK_COUNT = 'link_count';
    case METADATA = 'metadata';
    case RESULTS = 'results';
    case SUMMARY = 'summary';
    case DURATION = 'duration';

    // HTTP-related keys
    case HTTP_STATUS_CODE = 'http_status_code';
    case REDIRECT_URL = 'redirect_url';
    case RESPONSE_TIME = 'response_time';
    case METHOD = 'method';
    case HEADERS = 'headers';

    // Batch processing keys
    case BATCH_ID = 'batch_id';
    case PRIORITY = 'priority';
    case LINKS = 'links';
    case CONFIG = 'config';

    // Security keys
    case BLOCKED_DOMAINS = 'blocked_domains';
    case ALLOWED_PROTOCOLS = 'allowed_protocols';
    case MAX_FILE_SIZE = 'max_file_size';
    case BLOCKED_PATHS = 'blocked_paths';

    /**
     * Get all array key values as array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get display name for the array key.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::URL => 'URL',
            self::TEXT => 'Text',
            self::LINE => 'Line Number',
            self::ANCHOR => 'Anchor',
            self::FILE_PATH => 'File Path',
            self::ERROR => 'Error',
            self::STATUS => 'Status',
            self::SCOPE => 'Scope',
            self::TIMESTAMP => 'Timestamp',
            self::REASON => 'Reason',
            self::TOTAL_LINKS => 'Total Links',
            self::BROKEN_LINKS => 'Broken Links',
            self::VALID_LINKS => 'Valid Links',
            self::SUCCESS_RATE => 'Success Rate',
            self::EXECUTION_TIME => 'Execution Time',
            self::FILES_PROCESSED => 'Files Processed',
            self::STATUS_BREAKDOWN => 'Status Breakdown',
            self::TOTAL => 'Total',
            self::VALID => 'Valid',
            self::BROKEN => 'Broken',
            self::INVALID => 'Invalid',
            self::TIMEOUTS => 'Timeouts',
            self::SECURITY_RISKS => 'Security Risks',
            self::REDIRECTS => 'Redirects',
            self::FORMAT => 'Format',
            self::OUTPUT => 'Output',
            self::TIMEOUT => 'Timeout',
            self::MAX_REDIRECTS => 'Max Redirects',
            self::CONCURRENT_REQUESTS => 'Concurrent Requests',
            self::CACHE_ENABLED => 'Cache Enabled',
            self::FOLLOW_REDIRECTS => 'Follow Redirects',
            self::USER_AGENT => 'User Agent',
            self::OUTPUT_FORMAT => 'Output Format',
            self::ADDITIONAL_OPTIONS => 'Additional Options',
            self::INTERACTIVE => 'Interactive',
            self::DRY_RUN => 'Dry Run',
            self::FILE => 'File',
            self::LINK_COUNT => 'Link Count',
            self::METADATA => 'Metadata',
            self::RESULTS => 'Results',
            self::SUMMARY => 'Summary',
            self::DURATION => 'Duration',
            self::HTTP_STATUS_CODE => 'HTTP Status Code',
            self::REDIRECT_URL => 'Redirect URL',
            self::RESPONSE_TIME => 'Response Time',
            self::METHOD => 'Method',
            self::HEADERS => 'Headers',
            self::BATCH_ID => 'Batch ID',
            self::PRIORITY => 'Priority',
            self::LINKS => 'Links',
            self::CONFIG => 'Config',
            self::BLOCKED_DOMAINS => 'Blocked Domains',
            self::ALLOWED_PROTOCOLS => 'Allowed Protocols',
            self::MAX_FILE_SIZE => 'Max File Size',
            self::BLOCKED_PATHS => 'Blocked Paths',
        };
    }

    /**
     * Get category for the array key.
     */
    public function getCategory(): string
    {
        return match ($this) {
            self::URL, self::TEXT, self::LINE, self::ANCHOR, self::FILE_PATH => 'link',
            self::ERROR, self::STATUS, self::SCOPE, self::TIMESTAMP, self::REASON => 'status',
            self::TOTAL_LINKS, self::BROKEN_LINKS, self::VALID_LINKS, self::SUCCESS_RATE => 'metrics',
            self::EXECUTION_TIME, self::FILES_PROCESSED, self::STATUS_BREAKDOWN => 'performance',
            self::TOTAL, self::VALID, self::BROKEN, self::INVALID, self::TIMEOUTS => 'statistics',
            self::SECURITY_RISKS, self::REDIRECTS => 'statistics',
            self::FORMAT, self::OUTPUT, self::TIMEOUT, self::MAX_REDIRECTS => 'configuration',
            self::CONCURRENT_REQUESTS, self::CACHE_ENABLED, self::FOLLOW_REDIRECTS => 'configuration',
            self::USER_AGENT, self::OUTPUT_FORMAT, self::ADDITIONAL_OPTIONS => 'configuration',
            self::INTERACTIVE, self::DRY_RUN => 'configuration',
            self::FILE, self::LINK_COUNT, self::METADATA, self::RESULTS => 'data',
            self::SUMMARY, self::DURATION => 'data',
            self::HTTP_STATUS_CODE, self::REDIRECT_URL, self::RESPONSE_TIME => 'http',
            self::METHOD, self::HEADERS => 'http',
            self::BATCH_ID, self::PRIORITY, self::LINKS, self::CONFIG => 'batch',
            self::BLOCKED_DOMAINS, self::ALLOWED_PROTOCOLS, self::MAX_FILE_SIZE => 'security',
            self::BLOCKED_PATHS => 'security',
        };
    }
}
