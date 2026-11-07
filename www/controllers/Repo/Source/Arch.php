<?php

namespace Controllers\Repo\Source;

use Exception;
use Controllers\Utils\Validate;

class Arch extends \Controllers\Repo\Source\Source
{
    /**
     *  Add a repository (e.g. core, extra) or an architecture to an arch source repository
     *  $field: 'repos' or 'archs'
     */
    public function addItem(string $field, string $name): void
    {
        $this->checkField($field);

        $name = trim($name);

        if (!Validate::alphaNumericHyphen($name, ['.']) or str_starts_with($name, '.')) {
            throw new Exception(htmlspecialchars($name) . ' contains invalid characters');
        }

        foreach ($this->currentDefinition[$field] ?? [] as $item) {
            if ($item['name'] === $name) {
                throw new Exception(htmlspecialchars($name) . ' already exists');
            }
        }

        $this->currentDefinition[$field][] = ['name' => $name, 'description' => ''];

        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Remove a repository or an architecture from an arch source repository
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
     *  Get predefined repositories and architectures of an arch source repository, to populate the task form selects
     *  $field: 'repos' or 'archs'
     */
    public function getPredefined(string $field, string $source): array
    {
        $this->checkField($field);

        $data = [];
        $source = Validate::string($source);

        if (!$this->exists('arch', $source)) {
            throw new Exception('Source ' . $source . ' does not exist');
        }

        $definition = $this->getDefinition($this->getIdByTypeName('arch', $source));

        $items = $definition[$field] ?? [];

        foreach ($items as $index => $item) {
            $data[] = [
                'id' => $item['name'],
                'text' => $item['name'] . (!empty($item['description']) ? ' (' . $item['description'] . ')' : ''),
                // The first architecture is selected by default, as the repository when there is only one
                'selected' => ($field == 'archs' and $index == 0) or count($items) == 1
            ];
        }

        return $data;
    }

    /**
     *  Check that the specified field of the source definition can be edited
     */
    private function checkField(string $field): void
    {
        if (!in_array($field, ['repos', 'archs'])) {
            throw new Exception('Invalid field');
        }
    }
}
