# LinkedVocabularyEditor
LinkedVocabularyEditor is a Media Wiki extension that enables to load, browse, collaboratively edit, and publish linked vocabularies.



## installation: 

**1. run in LinkedVocabularyEditor**

```
php -r "readfile('https://getcomposer.org/installer');" | php
php composer.phar install
```

**2. add the following line to your LocalSettings.php**

```
require_once( "$IP/extensions/VocabularyEditor/VocabularyEditor.php" );
```

**3. replace the content of LinkedVocabularyEditor\api\databaseSettings.php with the content of section Database settings from the LocalSettings.php **

```
<?php
## Database settings
$wgDBtype = "mysql";
$wgDBserver = "localhost";
$wgDBname = "wikiseas2";
$wgDBuser = "root";
$wgDBpassword = "root";
```