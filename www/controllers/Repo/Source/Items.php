<?php

namespace Controllers\Repo\Source;

use Exception;
use Controllers\Utils\Validate;

/**
 *  Source repositories whose definition contains lists of named items, used to populate the mirror form:
 *  repositories and architectures of an arch source repository, branches, repositories and architectures of an alpine one,
 *  versions and architectures of an openbsd one
 *  Each item is ['name' => ..., 'description' => ...], plus 'eol' (end of life date, YYYY-MM-DD) for the fields of EOL_FIELDS
 */
abstract class Items extends Source
{
    // Package type of the source repository
    protected const TYPE = '';

    // Fields of the definition containing items, with the label of an item
    protected const FIELDS = [];

    // Fields whose items have an end of life date (e.g. alpine branches, openbsd versions)
    protected const EOL_FIELDS = [];

    /**
     *  Return the controller of a source repository of the specified package type
     */
    public static function fromType(string $type, int|null $id = null): Items
    {
        $controllers = [
            'arch'    => Arch::class,
            'alpine'  => Alpine::class,
            'openbsd' => Openbsd::class
        ];

        if (!isset($controllers[$type])) {
            throw new Exception('Invalid source repository type');
        }

        return new $controllers[$type]($id);
    }

    /**
     *  Return the controller of an existing source repository, according to its package type
     */
    public static function fromId(int $id): Items
    {
        $sourceController = new Source();

        if (!$sourceController->existsId($id)) {
            throw new Exception('Source repository #' . $id . ' does not exist');
        }

        return self::fromType($sourceController->getDefinition($id)['type'] ?? '', $id);
    }

    /**
     *  Return the label of an item of the specified field (e.g. 'Repository'), for messages
     */
    public static function getItemLabel(string $field): string
    {
        return static::FIELDS[$field] ?? 'Item';
    }

    /**
     *  Return true if the items of the specified field have an end of life date
     */
    public static function hasEol(string $field): bool
    {
        return in_array($field, static::EOL_FIELDS);
    }

    /**
     *  Add an item to the specified field (e.g. a repository)
     */
    public function addItem(string $field, string $name): void
    {
        $this->checkField($field);

        $name = $this->checkName($name);

        foreach ($this->currentDefinition[$field] ?? [] as $item) {
            if ($item['name'] === $name) {
                throw new Exception(htmlspecialchars($name) . ' already exists');
            }
        }

        $item = ['name' => $name, 'description' => ''];

        if (static::hasEol($field)) {
            $item['eol'] = '';
        }

        $this->currentDefinition[$field][] = $item;

        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Edit the name, description and end of life date (only for the fields of EOL_FIELDS) of an item
     */
    public function editItem(string $field, int $itemId, string $name, string $description, string $eol = ''): void
    {
        $this->checkField($field);

        if (!isset($this->currentDefinition[$field][$itemId])) {
            throw new Exception('Item does not exist');
        }

        $name = $this->checkName($name);

        foreach ($this->currentDefinition[$field] as $currentItemId => $item) {
            if ($currentItemId != $itemId and $item['name'] === $name) {
                throw new Exception(htmlspecialchars($name) . ' already exists');
            }
        }

        $this->currentDefinition[$field][$itemId]['name'] = $name;
        $this->currentDefinition[$field][$itemId]['description'] = trim($description);

        if (static::hasEol($field)) {
            $eol = trim($eol);

            if ($eol !== '' and !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eol)) {
                throw new Exception('Invalid end of life date');
            }

            $this->currentDefinition[$field][$itemId]['eol'] = $eol;
        }

        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Remove an item
     */
    public function removeItem(string $field, int $itemId): void
    {
        $this->checkField($field);

        if (!isset($this->currentDefinition[$field][$itemId])) {
            throw new Exception('Item does not exist');
        }

        unset($this->currentDefinition[$field][$itemId]);
        $this->currentDefinition[$field] = array_values($this->currentDefinition[$field]);

        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Get the items of the specified field of a source repository, to populate the task form selects
     */
    public function getPredefined(string $field, string $source): array
    {
        $this->checkField($field);

        $data = [];
        $source = Validate::string($source);

        if (!$this->exists(static::TYPE, $source)) {
            throw new Exception('Source ' . $source . ' does not exist');
        }

        $definition = $this->getDefinition($this->getIdByTypeName(static::TYPE, $source));

        $items = $definition[$field] ?? [];

        foreach ($items as $index => $item) {
            $text = $item['name'] . (!empty($item['description']) ? ' (' . $item['description'] . ')' : '');

            // Mark the items that reached their end of life, as deb distributions and rpm release versions
            if (!empty($item['eol']) and $item['eol'] < DATE_YMD) {
                $text .= ' (EOL)';
            }

            $data[] = [
                'id' => $item['name'],
                'text' => $text,
                // The first architecture is selected by default, as the item when there is only one
                'selected' => ($field == 'archs' and $index == 0) or count($items) == 1
            ];
        }

        return $data;
    }

    /**
     *  Check that the specified field of the source definition can be edited
     */
    protected function checkField(string $field): void
    {
        if (!isset(static::FIELDS[$field])) {
            throw new Exception('Invalid field');
        }
    }

    /**
     *  Check an item name, used as a directory name, return it trimmed
     */
    protected function checkName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            throw new Exception('Name is required');
        }

        if (!Validate::alphaNumericHyphen($name, ['.']) or str_starts_with($name, '.')) {
            throw new Exception(htmlspecialchars($name) . ' contains invalid characters');
        }

        return $name;
    }
}
