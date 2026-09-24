<?php

declare(strict_types=1);

/**
 * Codeception prints ✔ (U+2714) and ✖ (U+2716). FiraCode Nerd Font does not
 * include those codepoints, and Cursor's terminal does not fall back to another
 * font, so each test line shows a missing glyph. Map them to ✓ / × which the
 * font does contain. ✘ (U+2718) is included for the same reason.
 */
class PpcartNerdConsoleGlyphsFilter extends php_user_filter
{
    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $bucket->data = strtr(
                $bucket->data,
                [
                    "\u{2714}" => "\u{2713}",
                    "\u{2716}" => "\u{00D7}",
                    "\u{2718}" => "\u{00D7}",
                ]
            );
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}

if (! in_array('ppcart.nerd_glyphs', stream_get_filters(), true)) {
    stream_filter_register('ppcart.nerd_glyphs', PpcartNerdConsoleGlyphsFilter::class);
}

if (defined('STDOUT') && is_resource(STDOUT)) {
    stream_filter_append(STDOUT, 'ppcart.nerd_glyphs');
}
