## Simple updates?

Updating pre-generated files with simple PHP/Node processor?

- ✅Download HTML/XTML (`action=render`?).
- ✅Remove non-export tags (`.ws-noexport`).
- ✅Tidy if needed (close p tags etc).
- ✅Add/copy meta (head, foot)

Epub.css and indexes should already be there so only re-building HTML would be required.

## Next steps

- ✅Add Epub3 pages in xhtml.
- ✅Add Epub3 nav (page-list)?
- Make labels include volume or something?
- Make page ids universal (might have multiple djvu per html).
- Generate ToC from headers?
- Split by headers/prp-pages-output?
- Zip to epub.
- Parameters / cmd options.
- Make similar output as wsexport? Seems like it replaces `<div class="mw-parser-output">` with `<body...><section data-mw-section-id="0">`.
- Do not replace national characters with entities (but can replace e.g. thinspace).
- Do I need to filter duplicate CSS from templates? Maybe remove redundant link-css markers.

Epub3 pages:
http://kb.daisy.org/publishing/docs/navigation/pagelist.html#ex
Note that adding `doc-pagebreak` is not enough to have a page navigation. Thorium will not pick that up.

## Parse API vs render

Parse API has more options but ultimately seems to return the same code.
There are some differences in HTML comments, but other then that the HTML is the same.
