Waterwheel Module
=================
This module is used the Waterwheel Javascript Library to retrieve information from
Drupal that is not provide by core.

Current resources
-----------------

GET /entity/types

returns:
```php
{
  "block": {
    "label": "Block",
    "type": "config",
    "more": "/entity/types/block"
  },
  "block_content": {
    "label": "Custom block",
    "type": "content",
    "more": "/entity/types/block_content"
  },
  "block_content_type": {
    "label": "Custom block type",
    "type": "config",
    "more": "/entity/types/block_content_type"
  }
  ...
}
```


GET /entity/types/node

Returns:
```php
{
  "label": "Content",
  "type": "content",
  "methods": {
    "GET": "/node/{node}",
    "POST": "/entity/node",
    "DELETE": "/node/{node}",
    "PATCH": "/node/{node}"
  },
  "fields": {
    "nid": {
      "label": "ID",
      "type": "integer",
      "required": false,
      "readonly": true,
      "cardinality": 1
    },
    "uuid": {
      "label": "UUID",
      "type": "uuid",
      "required": false,
      "readonly": true,
      "cardinality": 1
    },
    ...
   }
}
```

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
 
