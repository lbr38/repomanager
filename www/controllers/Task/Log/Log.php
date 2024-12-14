<?php

namespace Controllers\Task\Log;

use Exception;
use JsonException;

class Log
{
    protected $model;
    protected $taskId;

    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
        $this->model = new \Models\Task\Log\Log($taskId);
    }

    /**
     *  Get task log content
     */
    public function getContent() : array
    {
        $content = [];
        $stepController = new \Controllers\Task\Log\Step($this->taskId);
        $subStepController = new \Controllers\Task\Log\SubStep($this->taskId);

        try {
            /**
             *  Get steps
             */
            $steps = $stepController->get($this->taskId);

            /**
             *  Add each step to the content
             */
            foreach ($steps as $step) {
                $stepId = $step['Id'];

                $content['steps'][$step['Identifier']] = [
                    'title' => $step['Title'],
                    'status' => $step['Status'],
                    'start' => $step['Start'],
                    'end' => $step['End'],
                    'duration' => $step['Duration'],
                    'message' => $step['Message']
                ];

                /**
                 *  Get sub steps
                 */
                $subSteps = $subStepController->get($stepId);

                /**
                 *  Add each sub step to the content
                 */
                foreach ($subSteps as $subStep) {
                    $content['steps'][$step['Identifier']]['substeps'][$subStep['Identifier']] = [
                        'title' => $subStep['Title'],
                        'status' => $subStep['Status'],
                        'start' => $subStep['Start'],
                        'end' => $subStep['End'],
                        'duration' => $subStep['Duration'],
                        'note' => $subStep['Note'],
                        'output' => json_decode($subStep['Output'], true)
                    ];
                }
            }
        } catch (Exception $e) {
            throw new Exception('Could not get task log content: ' . $e->getMessage());
        } catch (JsonException $e) {
            throw new Exception('Could not decode task log JSON content: ' . $e->getMessage());
        }

        return $content;
    }
}
