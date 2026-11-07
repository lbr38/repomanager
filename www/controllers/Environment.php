<?php

namespace Controllers;

use Exception;
use Controllers\Utils\Validate;

class Environment
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Environment();
    }

    /**
     *  Return all environments name
     */
    public function getAllByName(): array
    {
        return $this->model->getAllByName();
    }

    /**
     *  Get environment color
     */
    public static function getEnvColor(string $name)
    {
        // Retrieve color from ENVS array
        if (defined('ENVS')) {
            foreach (ENVS as $env) {
                if ($env['Name'] == $name and !empty($env['Color'])) {
                    return $env['Color'];
                }
            }
        }

        return '#ffffff';
    }

    /**
     *  Add a new environment
     */
    public function add(string $name, string $color) : void
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        $name = Validate::string($name);
        $color = Validate::string($color);

        if (!Validate::alphaNumericHyphen($name)) {
            throw new Exception('Environment name contains invalid characters');
        }

        /**
         *  Check if environment already exists
         */
        if ($this->exists($name)) {
            throw new Exception('Environment ' . $name . ' already exists');
        }

        /**
         *  Check that color is a valid hexadecimal color
         */
        if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            throw new Exception('Invalid color');
        }

        /**
         *  Add env to database
         */
        $this->model->add($name, $color, 'false');
    }

    /**
     *  Delete environment
     */
    public function delete(int $id) : void
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        /**
         *  Check if environment exists
         */
        if ($this->existsId($id) === false) {
            throw new Exception('Environment does not exist');
        }

        /**
         *  Delete env from database
         */
        $this->model->delete($id);
    }

    /**
     *  Add / edit the actual environments
     */
    public function edit(array $envs) : void
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        // Delete all envs from database before inserting the new ones
        $this->model->deleteAll();

        // Check that all specified env values are valid
        foreach ($envs as $env) {
            $name = Validate::string($env['name']);
            $color = Validate::string($env['color']);
            $protected = Validate::string($env['protected']);

            if (empty($name)) {
                throw new Exception('Environment name is empty');
            }

            if (empty($color)) {
                throw new Exception('Environment color is empty');
            }

            if (!in_array($protected, ['true', 'false'])) {
                throw new Exception('Environment protected value is invalid');
            }

            if (!Validate::alphaNumericHyphen($name)) {
                throw new Exception('Environment ' . $name . ' contains invalid characters');
            }

            if ($this->exists($name)) {
                throw new Exception('Environment ' . $name . ' already exists');
            }

            $this->model->add($name, $color, $protected);
        }
    }

    /**
     *  Return all environments list
     */
    public function listAll(): array
    {
        return $this->model->listAll();
    }

    /**
     *  Return the list of protected environments
     */
    public function getProtected(): array
    {
        return $this->model->getProtected();
    }

    /**
     *  Return the environments that can be pointed to a snapshot, in the configured order
     *  A protected environment cannot be pointed: it is not listed in the forms, protectedNote() explains why
     */
    public static function getPointable(): array
    {
        return array_values(array_filter(ENVS, function ($env) {
            return ($env['Protected'] ?? 'false') != 'true';
        }));
    }

    /**
     *  Return a list of environments for a message, singular or plural depending on the count
     *  e.g. 'protected environment <b>prod</b>' or 'protected environments <b>preprod, prod</b>'
     */
    public static function nameList(array $envs, string $adjective = ''): string
    {
        $word = count($envs) > 1 ? 'environments' : 'environment';

        if (!empty($adjective)) {
            $word = $adjective . ' ' . $word;
        }

        return $word . ' <b>' . htmlspecialchars(implode(', ', $envs)) . '</b>';
    }

    /**
     *  Return a note explaining why protected environments are not listed in the forms, or an empty string if there is none
     */
    public static function protectedNote(): string
    {
        $protected = [];

        foreach (ENVS as $env) {
            if (($env['Protected'] ?? 'false') == 'true') {
                $protected[] = $env['Name'];
            }
        }

        if (empty($protected)) {
            return '';
        }

        if (count($protected) > 1) {
            return '<p class="note margin-top-5">' . ucfirst(self::nameList($protected, 'protected')) . ' cannot be pointed and are not listed. Remove the protection in the settings to point them.</p>';
        }

        return '<p class="note margin-top-5">' . ucfirst(self::nameList($protected, 'protected')) . ' cannot be pointed and is not listed. Remove the protection in the settings to point it.</p>';
    }

    /**
     *  Return true if the environment is protected
     */
    public function isProtected(string $name): bool
    {
        if (in_array($name, $this->getProtected())) {
            return true;
        }

        return false;
    }

    /**
     *  Return default environment
     */
    public function default(): string
    {
        return $this->model->default();
    }

    /**
     *  Return the last configured environment name
     */
    public function last()
    {
        return $this->model->last();
    }

    /**
     *  Return total environment
     */
    public function total()
    {
        return count($this->model->listAll());
    }

    /**
     *  Return true if env Id exists
     */
    public function existsId(int $id) : bool
    {
        return $this->model->existsId($id);
    }

    /**
     *  Return true if env exists
     */
    public function exists(string $name) : bool
    {
        return $this->model->exists($name);
    }
}
