Multilanguage (plugin for Omeka)
================================

[Multilanguage] is an [Omeka] plugin that is a limited attempt to make parts of
Omeka multilanguage. It integrates the [locale switcher] from [BibLibre] for
public front-end and admin back-end, and [Translations], for management of
translations of specific or hard coded theme strings.

Status
--------
**UNMAINTAINED**

This plugin should be considered orphaned. The original main developer can no longer afford the maintenance time.
Feel free to fork and continue development! I'm happy to include a link to it here if you do so.

Installation
------------

Uncompress files and rename plugin folder `Multilanguage`.

Then install it like any other Omeka plugin and follow the config instructions.


Configuration
-------------

In configuration, you can select the languages into which your site could be
translated (see Limitations below).

You can also select the elements (applies to Items and Collections), that are
translatable. If something like Dublin Core Identifier is made translatable,
you will make me cry.


Features
--------

### Language Selection

The plugin should respect browser settings for preferred language.
Alternatively, it plays well with the Guest User plugin to allow guest users to
select their preferred language.
Anyway, the user can always use the flag icons.

### Record Elements

From the plugin configuration screen, check the elements that will be available
for translation. This will apply to Items and Collections.

Also, select the languages available for translation. The edit screens will add
clickables to add a translation for those elements.

Those links just display the not-always-transparent locale code. Basic training
will be needed to teach translators which codes correspond to which languages.

In the public front-end, the visitor will see the translated metadata according
to the current language. If the metadata is not translated, the original
metadata is displayed.

### Other Content

Simple Pages and Exhibits can be assigned a language code from the Multilanguage
Content tab. For a multilanguage site, you will have to recreate your pages and
exhibits in the new languages.

In the public front-end, the menu will display only links to simple pages that
matches the current language of the user. So the admin should includes all in
the navigation menu.

For the exhibits, the list of exhibits will be limited to the exhibits that
match the current language of the user.

The language of the exhibit pages is forced to their exhibit’s one.

The pages and exhibits with a language that doesn’t match the current language
are still accessible, as long as the link is known or is hard coded somewhere.

### Translations of specific strings

Often, the theme contains hard-coded strings. To get them translated, you first
need to translate them, then to make them available in Omeka.

#### Translations of strings

In Omeka, the translations are managed with `.po` files in the directory `application/languages/`
for the core and in  the directory `languages/` of each enabled plugin. This
plugin works the same.

Simply update the translations files either in the directory `languages/` of the
current theme, either in the directory `languages/` of the plugin: the main one
`template.pot` and each translation like `fr.po` and `fr.mo`. You can add any
language, but respect the names of the files (see `application/languages/`) and
copy the two files `.po` and `.mo` for each language.

The translations themselves can be created with a tool like [poedit], or [lokalize],
or any other compliant software or online service.

#### Reset cache of translations

Omeka uses a cache to store translations. To reset it, go the the config page of
the plugin and check the box. It must be done each time a language is updated.

When the cache is not cleared, you shoud reset the cache manually. You can
restart the web server or to remove all files starting with `omeka_i18n_cache`
in the temp directory of the web server, usually `/tmp`, or `/tmp/systemd-private-xxx/tmp`.
Or wait some hours for the automatic refresh of the translations.

### Theme adaptation

#### Standard functions

- `metadata()`
  The option `no_escape` should be set to `true` anytime.

```php
// Instead of:
metadata($item, array('Dublin Core', 'Description'));
// the theme should use:
html_escape(metadata($item, array('Dublin Core', 'Description'), array('no_escape' => true)));
```
  In particular, this is required when the option `snippet` is used.

```php
// Instead of:
metadata($item, array('Dublin Core', 'Description'), array('snippet' => 150));
// the theme should use:
html_escape(snippet(metadata($item, array('Dublin Core', 'Description'), array('no_escape' => true)), 0, 150));
```

For the function metadata(), the key `display_title` may not be translated, so
use `array('Dublin Core', 'Title')`.

#### Specific functions

Some functions should be used in themes in order to use features of Omeka.

- `locale_record()`
- `locale_record_from_id_or_slug()`
- `locale_exhibit_builder_display_random_featured_exhibit()`
- `locale_exhibit_builder_random_featured_exhibit()`
- `locale_convert_url()`

See the file [`helpers/functions.php`] for more information.


Limitations
-----------

### Base Languages

Omeka has its own system for i18n of core admin text. This plugin does not
address core text in any way.

Language options are based on the existance of core translation files.

### Record Elements

The translations submitted via the admin screens MUST exactly match the original
language element content. Any edit to the elements MUST be followed by a new
translation for each value. That is necessary to allow for translations of
multiple values for each element (e.g., multiple subjects, each one translated).

It might work well to solidify the base language text, then do translations. But
all workflows differ.

Of course, the translation itself need not actually be a translation of the text
-- it could be completely original text in another language -- but it will
always have to match with base-language text.

The Dublin Core Title element is tricky. In the flow of filters down to
Multilanguage, it is already translated from '[Untitled]' into a target
language.

Interactions with other plugins might produce unexpected results.

### Other Content

Simple Pages and Exhibits can be assigned a language code from the Multilanguage
Content tab. For a multilanguage site, you will have to recreate your pages and
exhibits in the new languages.

If the plugin is installed, Simple Pages and Exhibits content will disappear
without updating the language assignments from the Multilanguage Content tab.
Each new simple page or Exhibit requires a language assignment from that page.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [plugin issues] page on GitHub.


License
-------

### Plugin

This plugin is published under [GNU/GPL v3].

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

### Libraries

The flag icons are released under the MIT license.


Copyright
---------

* Copyright 2015-2016 Patrick Murray-John (see [patrickmj] on Github)
* Copyright 2017 BibLibre (see [BibLibre] on Github)
* Copyright Daniel Berthereau, 2018 (see [Daniel-KM] on GitHub)


[Multilanguage]: https://github.com/patrickmj/multilanguage
[Omeka]: https://omeka.org
[locale switcher]: https://github.com/Daniel-KM/Omeka-plugin-LocaleSwitcher
[Translations]: https://github.com/Daniel-KM/Omeka-plugin-Translations
[poedit]: https://poedit.net
[lokalize]: https://www.kde.org/applications/development/lokalize
[`helpers/functions.php`]: https://github.com/patrickmj/Multilanguage/blob/master/helpers/functions.php
[flag-icon-css]: http://flag-icon-css.lip.is/
[plugin issues]: https://github.com/patrickmj/Multilanguage/issues
[GNU/GPL v3]: https://www.gnu.org/licenses/gpl-3.0.html
[patrickmj]: https://github.com/patrickmj
[BibLibre]: https://github.com/BibLibre
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"

