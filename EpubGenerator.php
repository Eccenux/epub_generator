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
	/** Navigation file name. */
	private function getNavFile()
	{
		return $this->basePath.'nav.xhtml';
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
		$this->navPages($source, $file);
		
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

		// Remove elements with "ws-noexport"
		$elements = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' ws-noexport ')]");
		foreach ($elements as $element) {
			$element->parentNode->removeChild($element);
		}

		// Remove elements with rel="mw-deduplicated-inline-style"
		$elements = $xpath->query("//link[@rel='mw-deduplicated-inline-style']");
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

	/** Add Epub3 nav (page-list). */
	private function navPages(DOMDocument $dom, $file)
	{
		// might want to refactor this out later... maybe.
		// this could loop over all landmarks and be a separate step from update...

		// - Get nav file
		$destPath = $this->getNavFile();
		$dest = new DOMDocument();
		@$dest->loadHTMLFile($destPath);

		// - Setup
		$id = 'page-list';
		// $epubNs = 'http://www.idpf.org/2007/ops';

		// - Remove prev list
		$prev = $dest->getElementById($id);
		if ($prev instanceof DOMNode) {
			$prev->parentNode->removeChild($prev);
		}

		// - Create list container
		// <nav epub:type="page-list" hidden="hidden">
		// <ol></ol>
		// </nav>
		$nav = $dest->createElement('nav');
		$nav->setAttribute('hidden', 'hidden');
		$nav->setAttribute('id', $id);
		$nav->setAttribute('epub:type', 'page-list');
		$ol = $dest->createElement('ol');
		$nav->appendChild($ol);

		// - Get page breaks and generate list
		$xpath = new DOMXPath($dom);
		$elements = $xpath->query("//*[contains(@role, 'doc-pagebreak')]");
		// 	<li><a href="georgia.xhtml#page752">752</a></li>
		// 	<li><a href="georgia.xhtml#page753">753</a></li>
		$maxNum = 0;
		// $prevNum = 0;
		$volume = 0;
		foreach ($elements as $element) {
			// new line
			$ol->appendChild(new DOMText("\n\t"));

			// li
			$li = $dest->createElement('li');
			$ol->appendChild($li);

			// <a href="georgia.xhtml#page752">752</a>
			// $page->setAttribute('id', 'epub_p_'.$p);
			// $page->setAttribute('aria-label', $pageText);
			$a = $dest->createElement('a');
			$pid = $element->getAttribute('id');
			$label = $element->getAttribute('aria-label');

			// prepend volume number when needed
			$this->nextVol($label, $maxNum, $volume);
			if ($volume > 0) {
				$label = $this->intToRoman($volume) . ". " . $label;
			}

			$a->setAttribute('href', "$file#$pid");
			$a->textContent = $label;
			$li->appendChild($a);
		}

		// - Add list
		$navContainer = $dest->getElementsByTagName('nav')->item(0)->parentNode;
		// remove white space
		while ($navContainer->lastChild instanceof DOMText) {
			$navContainer->removeChild($navContainer->lastChild);
		}
		$navContainer->appendChild(new DOMText("\n"));
		$navContainer->appendChild($nav);
		$navContainer->appendChild(new DOMText("\n"));

		// - Save changes
		$html = $this->toXhtml($dest);
		// $html = $source->saveHTML();
		return file_put_contents($destPath, $html);
	}

	/** Roman numeric. */
	private function intToRoman(int $num) {
		$map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
		$returnValue = '';
		while ($num > 0) {
			foreach ($map as $roman => $int) {
				if($num >= $int) {
					$num -= $int;
					$returnValue .= $roman;
					break;
				}
			}
		}
		return $returnValue;
	}

	/**
	 * Figure out if there is a next volume.
	 * 
	 * Assume that page labels will go up and then to either:
	 * title page (named page)
	 * or something less then 10.
	 * 
	 * This could be optional...
	 * It would probably be confusing in Cortazar, more then in original :]
	 */
	private function nextVol($label, &$maxNum, &$volume) {
		// figure out if there is a next volume
		$nextVol = false;
		if (is_numeric($label)) {
			$num = intval($label);
			if ($num > 0) {
				if ($num > $maxNum) {
					$maxNum = $num;
				} else {
					$nextVol = true;
				}
			}
		} else if ($maxNum > 10) {
			$nextVol = true;
		}

		// inc vol
		if ($nextVol) {
			$maxNum = 0;
			if ($volume == 0) {
				$volume++;
			}
			$volume++;
		}
		return $nextVol;
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
