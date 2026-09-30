<?php

declare(strict_types=1);

namespace Tests\Integration\Sanitization;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Sanitize;

/**
 * Metabox values are sanitized for storage, not escaped for display.
 */
class MetaboxSanitizeApisTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    private function clean(string $type, $value)
    {
        $sanitizer = new PPCart_Sanitize();
        $sanitizer->set_data($value);
        $sanitizer->set_type($type);

        return $sanitizer->clean();
    }

    public function test_IT_392_url_fields_keep_query_separators_and_drop_unsafe_schemes(): void
    {
        $url = 'https://example.test/file.zip?a=1&b=2';

        foreach (['url', 'file-upload', 'secure-file-upload'] as $type) {
            $this->assertSame($url, $this->clean($type, $url), $type);
            $this->assertSame('', $this->clean($type, 'javascript:alert(1)'), $type);
        }
    }

    public function test_IT_392_textarea_keeps_line_breaks_and_plain_text_without_entities(): void
    {
        $clean = $this->clean('textarea', "Tom & Jerry's \"deal\"\nline two<script>alert(1)</script>");

        $this->assertSame("Tom & Jerry's \"deal\"\nline two", $clean);
    }

    public function test_IT_392_select_radio_and_color_strip_tags_without_entities(): void
    {
        foreach (['select', 'radio', 'color'] as $type) {
            $this->assertSame('a & "b"', $this->clean($type, '  a & "b"<script>x</script> '), $type);
        }
    }
}
