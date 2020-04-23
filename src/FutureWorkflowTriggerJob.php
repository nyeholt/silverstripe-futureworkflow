<?php

namespace Symbiote\FutureWorkflow;

use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use Symbiote\AdvancedWorkflow\Services\ExistingWorkflowException;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 *
 *
 * @author marcus
 */
class FutureWorkflowTriggerJob extends AbstractQueuedJob
{

    public function __construct($timespan = null)
    {
        if ($timespan) {
            $this->timespan = $timespan;
            $jobs = $this->getTriggers($timespan);
            $this->totalSteps = $jobs->count();
            $this->currentStep = 0;
        }
    }

    //put your code here
    public function getTitle()
    {
        return "Trigger workflows";
    }

    public function setup()
    {
        $jobs = $this->getTriggers();
        $this->triggerIds = $jobs->column();
    }

    protected function getTriggers($timespan = 0)
    {
        return FutureWorkflowTrigger::get()->filter('EffectiveTime:LessThan', date('Y-m-d H:i:s', time() + $timespan));
    }

    public function process()
    {
        $triggers = $this->triggerIds;

        $nextTrigger = array_shift($triggers);

        $this->currentStep++;

        $this->triggerIds = $triggers;
        if (count($triggers) === 0) {
            $this->isComplete = true;
        }

        $trigger = FutureWorkflowTrigger::get()->byID($nextTrigger);

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

    public function afterComplete()
    {
        $time = $this->timespan ? $this->timespan : 900;

        $nextJob = new FutureWorkflowTriggerJob($time);
        $nexttime = date('Y-m-d H:i:s', time() + $time);

        singleton(QueuedJobService::class)->queueJob($nextJob, $nexttime);
    }
}
