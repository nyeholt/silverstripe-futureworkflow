<?php

namespace Symbiote\FutureWorkflow;

/**
 * Represents a workflow that will be started at a future-date
 *
 * @author marcus
 */
class FutureWorkflow extends \DataObject
{
    const TYPE_FIXED_DATE = 'date';
    const TYPE_EDIT = 'edit';
    const TYPE_PUBLISH = 'publish';

    private static $db = [
        'Title' => 'Varchar(128)',
        'Type' => 'Varchar',
        'VariableTime' => 'Varchar',
        'ExecuteTime' => 'SS_Datetime',
        'ContentFields' => 'MultiValueField',
        'ApplyToChildren'   => 'Boolean',
    ];
    private static $has_one = [
        'BoundTo' => 'DataObject',
        'Workflow' => 'WorkflowDefinition',
    ];
    private static $summary_fields = [
        'Title', 'Workflow.Title',
    ];
    private static $defaults = [
        'ApplyToChildren' => true
    ];
    private static $singular_name = 'Future Workflow';
    public $types = [
        'edit' => 'Set start date based on page edit',
        'publish' => 'Set start date based on page publish',
        self::TYPE_FIXED_DATE => 'Triggered at specific time'
    ];

    public function onBeforeWrite()
    {

        parent::onBeforeWrite();

        if ($this->Type === self::TYPE_FIXED_DATE) {
            $this->VariableTime  = '';
        }
    }

    /**
     * Check whether fixed time trigger needs setting
     */
    public function checkFixedTimes($againstObject) {
        if ($this->Type === self::TYPE_FIXED_DATE && $this->ExecuteTime) {
            $trigger = $this->triggerFor($againstObject);
            $trigger->EffectiveTime = $this->ExecuteTime;
            $trigger->write();
        }
    }

    /**
     * Figures out whether a change is relevant for this future
     * workflow, and if so creates a futureworkflowtrigger
     *
     * @param DataObject $againstObject
     * @param type $changes
     * @param type $changeType
     * @return boolean
     */
    public function evaluateDataChanges($againstObject, $changes, $changeType)
    {
        if (!strlen($this->VariableTime)) {
            return;
        }

        if ($this->Type === strtolower($changeType)) {
            $lookingFor = $this->ContentFields->getValues();
            $update     = true;
            if (count($lookingFor)) {
                $found = false;
                foreach ($lookingFor as $field) {
                    if (isset($changes[$field])) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    return false;
                }
            }

            // okay, we know we can set things now
            $effectiveTime = date('Y-m-d H:i:s', strtotime($this->VariableTime));

            if (strlen($effectiveTime)) {
                // find an existing match, or create a new one
                $trigger = $this->triggerFor($againstObject);
                $trigger->EffectiveTime = $effectiveTime;
                $trigger->write();

                return $trigger;
            }
            
        }

        return false;
    }

    /**
     * Create a trigger for the future workflow against the relevant context
     * object
     *
     * @return FutureWorkflowTrigger
     */
    protected function triggerFor($context) {
        $filter = [
            'BoundToID' => $context->ID,
            'BoundToClass' => $context->class,
            'SourceID'  => $this->ID,
        ];

        $trigger = FutureWorkflowTrigger::get()->filter($filter)->first();
        if (!$trigger) {
            $trigger = FutureWorkflowTrigger::create($filter);
            $trigger->write();
        }
        return $trigger;
    }

    /**
     * @return \FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->replaceField('Type', \DropdownField::create('Type', 'Trigger type', $this->types));
        $fields->replaceField('EffectiveTime', \ReadonlyField::create('ReadonlyTime', 'Runs at', $this->EffectiveTime));

        $fields->dataFieldByName('VariableTime')->setRightTitle(_t('FutureWorkflow.STRTOTIME_FORMAT',
                'strtotime compatible string'));

        $fields->addFieldToTab('Root.Main',
            \MultiValueTextField::create('ContentFields', 'Fields monitored')
                ->setRightTitle('If set, trigger time is updated if these fields have changed. If not set, all fields are considered'));

        $fields->removeByName('BoundToID');
        $fields->removeByName('JobID');
        
        return $fields;
    }

    public function canEdit($member = null)
    {
        return $this->BoundTo()->canEdit($member);
    }

    public function canDelete($member = null)
    {
        return $this->BoundTo()->canDelete($member);
    }

    public function canView($member = null) {
        return $this->BoundTo()->canView($member);
    }
}