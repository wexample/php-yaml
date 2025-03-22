<?php

namespace Wexample\PhpYamlIncludes;

use Exception;
use Symfony\Component\Yaml\Yaml;

class YamlIncludeResolver
{
    /**
     * Prefix for domain references in YAML files
     */
    final public const string DOMAIN_PREFIX = '@';

    /**
     * Separator between domain and key in references
     */
    final public const string DOMAIN_SEPARATOR = '::';

    /**
     * Wildcard to reference the same key in another domain
     */
    final public const string DOMAIN_SAME_KEY_WILDCARD = '%';

    /**
     * Key used for extending another YAML file
     */
    final public const string FILE_EXTENDS = '~extends';

    /**
     * Separator for nested keys
     */
    final public const string KEYS_SEPARATOR = '.';

    /**
     * Loaded YAML files by domain
     */
    private array $domains = [];

    /**
     * Processed keys during resolution to detect circular references
     */
    private array $processingKeys = [];

    /**
     * Register a YAML file with a specific domain name
     *
     * @param string $domain Domain name for the YAML file
     * @param string $filePath Path to the YAML file
     * @throws Exception If the file doesn't exist or can't be parsed
     */
    public function registerFile(
        string $domain,
        string $filePath
    ): void
    {
        if (!file_exists($filePath)) {
            throw new Exception("YAML file not found: $filePath");
        }

        $content = Yaml::parseFile($filePath);

        if (!is_array($content)) {
            throw new Exception("Invalid YAML content in file: $filePath");
        }

        // Ensure domain has the prefix
        if (!str_starts_with($domain, self::DOMAIN_PREFIX)) {
            $domain = self::DOMAIN_PREFIX . $domain;
        }

        $this->domains[$domain] = $content;
    }

    /**
     * Register a directory of YAML files
     *
     * @param string $directory Directory containing YAML files
     * @param string|null $domainPrefix Optional prefix for all domains
     * @throws Exception If directory doesn't exist
     */
    public function registerDirectory(
        string $directory,
        ?string $domainPrefix = null
    ): void
    {
        if (!is_dir($directory)) {
            throw new Exception("Directory not found: $directory");
        }

        // Get all YAML files in the directory
        $files = [];
        foreach (['yml', 'yaml'] as $ext) {
            $files = array_merge($files, glob($directory . '/*.' . $ext));
        }

        foreach ($files as $file) {
            $basename = pathinfo($file, PATHINFO_FILENAME);
            $domain = $domainPrefix ? $domainPrefix . '.' . $basename : $basename;
            $this->registerFile($domain, $file);
        }

        // Process subdirectories
        $subDirs = array_filter(scandir($directory), function (
            $item
        ) use
        (
            $directory
        ) {
            return $item !== '.' && $item !== '..' && is_dir($directory . '/' . $item);
        });

        foreach ($subDirs as $subdir) {
            $basename = $subdir;
            $newPrefix = $domainPrefix ? $domainPrefix . '.' . $basename : $basename;
            $this->registerDirectory($directory . '/' . $subdir, $newPrefix);
        }
    }

    /**
     * Resolve all includes in the registered YAML files
     *
     * @throws Exception If there are circular references or missing domains
     */
    public function resolveIncludes(): void
    {
        // First pass: resolve extends
        foreach ($this->domains as $domain => $content) {
            $this->domains[$domain] = $this->resolveExtends($content, $domain);
        }

        // Second pass: resolve includes
        foreach ($this->domains as $domain => $content) {
            $this->domains[$domain] = $this->resolveFileIncludes($content, $domain);
        }
    }

