# PHP-Pixabay
PHP SDK for running queries against the millions of icons provided by
[Pixabay](https://pixabay.com). Includes recursive searches.

### Supports
- Searches

### Sample Search
``` php
$client = new onassar\Pixabay\Pixabay();
$client->setAPIKey('***');
$client->setLimit(10);
$client->setOffset(0);
$client->setHD(true);
$client->setMinWidth(1600);
$client->setMinHeight(400);
$results = $client->search('love') ?? array();
print_r($results);
exit(0);
```

### Note
Requires
[PHP-RemoteRequests](https://github.com/onassar/PHP-RemoteRequests).
