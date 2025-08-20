<?php

namespace Bigfork\SilverStripeFailWhale\Extensions;

use Bigfork\SilverStripeFailWhale\Model\ErrorDocument;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Extension;

class ControllerExtension extends Extension
{
    /**
     * @param $statusCode
     * @param HTTPRequest $request
     * @throws HTTPResponse_Exception
     */
    public function onBeforeHTTPError($statusCode, HTTPRequest $request): void
    {
        if ($request->isAjax()) {
            return;
        }

        $response = ErrorDocument::response_for($statusCode, $request);
        if ($response) {
            throw new HTTPResponse_Exception($response);
        }
    }
}
