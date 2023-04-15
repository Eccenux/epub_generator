## Simple updates?

Updating pre-generated files with simple PHP/Node processor?

- ✅Download HTML/XTML (`action=render`?).
- ✅Remove non-export tags (`.ws-noexport`).
- Tidy if needed (close p tags etc).
- ✅Add/copy meta (head, foot)

Epub.css and indexes should already be there so only re-building HTML would be required.

## Next steps

- Add Epub3 pages in xhtml.
- Add Epub3 nav (page-list)?
- Make page ids universal.
- Zip to epub.
- Parameters / cmd options.
- Make similar output as wsexport? Seems like it replaces `<div class="mw-parser-output">` with `<body...><section data-mw-section-id="0">`.
- Do not replace national characters with entities (but can replace e.g. thinspace).

http://kb.daisy.org/publishing/docs/navigation/pagelist.html#ex 