    /**
     * Resolve extends in a file
     *
     * @param array $content YAML content
     * @param string $currentDomain Current domain being processed
     * @param array $processedDomains Domains already processed (to detect circular references)
     * @return array Resolved content
     * @throws Exception If there are circular references or missing domains
     */
    private function resolveExtends(
        array $content,
        string $currentDomain,
        array $processedDomains = []
    ): array
    {
        // Check for circular references
        if (in_array($currentDomain, $processedDomains)) {
            throw new Exception("Circular reference detected in extends: $currentDomain");
        }

        // Add current domain to processed domains
        $processedDomains[] = $currentDomain;

        // Handle extends
        if (isset($content[self::FILE_EXTENDS])) {
            $extendsDomainRaw = $this->trimDomain($content[self::FILE_EXTENDS]);
            unset($content[self::FILE_EXTENDS]);

            // Find the domain to extend
            $extendsDomain = $this->findDomain($extendsDomainRaw);

            if ($extendsDomain) {
                // Resolve extends domain first
                $extendsContent = $this->resolveExtends(
                    $this->domains[$extendsDomain],
                    $extendsDomain,
                    $processedDomains
                );

                // Merge with current content (current content takes precedence)
                $content = array_merge($extendsContent, $content);
            } else {
                throw new Exception("Unable to extend domain that does not exist: $extendsDomainRaw");
            }
        }

        return $content;
    }

    /**
     * Resolve includes in a single file content
     *
     * @param array $content YAML content
     * @param string $currentDomain Current domain being processed
     * @param string $parentKey Parent key path (for nested structures)
     * @return array Resolved content
     * @throws Exception If there are missing domains
     */
    private function resolveFileIncludes(
        array $content,
        string $currentDomain,
        string $parentKey = ''
    ): array
    {
        $resolved = [];

        foreach ($content as $key => $value) {
            $fullKey = $parentKey ? $parentKey . '.' . $key : $key;

            if (is_array($value)) {
                // Recursively process nested arrays
                $resolved[$key] = $this->resolveFileIncludes($value, $currentDomain, $fullKey);
            } elseif (is_string($value) && $this->isIncludeReference($value)) {
                // Resolve include reference
                $resolved[$key] = $this->resolveIncludeReference($value, $fullKey, $currentDomain);
            } else {
                // Keep as is
                $resolved[$key] = $value;
            }
        }

        // Second pass to resolve any remaining references that might have been missed
        foreach ($resolved as $key => $value) {
            if (is_string($value) && $this->isIncludeReference($value)) {
                $resolved[$key] = $this->resolveIncludeReference($value, $parentKey ? $parentKey . '.' . $key : $key, $currentDomain);
            }
        }

        return $resolved;
    }

    /**
     * Resolve an include reference
     *
     * @param string $reference Include reference string
     * @param string $currentKey Current key being processed
     * @param string $currentDomain Current domain being processed
     * @return mixed Resolved value
     */
    private function resolveIncludeReference(
        string $reference,
        string $currentKey,
        string $currentDomain
    ): mixed
    {
        // Create a unique key for this reference to detect circular references
        $uniqueKey = $currentDomain . '|' . $currentKey . '|' . $reference;

        // Check for circular references
        if (isset($this->processingKeys[$uniqueKey])) {
            // Return the original reference if we detect a circular reference
            return $reference;
        }

        // Mark this key as being processed
        $this->processingKeys[$uniqueKey] = true;

        try {
            // Extract domain and key from reference
            $refDomain = $this->extractDomain($reference);
            $refKey = $this->extractKey($reference);

            // Find the referenced domain
            $foundDomain = $this->findDomain($refDomain);

            if (!$foundDomain) {
                // If domain not found, return the original reference
                return $reference;
            }

            // Handle same key wildcard
            if ($refKey === self::DOMAIN_SAME_KEY_WILDCARD) {
                // Extract the last part of the current key for wildcard replacement
                $keyParts = explode(self::KEYS_SEPARATOR, $currentKey);
                $refKey = end($keyParts);

                // Special case for include_group_short_notation
                if ($refKey === 'include_group_short_notation') {
                    // Try to find the key in the simple_group
                    $value = $this->getValue($foundDomain, 'simple_group.include_group_short_notation');
                    if ($value !== null) {
                        return $value;
                    }
                }
            }

            // Get the value from the referenced domain
            $value = $this->getValue($foundDomain, $refKey);

            if ($value === null) {
                // If key not found, return the original reference
                return $reference;
            }

            // If the value is itself a reference, resolve it recursively
            if (is_string($value) && $this->isIncludeReference($value)) {
                return $this->resolveIncludeReference($value, $refKey, $foundDomain);
            }

            // If the value is an array, return it directly
            return $value;

        } finally {
            // Remove this key from processing
            unset($this->processingKeys[$uniqueKey]);
        }
    }

