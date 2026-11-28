<?php

namespace Controllers\Repo\Source;

use Exception;

/**
 *  Public keys of a source repository stored in its definition ('keys': name, content and link they were downloaded from), used to
 *  verify its signatures when they are not GPG signatures: RSA keys of the alpine source repositories, signify keys of the openbsd ones
 *  Only the keys of a source repository are used to verify its signatures
 *
 *  The class using this trait checks the keys names and contents of its package type: see checkKeyName() and checkKeyContent()
 */
trait Keys
{
    /**
     *  Check a key name, throw an exception if invalid
     */
    abstract protected static function checkKeyName(string $name): void;

    /**
     *  Check a key content, throw an exception if invalid
     */
    abstract protected static function checkKeyContent(string $content): void;

    /**
     *  Add a public key to the source repository, downloaded from the specified URL or from its content (plain text), one of them
     *  only, as the GPG keys of the deb and rpm source repositories
     *  A downloaded key is named after the URL file name unless a name is specified, the name is required with a content
     */
    public function addKey(string $url, string $content, string $name = ''): void
    {
        $url = trim($url);
        $content = trim(str_replace("\r\n", "\n", $content));
        $name = trim($name);

        if (!empty($url) and !empty($content)) {
            throw new Exception('Cannot import a key from URL and plain text at the same time');
        }

        if (empty($url) and empty($content)) {
            throw new Exception('Specify the URL or the content of the key to import');
        }

        if (!empty($url)) {
            $key = static::downloadKey(['link' => $url, 'name' => $name]);
        } else {
            if (empty($name)) {
                throw new Exception('The key name is required when importing a key from plain text');
            }

            static::checkKeyName($name);
            static::checkKeyContent($content . "\n");

            $key = ['name' => $name, 'link' => '', 'content' => $content . "\n"];
        }

        foreach ($this->currentDefinition['keys'] ?? [] as $existingKey) {
            if ($existingKey['name'] === $key['name']) {
                throw new Exception('Key ' . htmlspecialchars($key['name']) . ' already exists');
            }
        }

        $this->currentDefinition['keys'][] = $key;

        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Remove a public key from the source repository
     */
    public function removeKey(string $name): void
    {
        $keys = array_values(array_filter($this->currentDefinition['keys'] ?? [], function ($key) use ($name) {
            return $key['name'] !== $name;
        }));

        if (count($keys) == count($this->currentDefinition['keys'] ?? [])) {
            throw new Exception('Key does not exist');
        }

        $this->currentDefinition['keys'] = $keys;

        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Return the public keys declared in a source repositories list (YAML), with their content
     *  Each key is downloaded from its link, unless its content is specified (with its name)
     */
    public static function importKeys(array $keys): array
    {
        $imported = [];

        foreach ($keys as $key) {
            if (!is_array($key)) {
                throw new Exception('Invalid key definition');
            }

            if (!empty($key['content'])) {
                $name = trim($key['name'] ?? '');

                static::checkKeyName($name);
                static::checkKeyContent($key['content']);

                $key = ['name' => $name, 'link' => '', 'content' => $key['content']];
            } else {
                $key = static::downloadKey($key);
            }

            $imported[$key['name']] = $key;
        }

        return array_values($imported);
    }

    /**
     *  Download a public key from its link, return the key definition: name, link and content
     */
    private static function downloadKey(array $key): array
    {
        $url = $key['link'] ?? '';

        if (!preg_match('#^https?://#', $url)) {
            throw new Exception('Invalid key URL, it must start with http(s)://');
        }

        // The key is named after the URL file name by default (e.g. https://alpinelinux.org/keys/<name>)
        $name = !empty($key['name']) ? $key['name'] : basename((string) parse_url($url, PHP_URL_PATH));

        static::checkKeyName($name);

        try {
            $content = (new \Controllers\HttpRequest())->get([
                'url'            => $url,
                'connectTimeout' => 10,
                'timeout'        => 30,
                'proxy'          => PROXY ?? null,
                'returnOutput'   => true
            ]);
        } catch (Exception $e) {
            throw new Exception('Could not download key ' . htmlspecialchars($name) . ' at ' . htmlspecialchars($url) . ': ' . $e->getMessage());
        }

        if (empty($content) or !is_string($content)) {
            throw new Exception('Could not download key ' . htmlspecialchars($name) . ' at ' . htmlspecialchars($url) . ': empty response');
        }

        static::checkKeyContent($content);

        return ['name' => $name, 'link' => $url, 'content' => $content];
    }
}
