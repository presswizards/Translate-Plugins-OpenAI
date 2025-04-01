# Translate-Plugins-OpenAI
Translate your plugin's .po file from English to other languages using OpenAI models via this simple PHP script!

## Useage

- Replace en.po with your plugin's en.po file.
- Edit the translate-po.php file, and set the array to the language codes you want it translated into.
- Run this from the CLI with your OpenAI API Key as the only argument:

php ./translate-po.php sk-proj-fake3asF9f87qw98efybq9pwh3rp8v2y5rp98qy239p5n8v

It will loop through the country codes, saving out the .po files for each country in the array you set.

## Language Reference

• English → en.po • Spanish → es.po • German → de.po • French → fr.po • Italian → it.po • Portuguese → pt.po • Dutch → nl.po • Russian → ru.po • Chinese (Simplified) → zh_CN.po • Japanese → ja.po • Korean → ko.po • Arabic → ar.po • Turkish → tr.po • Hindi → hi.po • Polish → pl.po • Swedish → sv.po • Danish → da.po • Finnish → fi.po • Greek → el.po • Czech → cs.po • Hungarian → hu.po • Thai → th.po • Hebrew → he.po
