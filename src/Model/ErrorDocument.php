<?php

namespace Bigfork\SilverStripeFailWhale\Model;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;

class ErrorDocument extends DataObject
{
    private static $table_name = 'ErrorDocument';

    private static $db = [
        'ErrorCode' => 'Int',
        'Title' => 'Varchar(255)',
        'Content' => 'HTMLText'
    ];

    private static $indexes = [
        'ErrorCode' => true
    ];

    private static $default_sort = 'ErrorCode ASC';

    private static $summary_fields = [
        'ErrorCode',
        'Title'
    ];

    /**
     * Whether error documents should be cached to a static file
     *
     * @config
     * @var bool
     */
    private static $enable_static_file = true;

    /**
     * Prefix for storing error files in the {@see GeneratedAssetHandler} store.
     * Defaults to empty (top level directory)
     *
     * @config
     * @var string
     */
    private static $store_filepath = null;

    public function canCreate($member = null, $context = [])
    {
        $config = SiteConfig::current_site_config();
        return $config->canEdit($member);
    }

    public function canView($member = null)
    {
        $config = SiteConfig::current_site_config();
        return $config->canView($member);
    }

    public function canEdit($member = null)
    {
        $config = SiteConfig::current_site_config();
        return $config->canEdit($member);
    }

    public function canDelete($member = null)
    {
        $config = SiteConfig::current_site_config();
        return $config->canEdit($member);
    }

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->replaceField(
                'ErrorCode',
                DropdownField::create(
                    'ErrorCode',
                    $this->fieldLabel('ErrorCode'),
                    $this->getCodes()
                )
            );
        });

        return parent::getCMSFields();
    }

    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);

        $labels['ErrorCode'] = _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE', 'Error code');
        $labels['Title'] = _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.TITLE', 'Title');

        return $labels;
    }

    protected function onAfterWrite()
    {
        $this->writeStaticContent();
        parent::onAfterWrite();
    }

    /**
     * @throws ValidationException
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        // Only run on ErrorDocument class directly, not subclasses
        if (static::class !== self::class) {
            return;
        }

        $defaultPages = $this->getDefaultRecords();
        foreach ($defaultPages as $defaultData) {
            $this->requireDefaultRecordFixture($defaultData);
        }
    }

    /**
     * @return array
     */
    protected function getDefaultRecords()
    {
        $data = [
            [
                'ErrorCode' => 404,
                'Title' => _t(
                    'Bigfork\\SilverStripeFailWhale\\ErrorDocument.DEFAULTERRORPAGETITLE',
                    'Page not found'
                ),
                'Content' => _t(
                    'Bigfork\\SilverStripeFailWhale\\ErrorDocument.DEFAULTERRORPAGECONTENT',
                    '<p>Sorry, it seems you were trying to access a page that doesn\'t exist.</p>'
                    . '<p>Please check the spelling of the URL you were trying to access and try again.</p>'
                )
            ],
            [
                'ErrorCode' => 500,
                'Title' => _t(
                    'Bigfork\\SilverStripeFailWhale\\ErrorDocument.DEFAULTSERVERERRORPAGETITLE',
                    'Server error'
                ),
                'Content' => _t(
                    'Bigfork\\SilverStripeFailWhale\\ErrorDocument.DEFAULTSERVERERRORPAGECONTENT',
                    '<p>Sorry, there was a problem with handling your request.</p>'
                )
            ]
        ];

        $this->extend('getDefaultRecords', $data);

        return $data;
    }

    /**
     * @param array $defaultData
     * @throws \SilverStripe\ORM\ValidationException
     */
    protected function requireDefaultRecordFixture(array $defaultData)
    {
        $code = $defaultData['ErrorCode'];
        $documentExists = true;
        $document = self::get()->filter(['ErrorCode' => $code])->first();
        if (!$document) {
            $documentExists = false;
            $document = self::create($defaultData);
            $document->write();
        }

        // Check if static files are enabled
        if (!self::config()->enable_static_file) {
            return;
        }

        // Ensure this document has cached error content
        $success = true;
        if (!$document->hasStaticContent()) {
            // Update static content
            $success = $document->writeStaticContent();
        } elseif ($documentExists) {
            // If document exists and already has content, no alteration_message is displayed
            return;
        }

        if ($success) {
            DB::alteration_message(
                sprintf('%s error document created', $code),
                'created'
            );
        } else {
            DB::alteration_message(
                sprintf('%s error document could not be created. Please check permissions', $code),
                'error'
            );
        }
    }

    /**
     * @return array
     */
    protected function getCodes()
    {
        return [
            400 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_400', '400 - Bad Request'),
            401 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_401', '401 - Unauthorized'),
            403 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_403', '403 - Forbidden'),
            404 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_404', '404 - Not Found'),
            405 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_405', '405 - Method Not Allowed'),
            406 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_406', '406 - Not Acceptable'),
            407 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_407', '407 - Proxy Authentication Required'),
            408 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_408', '408 - Request Timeout'),
            409 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_409', '409 - Conflict'),
            410 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_410', '410 - Gone'),
            411 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_411', '411 - Length Required'),
            412 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_412', '412 - Precondition Failed'),
            413 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_413', '413 - Request Entity Too Large'),
            414 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_414', '414 - Request-URI Too Long'),
            415 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_415', '415 - Unsupported Media Type'),
            416 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_416', '416 - Request Range Not Satisfiable'),
            417 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_417', '417 - Expectation Failed'),
            422 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_422', '422 - Unprocessable Entity'),
            429 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_429', '429 - Too Many Requests'),
            500 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_500', '500 - Internal Server Error'),
            501 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_501', '501 - Not Implemented'),
            502 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_502', '502 - Bad Gateway'),
            503 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_503', '503 - Service Unavailable'),
            504 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_504', '504 - Gateway Timeout'),
            505 => _t('Bigfork\\SilverStripeFailWhale\\ErrorDocument.CODE_505', '505 - HTTP Version Not Supported'),
        ];
    }

    /**
     * Determine if static content is cached for this document
     *
     * @return bool
     */
    protected function hasStaticContent()
    {
        if (!self::config()->enable_static_file) {
            return false;
        }

        // Attempt to retrieve content from generated file handler
        $filename = $this->getErrorFilename();
        $storeFilename = File::join_paths(self::config()->store_filepath, $filename);
        $result = self::get_asset_handler()->getContent($storeFilename);
        return !empty($result);
    }

    /**
     * Write out the published version of the document to the filesystem
     *
     * @return boolean - true if the document write was successful
     */
    protected function writeStaticContent()
    {
        if (!self::config()->enable_static_file) {
            return false;
        }

        // Run the page (reset the theme, it might've been disabled by LeftAndMain::init())
        $originalThemes = SSViewer::get_themes();
        // Clear any existing requirements
        Requirements::clear();
        try {
            // Restore front-end themes from config
            $themes = SSViewer::config()->get('themes') ?: $originalThemes;
            SSViewer::set_themes($themes);
            // Render page as non-member in live mode
            $response = Member::actAs(null, function () {
                return self::response_for($this->ErrorCode);
            });

            $errorContent = null;
            if ($response) {
                $errorContent = $response->getBody();
            }
        } finally {
            // Restore themes
            SSViewer::set_themes($originalThemes);
            // Clear any requirements loaded during rendering
            Requirements::clear();
        }

        // Make sure we have content to save
        if (!$errorContent) {
            return false;
        }

        // Store file content in the default store
        $storeFilename = File::join_paths(
            self::config()->store_filepath,
            $this->getErrorFilename()
        );
        self::get_asset_handler()->setContent($storeFilename, $errorContent);

        return true;
    }

    /**
     * @param int $errorCode
     * @param HTTPRequest $request
     * @return HTTPResponse|null
     */
    public static function response_for($errorCode, HTTPRequest $request = null)
    {
        $content = null;
        try {
            // Try to fetch document dynamically first
            /** @var self $document */
            $document = self::get()->filter(['ErrorCode' => $errorCode])->first();
            if ($document) {
                $content = $document->render($request)->forTemplate();
            }
        } catch (\Exception $e) {
            // Fall back to static HTML copy
            $content = self::get_content_for_errorcode($errorCode);
        }

        if ($content) {
            $response = new HTTPResponse();
            $response->setStatusCode($errorCode);
            $response->setBody($content);
            return $response;
        }

        return $content ?: null;
    }

    /**
     * @param HTTPRequest $request
     * @return \SilverStripe\ORM\FieldType\DBHTMLText
     */
    public function render(HTTPRequest $request = null)
    {
        $templatesFound = [];
        $templatesFound[] = SSViewer::get_templates_by_class(static::class, "_{$this->ErrorCode}", self::class);
        $templatesFound[] = SSViewer::get_templates_by_class(static::class, '', self::class);

        if (class_exists(\PageController::class)) {
            $page = \Page::create();
            $page->ID = -1;
            $page->ClassName = self::class;
            $controller = ModelAsController::controller_for($page);
            if ($request) {
                $controller->setRequest($request);
            }
            $controller->doInit();
            $templatesFound[] = $page->getViewerTemplates();

            return $controller->renderWith(array_merge(...$templatesFound), $this);
        }

        return $this->renderWith(array_merge(...$templatesFound));
    }

    /**
     * Returns statically cached content for a given error code
     *
     * @param int $statusCode
     * @return string|null
     */
    public static function get_content_for_errorcode($statusCode)
    {
        if (!self::config()->enable_static_file) {
            return null;
        }

        // Attempt to retrieve content from generated file handler
        $filename = self::get_error_filename($statusCode);
        $storeFilename = File::join_paths(
            self::config()->store_filepath,
            $filename
        );

        return self::get_asset_handler()->getContent($storeFilename);
    }

    /**
     * Gets the filename identifier for the given error code.
     * Used when handling responses under error conditions.
     *
     * @param int $statusCode A HTTP Statuscode, typically 404 or 500
     * @param ErrorDocument $instance Optional instance to use for name generation
     * @return string
     */
    protected static function get_error_filename($statusCode, ErrorDocument $instance = null)
    {
        if (!$instance) {
            $instance = self::singleton();
        }

        // Allow modules to extend this filename (e.g. for multi-domain, translatable)
        $name = "error-{$statusCode}.html";
        $instance->extend('updateErrorFilename', $name, $statusCode);

        return $name;
    }

    /**
     * Get filename identifier for this record.
     * Used for generating the filename for the current record.
     *
     * @return string
     */
    protected function getErrorFilename()
    {
        return self::get_error_filename($this->ErrorCode, $this);
    }

    /**
     * @return GeneratedAssetHandler
     */
    protected static function get_asset_handler()
    {
        return Injector::inst()->get(GeneratedAssetHandler::class);
    }
}
