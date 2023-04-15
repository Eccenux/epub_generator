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
		$this->cleanup($source);

		// - Tidy if needed (close p tags etc).

		// - Add/copy meta (head, foot).
		// (maybe get .mw-body-content and replace innerHTML)
		// echo $source->saveHTML();
		// $destHtml = file_get_contents($this->basePath.$file);
		$dest = new DOMDocument();
		@$dest->loadHTMLFile($this->basePath.$file);
		// $body = $dest->getElementsByTagName('body')[0];
		// $body->innerHTML = $source->getElementsByTagName('body')[0]->innerHTML;
		$this->replaceBody($source, $dest);

		// - Save (but at least for now do not replace previous file).
		$destPath = $this->getDestFile($file);
		$html = $dest->saveHTML();
		// $html = $source->saveHTML();
		return file_put_contents($destPath, $html);
	}

	/** Remove non-export tags (`.ws-noexport`). */
	private function cleanup(DOMDocument $dom)
	{
		// Create DOMXPath
		$xpath = new DOMXPath($dom);

		// Get elements with "ws-noexport"
		$elements = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' ws-noexport ')]");

		// Remove elements
		foreach ($elements as $element) {
			$element->parentNode->removeChild($element);
		}
	}

	/** Remove non-export tags (`.ws-noexport`). */
	private function replaceBody(DOMDocument $sourceDom, DOMDocument $targetDom)
	{
		// Get the <body> element from the source DOM document
		$body = $sourceDom->getElementsByTagName('body')->item(0);

		// Import the <body> element into the target DOM document
		$importedBody = $targetDom->importNode($body, true);

		// Replace the <body> element in the target DOM document with the imported <body> element
		$targetDom->getElementsByTagName('html')->item(0)->replaceChild($importedBody, $targetDom->getElementsByTagName('body')->item(0));
	}
}
