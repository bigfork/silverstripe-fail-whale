<?php

namespace Bigfork\SilverStripeFailWhale\Extensions;

use Bigfork\SilverStripeFailWhale\Model\ErrorDocument;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

class SiteConfigExtension extends Extension
{
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.ErrorDocuments',
            GridField::create(
                'ErrorDocuments',
                'Error Documents',
                ErrorDocument::get(),
                GridFieldConfig_RecordEditor::create()
            )
        );
    }
}