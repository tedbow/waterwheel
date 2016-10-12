Waterwheel Module
=================
This module is used the Waterwheel Javascript Library to retrieve information from
Drupal that is not provide by core.

Currently this module needs a specific version of the Schemata Module
https://github.com/tedbow/schemata/tree/8.x-1.x-waterwheel

Open API Specification Document
===============================
The Open API specification document in JSON format that describes all of the
entity REST resources can be downloaded from:

yoursite.com/water-wheel/swagger/entities?_format=json

Using within a Drupal site
----------------------------------------------
If you would like to access the Waterwheel.js library from Javascript on your Drupal site:

1. Down the latest built version of the waterwheel.js file from the [releases page](https://github.com/acquia/waterwheel-js/releases).
2. Place the waterwheel.js file into the root /libraries folder.
3. The file should be at [DRUPAL_ROOT]/libraries/waterwheel/waterwheel.js
4. If you have already enabled the module clear your caches("drush cr").
5. Include the library like this: 
 ```php
 $element['#attached']['library'][] = 'waterwheel/waterwheel';
 ```
 
