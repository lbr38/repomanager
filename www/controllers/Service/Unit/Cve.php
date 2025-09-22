<?php

namespace Controllers\Service\Unit;

use Exception;

class Cve extends \Controllers\Service\Service
{
    private $cveImportController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->cveImportController = new \Controllers\Cve\Tools\Import();
    }

    /**
     *  Import CVE data
     */
    public function import()
    {
        parent::log('Importing CVE data...');

        try {
            $this->cveImportController->import();
            parent::log('CVE data import finished');
        } catch (Exception $e) {
            throw new Exception('Error while importing CVE data: ' . $e->getMessage());
        }
    }
}
