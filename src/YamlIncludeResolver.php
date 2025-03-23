<?php

namespace Wexample\PhpYaml;

use Exception;
use Symfony\Component\Yaml\Yaml;
use Wexample\Helpers\Helper\FileHelper;

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
     * Separator for nested keys in dot notation
     */
    final public const string KEYS_SEPARATOR = '.';

    /**
     * Array of registered domains with their content
     */
    private array $domains = [];

    /**
     * Cache for resolved values
     */
    private array $valueCache = [];

    /**
     * Cache for domain splits
     */
    private array $domainSplitCache = [];

    /**
     * Cache for key splits
     */
    private array $keySplitCache = [];

    /**
     * Scans a directory for YAML files and registers them
     *
     * @param string $relativeBasePath Directory path to scan for YAML files
     * @param string|null $aliasPrefix Optional prefix for the domain names
     * @throws Exception If a file can't be registered
     */
    public function scanDirectory(
        string $relativeBasePath
    ): array
    {
        // Clear caches when scanning a new directory
        $this->clearCaches();

        // Use the FileHelper to scan the directory for YAML files
        return FileHelper::scanDirectoryForFiles(
            $relativeBasePath,
            FileHelper::FILE_EXTENSION_YML,
            function (
                \SplFileInfo $fileInfo
            ) use
            (
                $relativeBasePath
            ): \SplFileInfo {

                // Register the file
                $this->registerFile($this->buildDomainFromFile(
                    fileInfo: $fileInfo,
                    relativeBasePath: $relativeBasePath
                ), $fileInfo->getPathname());

                return $fileInfo;
            }
        );
    }

    public function buildDomainFromFile(
        \SplFileInfo $fileInfo,
        string $relativeBasePath
    ): string
    {
        $exp = explode('.', $fileInfo->getFilename());

        // Build the domain from the file path
        $domain = [];

        $relativePath = FileHelper::buildRelativePath(
            $fileInfo->getPath(),
            dirname($relativeBasePath)
        );

        // If we have a relative path, use it to build the domain
        if ($relativePath) {
            $domain = explode('/', $relativePath);
        }

        // Append file name to the domain parts
        $domain[] = $exp[0];

        return implode(self::KEYS_SEPARATOR, $domain);
    }

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
        
        // Clear caches when registering a new file
        $this->clearCaches();
    }

    /**
     * Clear all caches
     */
    private function clearCaches(): void
    {
        $this->valueCache = [];
        $this->domainSplitCache = [];
        $this->keySplitCache = [];
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
     * @param string $key Dot-notation key
     * @param string|null $domain Domain name
     * @return mixed Value or null if not found
     */
    public function getValue(
        string $key,
        string $domain = null
    ): mixed
    {
        // Generate a cache key
        $cacheKey = ($domain ?? '') . '|' . $key;
        
        // Check if the value is already in cache
        if (array_key_exists($cacheKey, $this->valueCache)) {
            return $this->valueCache[$cacheKey];
        }
        
        $default = $key;
        $found = false;

        // Extract domain from the key if not provided explicitly
        if (is_null($domain) && $domain = $this->splitDomain($key)) {
            $key = $this->splitKey($key);
        }

        if ($domain) {
            $default = $domain . static::DOMAIN_SEPARATOR . $key;
        }

        // Get the value from the domain using the path
        // If we encounter a non-array element before reaching the end of the path,
        // we'll capture the remaining path segments to append to the result
        $keys = explode(self::KEYS_SEPARATOR, $key);

        if (!$data = $this->domains[$domain] ?? null) {
            // Cache the result
            $this->valueCache[$cacheKey] = $default;
            return $default;
        }

        $remainingSegments = [];
        $searchData = $data;
        foreach ($keys as $i => $k) {
            if (is_array($searchData)) {
                if (array_key_exists($k, $searchData)) {
                    $searchData = $searchData[$k];
                    $found = true;
                } else {
                    $found = false;
                    $searchData = $default;
                }
            } else {
                // We've reached a point where we can't go further
                // Collect the remaining path segments
                $remainingSegments = array_slice($keys, $i);

                if (empty($remainingSegments)) {
                    $searchData = $default;
                    $found = false;
                }
                break;
            }
        }

        $value = $searchData;

        if (is_string($value) && $value !== $default && $this->isIncludeReference($value)) {
            $refDomain = $this->splitDomain($value);
            $refKey = $this->splitKey($value);
            if ($refKey === self::DOMAIN_SAME_KEY_WILDCARD) {
                $refKey = $key;
            }

            if (!empty($remainingSegments)) {
                $refKey .= self::KEYS_SEPARATOR . implode(separator: self::KEYS_SEPARATOR, array: $remainingSegments);
            }

            // Pass the remaining segments to the recursive call
            $result = $this->getValue($refKey, $refDomain);
            
            // Cache the result
            $this->valueCache[$cacheKey] = $result;
            
            return $result;
        }

        // If value was not found and the domain has an extends directive,
        // try to get the value from the parent domain
        if (!$found && is_array($data) && array_key_exists(self::FILE_EXTENDS, $data) && is_string($data[self::FILE_EXTENDS])) {
            // The domain has an extends directive, try to get the value from the parent domain
            // This allows for inheritance of values between domains
            $result = $this->getValue(
                key: $key,
                domain: $data[self::FILE_EXTENDS]
            );
            
            // Cache the result
            $this->valueCache[$cacheKey] = $result;
            
            return $result;
        }

        // Cache the result
        $this->valueCache[$cacheKey] = $value;
        
        return $value;
    }

    /**
     * Extract domain part from a reference
     *
     * @param string|null $key Reference to extract domain from
     * @return string|null Domain part or null if not found
     */
    public function splitDomain(?string $key): ?string
    {
        if ($key === null) {
            return null;
        }
        
        // Check cache first
        if (array_key_exists($key, $this->domainSplitCache)) {
            return $this->domainSplitCache[$key];
        }
        
        $result = null;
        if (str_contains($key, self::DOMAIN_SEPARATOR)) {
            $result = current(explode(self::DOMAIN_SEPARATOR, $key));
        }
        
        // Cache the result
        $this->domainSplitCache[$key] = $result;
        
        return $result;
    }

    /**
     * Extract key part from a domain reference
     *
     * @param string $key Reference to extract key from
     * @return string Key part
     */
    public function splitKey(string $key): ?string
    {
        // Check cache first
        if (array_key_exists($key, $this->keySplitCache)) {
            return $this->keySplitCache[$key];
        }
        
        $result = $key;
        if (str_contains($key, self::DOMAIN_SEPARATOR)) {
            $exp = explode(self::DOMAIN_SEPARATOR, $key);
            $result = end($exp);
        }
        
        // Cache the result
        $this->keySplitCache[$key] = $result;
        
        return $result;
    }
}
