<?php

namespace Symbiote\FutureWorkflow;

/**
 * 
 *
 * @author marcus
 */
class FutureWorkflowTrigger extends \DataObject
{
    private static $db = [
        'EffectiveTime' => 'SS_Datetime',
    ];

    private static $has_one = [
        'BoundTo' => 'DataObject',
        'Source' => 'Symbiote\FutureWorkflow\FutureWorkflow',
        'Job' => 'QueuedJobDescriptor'
    ];

    private static $summary_fields = [
        'EffectiveTime',
        'Source.Title',
    ];

    public function onBeforeWrite()
    {

        parent::onBeforeWrite();

        if (strtotime($this->EffectiveTime) <= time()) {
            $this->EffectiveTime = '';
        }

        $job = null;

        if ($this->JobID) {
            $job = $this->Job();
            if (!$job || !$job->ID) {
                $this->JobID = 0;
            }
        }

        if (!strlen($this->EffectiveTime) && $job && $job->ID) {
            $job->delete();
            $this->JobID = 0;
        }

        if (strlen($this->EffectiveTime) && $this->isChanged('EffectiveTime', \DataObject::CHANGE_VALUE)) {
            if ($job && $job->ID) {
                $job->delete();
                $this->JobID = 0;
            }
        }

        if (strlen($this->EffectiveTime) && !$this->JobID) {
            $job         = new FutureWorkflowJob($this);
            $this->JobID = singleton('QueuedJobService')->queueJob($job, $this->EffectiveTime);
        }
    }

    public function canEdit($member = null)
    {
        $boundTo = $this->BoundTo();
        if ($boundTo) {
            return $this->BoundTo()->canEdit($member);
        }
        return \Permission::check('CMS_ACCESS_CMSMain');
    }

    public function canDelete($member = null)
    {
        $boundTo = $this->BoundTo();
        if ($boundTo) {
            return $this->BoundTo()->canDelete($member);
        }
        return \Permission::check('CMS_ACCESS_CMSMain');
    }

    public function canView($member = null)
    {
        $boundTo = $this->BoundTo();
        if ($boundTo) {
            return $this->BoundTo()->canView($member);
        }
        return \Permission::check('CMS_ACCESS_CMSMain');
    }
    
}