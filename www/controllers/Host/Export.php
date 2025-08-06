<?php

namespace Controllers\Host;

use Exception;
use JsonException;

class Export extends \Controllers\Host
{
    /**
     *  Export a list of hosts to a CSV format
     */
    public function export(array $hosts)
    {
        $csv = [];

        if (empty($hosts)) {
            throw new Exception('No hosts provided for export');
        }

        /**
         *  First check that hosts Id are valid and exist
         */
        foreach ($hosts as $id) {
            if (!is_numeric($id)) {
                throw new Exception('Invalid host Id: ' . $id);
            }

            if (!$this->existsId($id)) {
                throw new Exception('Host with Id ' . $id . ' does not exist');
            }
        }

        /**
         *  Generate the CSV header
         */
        $csv[] = [
            'Id',
            'Hostname',
            'IP address',
            'OS',
            'OS Version',
            'OS Family',
            'Kernel',
            'Architecture',
            'Type',
            'Profile',
            'Environment',
            'Agent status',
            'Agent status date',
            'Agent status time',
            'Agent version',
            'Reboot required',
        ];

        /**
         *  For each host, get its information and add it to the CSV array
         */
        foreach ($hosts as $id) {
            $data = $this->getAll($id);

            $csv[] = [
                $data['Id'],
                $data['Hostname'],
                $data['Ip'],
                $data['Os'],
                $data['Os_version'],
                $data['Os_family'],
                $data['Kernel'],
                $data['Arch'],
                $data['Type'],
                $data['Profile'],
                $data['Env'],
                $data['Online_status'],
                $data['Online_status_date'],
                $data['Online_status_time'],
                $data['Linupdate_version'],
                $data['Reboot_required'],
            ];
        }

        /**
         *  Encode to JSON and return the CSV data
         */
        try {
            return json_encode($csv, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Error encoding CSV data: ' . $e->getMessage());
        }
    }
}
