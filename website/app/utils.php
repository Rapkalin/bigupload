<?php

$url = $_GET['longUrl'];

$api_url = 'https://tinyurl.com/api-create.php?url=' . $url;


try {
    $curl = curl_init();
    $timeout = 10;

    // Check if initialization had gone wrong*
    if ($curl === false) {
        throw new Exception('failed to initialize');
    }

    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_URL, $api_url);

    $new_url = curl_exec($curl);

    // Check the return value of curl_exec(), too
    if ($new_url === false) {
        throw new Exception(curl_error($curl), curl_errno($curl));
    }

    // Check HTTP return code, too; might be something else than 200
    $httpReturnCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    /* Process $content here */
    curl_close($curl);
    echo json_encode($new_url);

} catch (\Exception $e) {
    var_dump('exeption error', $e);
}
