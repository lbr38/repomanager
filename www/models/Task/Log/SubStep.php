<?php

namespace Models\Task\Log;

use Exception;

class SubStep extends \Models\Model
{
    public function __construct(int $taskId)
    {
        $this->getConnection('task-log', $taskId);
    }

    /**
     *  Add a new sub step in the database
     */
    public function new(int $stepId, string $identifier, string $title, string $note) : void
    {
        try {
            $stmt = $this->dedicatedDb->prepare("INSERT INTO substeps ('Identifier', 'Title', 'Note', 'Status', 'Start', 'Step_id') VALUES (:identifier, :title, :note, 'running', :start, :stepId)");
            $stmt->bindValue(':identifier', $identifier);
            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':note', $note);
            $stmt->bindValue(':start', microtime(true));
            $stmt->bindValue(':stepId', $stepId);
            $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Set the latest sub step status
     */
    public function status(int $subStepId, string $status) : void
    {
        try {
            $end = microtime(true);

            /**
             *  Get sub step start time
             */
            $stmt = $this->dedicatedDb->prepare("SELECT Start FROM substeps WHERE Id = :subStepId");
            $stmt->bindValue(':subStepId', $subStepId);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $start = $row['Start'];
            }

            /**
             *  Calculate sub step duration
             */
            $duration = $end - $start;

            $stmt = $this->dedicatedDb->prepare("UPDATE substeps SET Status = :status, End = :end, Duration = :duration WHERE Id = :subStepId");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':end', microtime(true));
            $stmt->bindValue(':duration', $duration);
            $stmt->bindValue(':subStepId', $subStepId);
            $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Get sub steps for the provided step ID
     */
    public function get(int $stepId) : array
    {
        $data = [];

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT * FROM substeps WHERE Step_id = :stepId");
            $stmt->bindValue(':stepId', $stepId);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $data[] = $row;
            }

            return $data;
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Return the latest sub step Id
     */
    public function getLatestSubStepId(int $stepId) : int|null
    {
        $data = null;

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT Id FROM substeps WHERE Step_id = :stepId ORDER BY Id DESC LIMIT 1");
            $stmt->bindValue(':stepId', $stepId);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $data = $row['Id'];
            }

            return $data;
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Return the sub step Id by identifier
     */
    public function getSubStepIdByIdentifier(int $stepId, string $identifier) : int
    {
        $data = '';

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT Id FROM substeps WHERE Step_id = :stepId AND Identifier = :identifier ORDER BY Id DESC LIMIT 1");
            $stmt->bindValue(':stepId', $stepId);
            $stmt->bindValue(':identifier', $identifier);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $data = $row['Id'];
            }

            return $data;
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Return the output of the latest sub step
     */
    public function getOutput(int $substepId) : string|null
    {
        $data = '';

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT Output FROM substeps WHERE Id = :substepId");
            $stmt->bindValue(':substepId', $substepId);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $data = $row['Output'];
            }

            return $data;
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Write output to the database
     */
    public function writeOutput(int $substepId, string $output) : void
    {
        try {
            $stmt = $this->dedicatedDb->prepare("UPDATE substeps SET Output = :output WHERE Id = :substepId");
            $stmt->bindValue(':output', $output);
            $stmt->bindValue(':substepId', $substepId);
            $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }
}
