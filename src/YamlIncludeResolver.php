<?php

namespace Wexample\PhpYaml;

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

    protected array $domainsStack = [];

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
        $default = $key;

        // Extract domain from the key if not provided explicitly
        if (is_null($domain) && $domain = $this->splitDomain($key)) {
            $key = $this->splitId($key);
            $domain = $this->resolveDomain($domain);
        };

        if ($domain) {
            $default = $domain . static::DOMAIN_SEPARATOR . $key;
        }

        // Get the value from the domain using the path
        // If we encounter a non-array element before reaching the end of the path,
        // we'll capture the remaining path segments to append to the result
        $keys = explode(self::KEYS_SEPARATOR, $key);

        if (!$data = $this->domains[$domain] ?? null) {
            return $default;
        }

        $remainingSegments = [];
        foreach ($keys as $i => $k) {
            if (is_array($data)) {
                if (array_key_exists($k, $data)) {
                    $data = $data[$k];
                } else {
                    $data = $default;
                }
            } else {
                // We've reached a point where we can't go further
                // Collect the remaining path segments
                $remainingSegments = array_slice($keys, $i);

                if (empty($remainingSegments)) {
                    $data = $default;
                }
                break;
            }
        }

        $value = $data;

        if (is_string($value) && $value !== $default && $this->isIncludeReference($value)) {
            $refDomain = $this->splitDomain($value);
            $refKey = $this->splitId($value);
            if ($refKey === self::DOMAIN_SAME_KEY_WILDCARD) {
                $refKey = $key;
            }

            if (!empty($remainingSegments)) {
                $refKey .= self::KEYS_SEPARATOR . implode(separator: self::KEYS_SEPARATOR, array: $remainingSegments);
            }

            // Pass the remaining segments to the recursive call
            return $this->getValue($refKey, $refDomain);
        }

        return $value;
    }

    public function splitDomain(?string $id): ?string
    {
        if (strpos($id, self::DOMAIN_SEPARATOR)) {
            return current(explode(self::DOMAIN_SEPARATOR, $id));
        }

        return null;
    }

    public function splitId(string $id): ?string
    {
        if (strpos($id, self::DOMAIN_SEPARATOR)) {
            $exp = explode(self::DOMAIN_SEPARATOR, $id);

            return end($exp);
        }

        return $id;
    }

    public function resolveDomain(string $domain): ?string
    {
        if (str_starts_with($domain, self::DOMAIN_PREFIX)) {
            if (isset($this->domainsStack[$domain])) {
                return $domain;
            }
        }

        return $domain;
    }
}
