<?php

namespace Controllers;

use Exception;

class Environment
{
    private $model;

    public function __construct(array $variables = [])
    {
        $this->model = new \Models\Environment();
    }

    /**
     *  Delete environment
     */
    public function delete(string $name)
    {
        /**
         *  Check if environment exists
         */
        if ($this->exists($name) === false) {
            throw new Exception('Environment <b>' . $name . '</b> does not exist');
        }

        /**
         *  Delete env from database
         */
        $this->model->delete($name);

        /**
         *  Clean repos list cache
         */
        \Controllers\App\Cache::clear();
    }

    /**
     *  Add / edit the actual environments
     */
    public function edit(array $envs)
    {
        /**
         *  Check if all specified envs are valid then add them to $envsToInsert array
         */
        foreach ($envs as $env) {
            if (!\Controllers\Common::isAlphanumDash($env)) {
                throw new Environment('Environment <b>' . $env . '</b> contains invalid characters');
            }

            $envsToInsert[] = $env;
        }

        /**
         *  Si l'array contient des environnements valides à insérer alors on traite
         */
        if (!empty($envsToInsert)) {
            /**
             *  Delete all envs from database before inserting the new ones
             */
            $this->model->deleteAll();

            /**
             *  Insert all environments
             */
            foreach ($envsToInsert as $env) {
                if (!empty($env)) {
                    $this->model->new($env);
                }
            }

            /**
             *  Clean repos list cache
             */
            \Controllers\App\Cache::clear();
        }
    }

    /**
     *  Return all environments list
     */
    public function listAll()
    {
        return $this->model->listAll();
    }

    /**
     *  Return default environment
     */
    public function default()
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
     *  Return true if env exists
     */
    public function exists(string $name)
    {
        return $this->model->exists($name);
    }
}
