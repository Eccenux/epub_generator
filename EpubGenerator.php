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
		return $this->basePath.$file.'.__EpubGenerator__.raw.html';
	}
	/** Work file name. */
	private function getDestFile(string $file)
	{
		// return $this->basePath.$file.'.__EpubGenerator__.html';
		return $this->basePath.$file;
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
			."\n<section>$sourceHtml</section>"
			."\n</body>"
			."\n</html>"
		);

		// - Add Epub3 pages in xhtml.
		// - Add Epub3 nav (page-list)?
		// - Generate ToC from headers?
		$this->pages($source);
		
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
		$html = $this->toXhtml($dest);
		// $html = $source->saveHTML();
		return file_put_contents($destPath, $html);
	}

	/** Final cleanup to html. */
	private function toXhtml(DOMDocument $dest)
	{
		$html = $dest->saveXML();
		// proper order, not what DOMDocument does :-/
		// <!DOCTYPE html>
		// <[?]xml version="1.0" encoding="UTF-8" standalone="yes"[?]>
		$html = preg_replace('#<[?]xml[^>]+[?]>#', '', $html);
		$html = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . $html;
		return $html;
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

	/** Add epub page markers. */
	private function pages(DOMDocument $dom)
	{
		// Create DOMXPath
		$xpath = new DOMXPath($dom);

		// Get elements with "ws-noexport"
		$elements = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' ws-pagenum ')]");
	
		// Replace elements
		$p = 0;
		foreach ($elements as $element) {
			$p++;
			$pageText = $element->getElementsByTagName('a')->item(0)->textContent;
			// <span
			// role="doc-pagebreak"
			// id="pg24"
			// aria-label="24"/>
			$page = $dom->createElement('span');
			$page->setAttribute('role', 'doc-pagebreak');
			$page->setAttribute('id', 'epub_p_'.$p);
			$page->setAttribute('aria-label', $pageText);
			// $element->parentNode->replaceChild($element, $page);
			$element->parentNode->insertBefore($page, $element);
			$element->parentNode->removeChild($element);
		}
	}

	/** Remove non-export tags (`.ws-noexport`). */
	private function replaceBody(DOMDocument $sourceDom, DOMDocument $targetDom)
	{
		// get container we added when creating sourceDom
		$container = $sourceDom->getElementsByTagName('section')->item(0);
		// import container to target
		$importedContainer = $targetDom->importNode($container, true);
		
		// $targetBody->replaceChild($importedBody, $targetBody->firstChild);
		// remove content from target body
		$targetBody = $targetDom->getElementsByTagName('body')->item(0);
		foreach ($targetBody->childNodes as $node) {
			$targetBody->removeChild($node);
		}
		// append
		$targetBody->appendChild($importedContainer);
	}
}
