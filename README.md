catalog_exclude
===============

About
-----

Contao/catalog extension

* hide catalog entries that do not belong to the logged in user
* best use with <a href="http://contao.org/de/extension-list/view/catalog_author_field.de.html" title="http://contao.org/de/extension-list/view/catalog_author_field.de.html" target="_blank">[catalog_autor_field]</a> extension from Yanick Witschi (Toflar)

Usage (when not using catalog_author_field)
-----

* Create a new catalog field, column name "user", type "select" and point at the tl_user table
* (if you chose a different name edit the variable $userField in the /dca/tl_catalog_fields.php)