# LinkedVocabularyEditor
LinkedVocabularyEditor is a Media Wiki extension that enables to load, browse, collaboratively edit, and publish linked vocabularies.

installation: 

1. run in LinkedVocabularyEditor:

php -r "readfile('https://getcomposer.org/installer');" | php
php composer.phar install


2. add the following line to LocalSettings.php

require_once( "$IP/extensions/VocabularyEditor/VocabularyEditor.php" );

