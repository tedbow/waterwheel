This module is used the Waterwheel Javascript Library to retrieve information from
Drupal that is not provide by core.

Current resources:

GET /entity/types

returns:
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


GET /entity/types/node

Returns:

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
