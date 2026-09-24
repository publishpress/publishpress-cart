/**
 * Playwright list reporter prints ✘ (U+2718). FiraCode Nerd Font does not
 * include that codepoint, and Cursor's terminal does not fall back, so failed
 * tests show a missing glyph. Map dingbats to ✓ / × which the font contains.
 */
const REPLACEMENTS = {
	'\u2718': '\u00D7', // ✘ → ×
	'\u2716': '\u00D7', // ✖ → ×
	'\u2714': '\u2713', // ✔ → ✓
};

function replaceGlyphs(text) {
	return text.replace(/[\u2718\u2716\u2714]/g, (ch) => REPLACEMENTS[ch] || ch);
}

function patchStream(stream) {
	if (!stream || typeof stream.write !== 'function' || stream.__ppcartNerdGlyphs) {
		return;
	}

	stream.__ppcartNerdGlyphs = true;
	const original = stream.write.bind(stream);

	stream.write = (chunk, encoding, callback) => {
		if (typeof chunk === 'string') {
			chunk = replaceGlyphs(chunk);
		} else if (Buffer.isBuffer(chunk)) {
			chunk = Buffer.from(replaceGlyphs(chunk.toString('utf8')), 'utf8');
		}

		return original(chunk, encoding, callback);
	};
}

patchStream(process.stdout);
patchStream(process.stderr);
