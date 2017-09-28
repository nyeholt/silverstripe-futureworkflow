<?php

namespace Symbiote\FutureWorkflow;

/**
 * 
 *
 * @author marcus
 */
class FutureWorkflowJob extends \AbstractQueuedJob
{

    public function __construct($futureWorkflowTrigger = null)
    {
        if ($futureWorkflowTrigger) {
            $this->setObject($futureWorkflowTrigger);
        }
    }

    //put your code here
    public function getTitle()
    {
        $trigger = $this->getObject();
        if ($trigger && $trigger->SourceID) {
            $source = $trigger->Source();
            $boundTo = $source->BoundTo();
            return "Start workflow \"" . $trigger->Source()->Title ."\" on " . ($boundTo ? $boundTo->Title : " unknown");
        }
        return "Expired future workflow job";
    }

    public function process()
    {
        $object = $this->getObject();

        $this->isComplete = true;

        if (!$object) {
            return;
        }

        $object = $this->getObject()->Source();

        if (!$object) {
            return;
        }

        $applyTo = $object->BoundTo();

        if (!$applyTo) {
            return;
        }

        $def = $object->Workflow();

        if (!$def) {
            return;
        }

        try {
            \singleton('WorkflowService')->startWorkflow($applyTo, $def);
        } catch (\ExistingWorkflowException $ex) {

        }
        

        // and we can now delete the trigger
        $this->getObject()->delete();
    }
}