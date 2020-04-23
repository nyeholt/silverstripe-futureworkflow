<?php

namespace Symbiote\FutureWorkflow;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

/**
 *
 *
 * @author marcus
 */
class FutureWorkflowTrigger extends DataObject
{
    private static $table_name = 'FutureWorkflowTrigger';

    private static $db = [
        'EffectiveTime' => 'Datetime',
    ];

    private static $has_one = [
        'BoundTo' => DataObject::class,
        'Source' => FutureWorkflow::class,
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

    }

    public function canEdit($member = null)
    {
        $boundTo = $this->BoundTo();
        if ($boundTo) {
            return $this->BoundTo()->canEdit($member);
        }
        return Permission::check('CMS_ACCESS_CMSMain');
    }

    public function canDelete($member = null)
    {
        $boundTo = $this->BoundTo();
        if ($boundTo) {
            return $this->BoundTo()->canDelete($member);
        }
        return Permission::check('CMS_ACCESS_CMSMain');
    }

    public function canView($member = null)
    {
        $boundTo = $this->BoundTo();
        if ($boundTo) {
            return $this->BoundTo()->canView($member);
        }
        return Permission::check('CMS_ACCESS_CMSMain');
    }
}
