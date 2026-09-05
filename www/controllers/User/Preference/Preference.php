<?php

namespace Controllers\User\Preference;

use Exception;
use JsonException;
use Controllers\Utils\Convert;

class Preference extends \Controllers\User\User
{
    private $preferenceModel;
    private $defaultPreferences = [
        'repositories' => [
            'list' => [
                'expand' => false,
                'row-by-row' => false
            ]
        ]
    ];

    public function __construct()
    {
        parent::__construct();
        $this->preferenceModel = new \Models\User\Preference();
    }

    /**
     *  Get user preferences
     */
    public function get(int $id): array
    {
        // Check if user exists
        if (!$this->existsId($id)) {
            throw new Exception('User does not exist');
        }

        // Get user preferences
        $preferences = $this->preferenceModel->get($id);

        // If preferences are empty, create default preferences and return them
        if (empty($preferences)) {
            try {
                $this->preferenceModel->set($id, json_encode($this->defaultPreferences, JSON_THROW_ON_ERROR));
            } catch (JsonException $e) {
                throw new Exception('Error setting default preferences: ' . $e->getMessage());
            }

            return $this->defaultPreferences;
        }

        // Decode preferences (JSON) and return them
        try {
            $preferences = json_decode($preferences, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            throw new Exception('Error decoding preferences: ' . $e->getMessage());
        }

        // Merge with default preferences if some keys are missing
        $preferences = array_replace_recursive($this->defaultPreferences, $preferences);

        return $preferences;
    }

    /**
     *  Get default preferences definition
     */
    public function getDefault(): array
    {
        return $this->defaultPreferences;
    }

    /**
     *  Filter preferences to only keep keys that exist in the schema
     */
    private function filterPreferencesBySchema(array $preferences, array $schema): array
    {
        $filtered = [];

        foreach ($schema as $key => $value) {
            if (!isset($preferences[$key])) {
                continue;
            }

            if (is_array($value)) {
                // Recursive filtering for nested arrays
                $filtered[$key] = $this->filterPreferencesBySchema($preferences[$key], $value);
            } else {
                // Keep scalar values
                $filtered[$key] = $preferences[$key];
            }
        }

        return $filtered;
    }

    /**
     *  Set user preferences
     */
    public function set(int $id, array $preferences): void
    {
        // Check if user exists
        if (!$this->existsId($id)) {
            throw new Exception('User does not exist');
        }

        // Get current preferences (includes defaults for missing keys)
        $currentPreferences = $this->get($id);

        // Update preferences using dot-separated keys
        foreach ($preferences as $key => $value) {
            $keys = explode('.', $key);
            $lastKey = array_key_last($keys);

            // Navigate through the default preferences to find the expected type
            $defaultValue = $this->defaultPreferences;
            $isValidKey = true;

            foreach ($keys as $k) {
                if (isset($defaultValue[$k])) {
                    $defaultValue = $defaultValue[$k];
                } else {
                    $isValidKey = false;
                    break;
                }
            }

            // Reject invalid keys (not in the schema)
            if (!$isValidKey) {
                throw new Exception('Invalid preference key: ' . $key);
            }

            // Validate value type based on the default value's type
            $expectedType = gettype($defaultValue);

            if ($expectedType === 'boolean') {
                // For booleans, accept string 'true'/'false' or actual boolean
                if (!is_bool($value) && !(is_string($value) && in_array($value, ['true', 'false'], true))) {
                    throw new Exception('Preference ' . $key . ' expects a boolean, got ' . gettype($value));
                }

                $validatedValue = is_bool($value) ? $value : Convert::toBool($value);
            } elseif ($expectedType === 'array') {
                // For arrays, accept arrays only
                if (!is_array($value)) {
                    throw new Exception('Preference ' . $key . ' expects an array, got ' . gettype($value));
                }

                $validatedValue = $value;
            } else {
                // For strings, numbers, etc. - accept same type only
                if (gettype($value) !== $expectedType) {
                    throw new Exception('Preference ' . $key . ' expects a ' . $expectedType . ', got ' . gettype($value));
                }

                $validatedValue = $value;
            }

            // Set the validated value
            $current = &$currentPreferences;
            foreach ($keys as $i => $k) {
                if ($i === $lastKey) {
                    $current[$k] = $validatedValue;
                } else {
                    $current = &$current[$k];
                }
            }
            unset($current);
        }

        // Filter preferences to only keep keys that exist in the schema
        $currentPreferences = $this->filterPreferencesBySchema($currentPreferences, $this->defaultPreferences);

        // Save updated preferences
        try {
            $this->preferenceModel->set($id, json_encode($currentPreferences, JSON_THROW_ON_ERROR));
        } catch (JsonException $e) {
            throw new Exception('Error encoding preferences: ' . $e->getMessage());
        }
    }
}
