<?php

namespace Bigfork\SilverStripeFailWhale\Logging;

use Bigfork\SilverStripeFailWhale\Model\ErrorDocument;
use SilverStripe\Control\Director;
use SilverStripe\Logging\DebugViewFriendlyErrorFormatter;

class ErrorDocumentErrorFormatter extends DebugViewFriendlyErrorFormatter
{
    public function output($statusCode)
    {
        // Ajax content is plain-text only
        if (Director::is_ajax()) {
            return $this->getTitle();
        }

        // Determine if cached ErrorDocument content is available
        $content = ErrorDocument::get_content_for_errorcode($statusCode);
        if ($content) {
            return $content;
        }

        // Fallback to default output
        return parent::output($statusCode);
    }
}
