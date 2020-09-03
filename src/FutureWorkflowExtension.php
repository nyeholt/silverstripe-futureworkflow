<?php

namespace Symbiote\FutureWorkflow;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use Symbiote\AdvancedWorkflow\Services\WorkflowService;

/**
 *
 *
 * @author marcus
 */
class FutureWorkflowExtension extends DataExtension
{
    private static $has_many = [
        'FutureJobs' => 'Symbiote\FutureWorkflow\FutureWorkflow',
    ];

    /**
     * @var WorkflowService
     */
    public $workflowService;

    /**
     *
     * @var DataList
     */
    protected $_cache_FutureJobs;

    public function updateSettingsFields(FieldList $fields)
    {
        $config = GridFieldConfig_RecordEditor::create();
        $grid   = GridField::create('FutureJobs', 'Future workflows', $this->owner->FutureJobs(), $config);
        $fields->addFieldToTab('Root.Workflow', $grid);


        $triggers = FutureWorkflowTrigger::get()->filter([
            'BoundToID' => $this->owner->ID,
            'BoundToClass' => $this->owner->getClassName(),
        ]);
        if ($triggers->count()) {
            $config = GridFieldConfig_RecordEditor::create();
            $grid   = GridField::create('FutureTriggers', 'Future triggers', $triggers, $config);
            $fields->addFieldToTab('Root.Workflow', $grid);
        }
    }

    /**
     * Build up the list of future jobs inherited or otherwise
     */
    public function collectFutureJobs()
    {
        if (!$this->_cache_FutureJobs) {
            if ($this->owner->hasMethod('getAncestors')) {
                $ancestors = $this->owner->getAncestors();
                $ids = $ancestors->column('ID');
                $ids[] = $this->owner->ID;

                $hierarchy = ClassInfo::getValidSubClasses(ClassInfo::baseDataClass($this->owner->getClassName()));

                $parents = ArrayList::create(FutureWorkflow::get()->filter([
                    'BoundToID' => $ids,
                    'BoundToClass' => $hierarchy,
                    'ApplyToChildren' => 1
                ])->toArray());

                $mine = ArrayList::create($this->owner->FutureJobs()->toArray());

                $parents->merge($mine);
                $this->_cache_FutureJobs = $parents;
            } else {
                $this->_cache_FutureJobs = $this->owner->FutureJobs();
            }
        }

        return $this->_cache_FutureJobs;
    }

    public function onBeforeWrite()
    {
        $changes    = $this->owner->getChangedFields(true, DataObject::CHANGE_VALUE);
        $jobs = $this->collectFutureJobs();
        foreach ($jobs as $j) {
            $j->evaluateDataChanges($this->owner, $changes, FutureWorkflow::TYPE_EDIT);
        }
    }

    public function onBeforePublish(&$original)
    {
        $originalData = $original ? $original->getQueriedDatabaseFields() : [];
        $new  = $this->owner->getQueriedDatabaseFields();

        $changes = [];
        foreach ($new as $k => $v) {
            if (!isset($originalData[$k])) {
                $changes[$k] = [
                    'before' => '',
                    'after' => $v,
                ];
                continue;
            }
            if (isset($originalData[$k]) && is_scalar($v) && $v != $originalData[$k]) {
                $changes[$k] = [
                    'before' => $originalData[$k],
                    'after' => $v,
                ];
            }
        }

        $jobs = $this->collectFutureJobs();
        foreach ($jobs as $j) {
            $j->evaluateDataChanges($this->owner, $changes, FutureWorkflow::TYPE_PUBLISH);
        }
    }

    public function onAfterWrite()
    {
        $jobs = $this->collectFutureJobs();
        foreach ($jobs as $j) {
            $j->checkFixedTimes($this->owner);
        }
    }

}
