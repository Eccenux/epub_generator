<?php

/**
 * Simple ePub updates.
 */
class EpubGenerator
{
	private string $basePath;

	/** Init. */
	public function __construct(string $basePath) {
		$this->basePath = $basePath;
	}

	/**
	 * Update HTML.
	 *
	 * @param string $url Source URL.
	 * @param string $file HTML to update.
	 */
	public function update(string $url, string $file)
	{
		// - Download HTML/XTML (`action=render`?).
		// - Remove non-export tags (`.ws-noexport`).
		// - Tidy if needed (close p tags etc).
		// - Add/copy meta (head, foot).
		// - Save (but at least for now do not replace previous file).
	}
}