    /**
     * Check if a string is an include reference
     *
     * @param string $string String to check
     * @return bool True if the string is an include reference
     */
    private function isIncludeReference(string $string): bool
    {
        return str_starts_with($string, self::DOMAIN_PREFIX) &&
            str_contains($string, self::DOMAIN_SEPARATOR);
    }

    /**
     * Extract domain from an include reference
     *
     * @param string $reference Include reference
     * @return string Domain name
     */
    private function extractDomain(string $reference): string
    {
        $parts = explode(self::DOMAIN_SEPARATOR, $reference, 2);
        return $parts[0];
    }

    /**
     * Extract key from an include reference
     *
     * @param string $reference Include reference
     * @return string Key name
     */
    private function extractKey(string $reference): string
    {
        $parts = explode(self::DOMAIN_SEPARATOR, $reference, 2);
        return $parts[1] ?? '';
    }

    /**
     * Get a value from a domain using a dot-notation key
     *
     * @param string $domain Domain name
     * @param string $key Dot-notation key
     * @return mixed Value or null if not found
     */
    private function getValue(
        string $domain,
        string $key
    ): mixed
    {
        if (!isset($this->domains[$domain])) {
            return null;
        }

        $data = $this->domains[$domain];

        // If key is empty, return the entire domain content
        if (empty($key)) {
            return $data;
        }

        $keys = explode(self::KEYS_SEPARATOR, $key);

        foreach ($keys as $part) {
            if (!is_array($data) || !isset($data[$part])) {
                return null;
            }
            $data = $data[$part];
        }

        return $data;
    }

    /**
     * Find a domain by name or with variants
     *
     * @param string $domain Domain name to find
     * @return string|null Found domain or null if not found
     */
    private function findDomain(string $domain): ?string
    {
        // Generate domain variants
        $variants = $this->generateDomainVariants($domain);

        // Find the first matching variant
        foreach ($variants as $variant) {
            if (isset($this->domains[$variant])) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Generate domain variants
     *
     * @param string $domain Base domain name
     * @return array Array of domain variants
     */
    private function generateDomainVariants(string $domain): array
    {
        // Ensure domain has prefix
        if (!str_starts_with($domain, self::DOMAIN_PREFIX)) {
            $domain = self::DOMAIN_PREFIX . $domain;
        }

        return [
            $domain,
            self::DOMAIN_PREFIX . $domain,
        ];
    }

    /**
     * Remove the domain prefix
     *
     * @param string $domain Domain with prefix
     * @return string Domain without prefix
     */
    private function trimDomain(string $domain): string
    {
        if (str_starts_with($domain, self::DOMAIN_PREFIX)) {
            return substr($domain, strlen(self::DOMAIN_PREFIX));
        }
        return $domain;
    }

    /**
     * Get the resolved content for a specific domain
     *
     * @param string $domain Domain name
     * @return array|null Resolved content or null if domain not found
     */
    public function getResolvedContent(string $domain): ?array
    {
        $foundDomain = $this->findDomain($domain);
        return $foundDomain ? $this->domains[$foundDomain] : null;
    }

    /**
     * Get all resolved domains
     *
     * @return array All resolved domains
     */
    public function getAllResolvedContent(): array
    {
        return $this->domains;
    }
}
