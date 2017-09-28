# Quick Start

1. Install via composer and run dev/build
2. Add the FutureWorkflowExtension to classes needing future workflows, ie
SiteTree:
  extensions:
    - Symbiote\FutureWorkflow\FutureWorkflowExtension
```

3. Create a workflow in the CMS - admin/workflows/
4. Create a Future Workflow configuration against the page (or parent page, if inherited workflow is enabled)
  1. Navigate to the page, go to the "Settings" tab (top right), then the "Workflow" tab
5. Specify how the future workflow should be triggered
6. Future workflow run-times will be visible as the "Effective time" on the Future triggers listing


## Configuring the future workflow

**At a fixed time**

* Select a Trigger type of "Triggered at specific date"
* Specify an "Execute time" 

Done. 

**Variable time based on actions**

Future workflows have an "Effective Time" aka trigger time, which is evaluated based on a few factors

* The fields being monitored (configurable)
* The event being monitored (edit or publish)
* The `strtotime` compatible description of when the trigger should occur. 

Typically, the Future Workflow should be configured to monitor Title and Content fields; these can be entered
in the "Fields monitored" section. 

Next a "Variable time" should be defined; this should be a strtotime compatible text, for example, `+1 month`. See
https://php.net/strtotime for a full description. 

Now, whenever the relevant event happens (publish or edit) _and_ one of the fields has been changed, the workflow 
trigger date is configured based on the "Variable time" setting

**Apply to all child items**

By default, all child pages will inherit the setting that the parent page uses. This can be toggled with the 
"Apply to children" option. 



