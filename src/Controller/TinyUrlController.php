<?php

namespace App\Controller;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TinyUrlController extends BaseController
{
    /**
     * @throws Exception
     */
    #[Route('/shortenUrl', name: 'url.shorten')]
    public function getTinyUrl(Request $request): Response
    {
        $apiUrl = 'https://tinyurl.com/api-create.php?url=' . strip_tags($request->query->get('longUrl'));

        try {
            $curl = curl_init();
            $timeout = 10;

            // Check if initialization had gone wrong*
            if ($curl === false) {
                throw new Exception('failed to initialize');
            }

            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $apiUrl);

            $newUrl = curl_exec($curl);

            // Check the return value of curl_exec(), too
            if (!$newUrl) {
                throw new Exception(curl_error($curl), curl_errno($curl));
            }

            // Check HTTP return code, too; might be something else than 200
            // $httpReturnCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            /* Process $content here */
            curl_close($curl);
            return new Response(json_encode($newUrl));

        } catch (\Exception $e) {
            throw new Exception('something went wrong: ', $e);
        }
    }
}