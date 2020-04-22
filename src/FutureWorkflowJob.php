<?php

namespace Symbiote\FutureWorkflow;

use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use Symbiote\AdvancedWorkflow\Services\ExistingWorkflowException;
use Symbiote\AdvancedWorkflow\Services\WorkflowService;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;

/**
 *
 *
 * @author marcus
 */
class FutureWorkflowJob extends AbstractQueuedJob
{

    public function __construct($futureWorkflowTrigger = null)
    {
        if ($futureWorkflowTrigger && $futureWorkflowTrigger instanceof FutureWorkflowTrigger) {
            $this->setObject($futureWorkflowTrigger);
        }
    }

    //put your code here
    public function getTitle()
    {
        $trigger = $this->getObject();
        if ($trigger && $trigger->SourceID) {
            $source = $trigger->Source();
            $boundTo = $trigger->BoundTo();
            return "Start workflow \"" . $trigger->Source()->Title . "\" on " . ($boundTo ? $boundTo->Title : " unknown");
        }
        return "Expired future workflow job";
    }

    public function process()
    {
        $trigger = $this->getObject();

        $this->isComplete = true;

        if (!$trigger) {
            return;
        }

        $futureWorkflow = $trigger->Source();

        if (!$futureWorkflow) {
            return;
        }

        $applyTo = $trigger->BoundTo();

        if (!$applyTo) {
            return;
        }

        $def = $futureWorkflow->Workflow();

        if (!$def) {
            $this->addMessage("No workflow found to execute");
            return;
        }

        try {
            $current = $applyTo->getWorkflowInstance();
            if (!$current) {
                $instance = new WorkflowInstance();
                $instance->beginWorkflow($def, $applyTo);
                $instance->execute();
            }
        } catch (ExistingWorkflowException $ex) {
        }


        // and we can now delete the trigger
        $trigger->delete();
    }
}
