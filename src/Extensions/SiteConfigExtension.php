<?php

namespace Bigfork\SilverStripeFailWhale\Extensions;

use Bigfork\SilverStripeFailWhale\Model\ErrorDocument;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Subsites\State\SubsiteState;

class SiteConfigExtension extends Extension
{
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.ErrorDocuments',
            $gridField = GridField::create(
                'ErrorDocuments',
                'Error Documents',
                ErrorDocument::get(),
                GridFieldConfig_RecordEditor::create()
            )
        );

        if (class_exists(Subsite::class) && ErrorDocument::config()->get('enable_subsites')) {
            $list = $gridField->getList()->filter(['SubsiteID' => SubsiteState::singleton()->getSubsiteId()]);
            $gridField->setList($list);
        }
    }
}
