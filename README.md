# Multilanguage
A limited attempt to make parts of Omeka multilanguage

# Installation and Configuration

Install the plugin in the usual Omeka way.

## Configuration

In configuration, you can select the languages into which your site could be translated (see Limitations below).

You can also select the elements (applies to Items and Collections), that are translatable. If something like
Dublin Core Identifier is made translatable, you will make me cry.

# Features

## Language Selection

The plugin should respect browser settings for preferred language. Alternatively, it plays well with the Guest User plugin
to allow guest users to select their preferred language.

## Elements

From the plugin configuration screen, check the elements that will be available for translation. 
This will apply to Items and Collections. 

Also, select the languages available for translation. The edit screens will add clickables to add a translation for those elements.

Those links just display the not-always-transparent locale code. Basic training will be needed to teach translators which codes correspond to which languages.

## Other Content

Simple Pages and Exhibits can be assigned a language code from the Multilanguage Content tab. For a multilanguage site,
you will have to recreate your pages and exhibits in the new languages.

# Limitations

## Base Languages

Omeka has its own system for i18n of core admin text. This plugins does not address core text in any way.

Language options are based on the existance of core translation files.

## Elements

The translations submitted via the admin screens MUST be IDENTICAL to the original language content. Any edit to the
Item or Collection elements MUST be followed by a new translation for each value. That is necessary to allow for
translations of multiple values for each element (e.g., multiple subjects, each one translated).

It might work well to solidify the base language text, then do translations. But all workflows differ.

The Dublin Core Title element is tricky. In the flow of filters down to Multilanguage, it is already translated
from '[Untitled]' into a target language.

Interactions with other plugins might produce unexpected results.



## Other Content

Simple Pages and Exhibits can be assigned a language code from the Multilanguage Content tab. For a multilanguage site,
you will have to recreate your pages and exhibits in the new languages.

