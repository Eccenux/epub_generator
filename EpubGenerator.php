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
	 * Download raw HTML.
	 *
	 * @param string $url Source URL.
	 * @param string $file HTML to update.
	 */
	public function download(string $url, string $file)
	{
		$cachePath = $this->getCacheFile($file);
		if (file_exists($cachePath)) {
			return true;
		}

		// - Download HTML/XTML (`action=render`?).
		$html = file_get_contents($url);
		// echo $html;

		// - Save as ~cache.
		return file_put_contents($cachePath, $html);
	}

	/** Temp file name. */
	private function getCacheFile(string $file)
	{
		return $this->basePath.$file.'.__EpubGenerator__';
	}
	/** Work file name. */
	private function getDestFile(string $file)
	{
		return $this->basePath.$file.'.__EpubGenerator__.html';
	}

	/**
	 * Update HTML.
	 *
	 * @param string $url Source URL.
	 * @param string $file HTML to update.
	 */
	public function update(string $url, string $file)
	{
		// - Download/read HTML/XTML (`action=render`?).
		// could later check last change and update raw html only when needed
		$this->download($url, $file);
		$cachePath = $this->getCacheFile($file);

		// parse
		$sourceHtml = file_get_contents($cachePath);
		$source = new DOMDocument();
		// $source->loadHTML("<html><body>Test<br></body></html>");
		@$source->loadHTML("<!DOCTYPE html>"
			."\n<html><head><meta charset='UTF-8'></head>"
			."\n<body>"
			."\n$sourceHtml"
			."\n</body>"
			."\n</html>"
		);

		// - Remove non-export tags (`.ws-noexport`).

		// - Tidy if needed (close p tags etc).

		// - Add/copy meta (head, foot).
		// (maybe get .mw-body-content and replace innerHTML)
		// echo $source->saveHTML();

		// - Save (but at least for now do not replace previous file).
		$destPath = $this->getDestFile($file);
		$html = $source->saveHTML();
		return file_put_contents($destPath, $html);
	}
}
