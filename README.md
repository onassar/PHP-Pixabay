# PHP-Pixabay

Simple PHP wrapper for Pixabay's API, using `file_get_contents` and streams

### Sample Call

``` php
<?php
    require_once '/path/to/Pixabay.class.php';
    $username  = '*****';
    $key = '*****';
    $pixabay = (new Pixabay($username, $key));
    $pixabay->setMinWidth(1600);
    $pixabay->setPhotosPerPage(100);
    $pixabay->setHD(true);
    $photos = $pixabay->query('elephants');
    print_r($photos);
    exit(0);

```
