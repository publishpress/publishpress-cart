<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

require_once dirname(__FILE__) . '/traits/trait-ppcart-debug-log-viewer-reader.php';
require_once dirname(__FILE__) . '/traits/trait-ppcart-debug-log-viewer-page.php';
require_once dirname(__FILE__) . '/traits/trait-ppcart-debug-log-viewer-render.php';
require_once dirname(__FILE__) . '/traits/trait-ppcart-debug-log-viewer-grouping.php';
require_once dirname(__FILE__) . '/traits/trait-ppcart-debug-log-viewer-detection.php';
require_once dirname(__FILE__) . '/traits/trait-ppcart-debug-log-viewer-redaction.php';
require_once dirname(__FILE__) . '/traits/trait-ppcart-debug-log-viewer-files.php';
require_once dirname(__FILE__) . '/traits/trait-ppcart-debug-log-viewer-utilities.php';

/**
 * Parser and modern viewer for the legacy PublishPress Cart debug log.
 */
class PPCart_Debug_Log_Viewer
{
    use PPCart_Debug_Log_Viewer_Reader_Trait;
    use PPCart_Debug_Log_Viewer_Page_Trait;
    use PPCart_Debug_Log_Viewer_Render_Trait;
    use PPCart_Debug_Log_Viewer_Grouping_Trait;
    use PPCart_Debug_Log_Viewer_Detection_Trait;
    use PPCart_Debug_Log_Viewer_Redaction_Trait;
    use PPCart_Debug_Log_Viewer_Files_Trait;
    use PPCart_Debug_Log_Viewer_Utilities_Trait;

    public const DEFAULT_TAIL_BYTES = 1048576;
    public const DEFAULT_READ_LIMIT = 100;
    public const MAX_ROTATED_FILES  = 3;
}
