# APINest

When you request an item from the Omeka S APIs, the linked data contained in the JSON-LD response require additional queries to be retrieved. This module allows you to get them nested directly into the initial response.

Current version: v0.1.3

This module is still under active development. It is recommended to wait for the v1.0 release before using it on a production environment.

## Installation

See general end user documentation for [Installing a module](http://omeka.org/s/docs/user-manual/modules/#installing-modules)

## Module settings

Once installed and activated, click the **Configure** button to set the following settings:
- **Allowed APIs**: only requests sent through these APIs will be processed. Leave unchecked to disable the processing.
  - **REST API**: the module will process the requests sent to the `/api/items/` endpoint.
  - **PHP API**: the module will process the queries received by the item show page *(more implementations to come…)*.
- **Default merge key**:
  - **Linked data**: if checked, the data will be added to the JSON-LD tree under its corresponding key.
  - **apinest**: if checked, the data will be added to the `o:apinest` key.

## Usage

Each time an item is requested through an allowed API, the module checks if the `nest` parameter is present in the query string. If yes, it retrieves the corresponding linked data and adds them to the response tree.

The operating procedures for both APIs are detailed below.

### Common parameters
- `nest`: a comma-separated list of keys. The following keys are supported:
  - `o:item_set`
  - `o:media`
  - `o:owner`
  - `o:primary_media`
  - `o:resource_class`
  - `o:resource_template`
  - `o:site`

  *(more to come…)*

- `merge` *(optional)*: overrides the **Default merge key** setting:
  - If set to `1`, the data will be added to the JSON-LD tree under its corresponding key.
  - if set to `0`, the data will be added to the `o:apinest` key.

### Using the REST API

Add the parameters to your request:
```
https://my.web.site/api/items/1234?nest=o:owner,o:resource_class,o:resource_template,o:primary_media
```

The response will embed the requested keys (see example below).

### Using the PHP API

Add the parameters to the URL of your item show page:

```
https://my.web.site/item/1234?nest=o:owner,o:resource_class,o:resource_template,o:primary_media
```

The `<script type="application/ld+json">…</script>` block provided in the HTML content of the resulting page should contain the requested keys. Note that **JSON-LD embedding** should be enabled in your site settings.

## Example of use
If you send a standard request to Omeka S, like for example:
```
https://my.web.site/api/items/1234
```
you get a response like the following:
```
{
  "@context": "https://my.web.site/api-context",
  "@id": "https://my.web.site/api/items/1234",
  "@type": ["o:Item", "bibo:Document"],
  "o:id": 1234,
  "o:is_public": true,
  "o:owner": {
    "@id": "https://my.web.site/api/users/7",
    "o:id": 7
  },
  "o:resource_class": {
    "@id": "https://my.web.site/api/resource_classes/49",
    "o:id": 49
  },
  "o:resource_template": {
    "@id": "https://my.web.site/api/resource_templates/7",
    "o:id": 7
  },

  ...

  "o:primary_media": {
    "@id": "https://my.web.site/api/media/3918",
    "o:id": 3918
  },
  "o:media": [{
    "@id": "https://my.web.site/api/media/3918",
    "o:id": 3918
  }, {
    "@id": "https://my.web.site/api/media/3946",
    "o:id": 3946
  }],
  "o:item_set": [{
    "@id": "https://my.web.site/api/item_sets/917",
    "o:id": 917
  }],
  "o:site": [{
    "@id": "https://my.web.site/api/sites/1",
    "o:id": 1
  }],

  ...

}
```
If you add the `nest` parameter with a list of keys, like:
```
https://my.web.site/api/items/1234?nest=o:owner,o:resource_class,o:resource_template,o:primary_media
```
the corresponding linked data will be nested in the response tree:
```
{
  "@context": "https://my.web.site/api-context",
  "@id": "https://my.web.site/api/items/1234",
  "@type": ["o:Item", "bibo:Document"],
  "o:id": 1234,
  "o:is_public": true,
  "o:owner": {
    "o:id": 7,
    "o:name": "ABC",
    "o:email": "abc@my.web.site",
    "o:created": "2026-01-01T12:34:56+00:00",
    "o:role": "global_admin",
    "o:is_active": true
  },
  "o:resource_class": {
    "o:id": 49,
    "o:local_name": "Document",
    "o:label": "Document",
    "o:comment": "A document (noun) is a bounded physical representation of body of information designed with the capacity (and usually intent) to communicate. A document may manifest symbolic, diagrammatic or sensory-representational information.",
    "o:term": "bibo:Document",
    "o:vocabulary": {
      "@id": "https://my.web.site/api/vocabularies/3",
      "o:id": 3
    }
  },
  "o:resource_template": {
    "o:id": 7,
    "o:label": "Awesome documents",
    "o:owner": {
      "@id": "https://my.web.site/api/users/7",
      "o:id": 7
    },
    "o:resource_class": {
      "@id": "https://my.web.site/api/resource_classes/49",
      "o:id": 49
    },
    "o:title_property": null,
    "o:description_property": null,
    "o:resource_template_property": [{
      "o:property": {
        "@id": "https://my.web.site/api/properties/1",
        "o:id": 1
      },
      "o:alternate_label": null,
      "o:alternate_comment": null,
      "o:data_type": [],
      "o:is_required": false,
      "o:is_private": false,
      "o:default_lang": null
    }, {

    ...

    }]
  },

  ...

  "o:primary_media": {
    "o:id": 3918,
    "o:is_public": true,
    "o:owner": {
      "@id": "https://my.web.site/api/users/7",
      "o:id": 7
    },
    "o:resource_class": null,
    "o:resource_template": null,
    "o:thumbnail": null,
    "o:title": "Awsome media",
    "thumbnail_display_urls": {
      "large": "https://my.web.site/files/large/123e455130746329203938a15a76888203c55395.jpg",
      "medium": "https://my.web.site/files/medium/123e455130746329203938a15a76888203c55395.jpg",
      "square": "https://my.web.site/files/square/123e455130746329203938a15a76888203c55395.jpg"
    },
    "o:created": {
      "@value": "2026-01-01T12:34:56+00:00",
      "@type": "http://www.w3.org/2001/XMLSchema#dateTime"
    },
    "o:modified": {
      "@value": "2026-01-01T12:34:56+00:00",
      "@type": "http://www.w3.org/2001/XMLSchema#dateTime"
    },
    "o:ingester": "url",
    "o:renderer": "file",
    "o:item": {
      "@id": "https://my.web.site/api/items/3889",
      "o:id": 3889
    },
    "o:source": "https://another.web.site/with-an-awsome-picture.jpg",
    "o:media_type": "image/jpeg",
    "o:sha256": "6a71ebc0aea76797e3f86fee8f7a91b64a8918fb7b1684658fd23d50897678d9",
    "o:size": 94312,
    "o:filename": "123e455130746329203938a15a76888203c55395.jpg",
    "o:lang": null,
    "o:alt_text": null,
    "o:original_url": "https://my.web.site/files/original/123e455130746329203938a15a76888203c55395.jpg",
    "o:thumbnail_urls": {
      "large": "https://my.web.site/files/large/123e455130746329203938a15a76888203c55395.jpg",
      "medium": "https://my.web.site/files/medium/123e455130746329203938a15a76888203c55395.jpg",
      "square": "https://my.web.site/files/square/123e455130746329203938a15a76888203c55395.jpg"
    },
    "data": {
      "dimensions": {
        "original": {
          "width": 1720,
          "height": 2473
        },
        "large": {
          "width": 890,
          "height": 1280
        },
        "medium": {
          "width": 139,
          "height": 200
        },
        "square": {
          "width": 200,
          "height": 200
        }
      }
    },
    "dcterms:title": [{
      "type": "literal",
      "property_id": 1,
      "property_label": "Title",
      "is_public": true,
      "@value": "Awsome media"
    }]
  },

  ...

  "o:apinest": {
    "version": "0.1.3"
  }
}
```

If the **Default merge key** is set to `apinest` or if you add the parameter `merge=0` to your request, the linked data will be added to the `o:apinest` key in the response tree:
```
{
  "@context": "https://my.web.site/api-context",
  "@id": "https://my.web.site/api/items/1234",
  "@type": ["o:Item", "bibo:Document"],
  "o:id": 1234,
  "o:is_public": true,
  "o:owner": {
    "o:id": 7,
    "o:name": "ABC",
    "o:email": "abc@my.web.site",
    "o:created": "2026-01-01T12:34:56+00:00",
    "o:role": "global_admin",
    "o:is_active": true
  },

  ...

  "o:apinest": {
    "version": "0.1.3",
    "o:primary_media": {
      "o:id": 3918,

      ...

      }
    }]
  }
}
```

## Resources

Inspired by the [JSON:API specification](https://jsonapi.org/), expected to conform to the [JSON-LD specification](https://json-ld.org/).

## Copyright

Copyright 2026 Christian Morel

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
