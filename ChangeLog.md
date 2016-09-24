# Dokuwiki pageredirect plugin change log

When writing entries, refer to [Keep a CHANGELOG](http://keepachangelog.com/) for guidelines.

All notable changes to this project will be documented in this file.

## [UNRELEASED]

  -

## [20140414]

  - add `<br/>` tag to the redirect note to avoid being covered by page TOC. #4
  - add Japanese translations. #7
  - make `~~REDIRECT` pattern non-greedy. d77260fb, #5
  - honour `conf['useheading']` for redirect note. a693d6b, #6
  - basic support for external redirects. 8c725f3d
  - fix access to protected variable after splitbrain/dokuwiki#555. 29a6d85, #10, #11

## [20120816]

  - allow `#redirect` syntax to be lowercase, but it must be start on line. 1362442

## [20120612]

  - match anything in page name, to wiki path it is converted internally. f423934
  - preserve `#section` anchors on redirect. c31b525
  - make redirects 301 redirect permanently to be SEO friendly, 9796335
  - apply prevent conflict patch from wiki comments. 87145da
  - add alternative `#REDIRECT namespace/pagename` syntax. 01efce2
  - add zh-tw translations. 82539ae
  - add Korean translations. 6aa688d
  - add portugese translations. e22f33a
  - fix matching page with `#REDIRECT` syntax. 4dca632

## [20070124]

  - Build 2

[UNRELEASED]: https://github.com/glensc/dokuwiki-plugin-pageredirect/compare/20140414...master
[20140414]: https://github.com/glensc/dokuwiki-plugin-pageredirect/compare/20120816...20140414
[20120816]: https://github.com/glensc/dokuwiki-plugin-pageredirect/compare/20120612...20120816
[20120612]: https://github.com/glensc/dokuwiki-plugin-pageredirect/compare/20070124...20120612
[20070124]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commits/20070124
