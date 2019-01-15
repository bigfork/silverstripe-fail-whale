# SilverStripe Fail Whale 🐳

SilverStripe error pages, just without the pages.

## What is this?

CMS-editable error pages (404, 500 etc), without clogging up the site tree and confusing content authors who rarely (if ever) need to edit them. Also works without the CMS module installed.

## How does it work?

Error documents are DataObjects which can be edited via the “Settings” area of the CMS. There is no versioning or draft/live stages, so all changes are published immediately. On encountering an error, SilverStripe will look for a matching error document and render it if one is found.

Cached content is still stored in `assets/error-<code>.html` files, and it’s still possible to use custom templates if required.

When the CMS module is installed, `PageController::init()` will be called before rendering the error document to ensure any requirements are included. If you need different behaviour in this method in the event of an error, you can check `if ($this->data()->ClassName === ErrorDocument::class)`.

## How do I use custom templates?

As an example, in the event of a 404 error this module will look for the following templates:

- `<theme>/templates/Bigfork/SilverStripeFailWhale/Model/Layout/ErrorDocument_404.ss`
- `<theme>/templates/Bigfork/SilverStripeFailWhale/Model/Layout/ErrorDocument.ss`
- `<theme>/templates/Bigfork/SilverStripeFailWhale/Model/ErrorDocument.ss`

If none of the above templates are present and you have the CMS module installed, the module will fall back to using the default `Page` templates.

## What’s with the name?

> The “Fail Whale” is an illustration of a white beluga whale held up by a flock of birds, originally named <a title="The Origin of Twitter's &quot;Fail Whale&quot; - Mashable" href="http://mashable.com/2010/08/01/fail-whale-designer-interview/" target="_blank"><em>“Lifting a Dreamer”</em></a>, illustrated by Australian artist <a title="YIYING LU on Twitter" href="http://twitter.com/yiyinglu" target="_blank">Yiying Lu</a>. It was used during periods of downtime by the social networking service Twitter.
> 
> &ndash; http://www.whatisfailwhale.info/

h/t to <a href="https://github.com/andrewandante">@andrewandante</a> for the suggestion!
