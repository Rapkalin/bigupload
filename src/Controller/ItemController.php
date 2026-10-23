<?php

namespace App\Controller;

use App\Exception\UploadException;
use App\Services\ChunkUploadService;
use App\Services\ItemService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class ItemController extends BaseController
{
    // Max execution time of one chunk request (upload of the chunk + zip of the file on the last one)
    private const int CHUNK_TIME_LIMIT = 5 * 60;
    private const string STATUS_SUCCESS = 'success';
    private const string STATUS_ERROR = 'error';

    public function __construct(
        private readonly ChunkUploadService $chunkUploadService,
        private readonly ItemService $itemService,
        #[Target('upload.limiter')] private readonly RateLimiterFactoryInterface $uploadLimiter,
        #[Target('upload')] private readonly LoggerInterface $uploadLogger,
    ) {
    }

    /**
     * Receives the chunks sent by PLupload (upload.js). The last chunk creates the item and returns its download url.
     */
    #[Route('/handleFile', name: 'file.handle', methods: ['POST'])]
    public function handleFile(Request $request): JsonResponse
    {
        // basename() prevents writing outside the upload directory (e.g. "../../file.jpg")
        $fileName = basename($request->query->getString('name'));
        $chunk = $request->query->getInt('chunk');

        try {
            if (0 === $chunk && !$this->uploadLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                throw new UploadException('Too many uploads, please try again later.', Response::HTTP_TOO_MANY_REQUESTS);
            }

            set_time_limit(self::CHUNK_TIME_LIMIT);
            $receivedChunk = $this->chunkUploadService->receive(
                $fileName,
                $chunk,
                $request->query->getInt('chunks'),
                $request->query->get('upload_id'),
                $request->getContent(true),
            );

            if (!$receivedChunk->isComplete) {
                return $this->uploadResponse("$fileName chunk successfully uploaded.", Response::HTTP_OK, [
                    'filename' => $fileName,
                    'upload_id' => $receivedChunk->uploadId,
                ]);
            }

            $item = $this->itemService->createFromUpload($receivedChunk->partPath, $fileName);

            return $this->uploadResponse("$fileName has been created.", Response::HTTP_CREATED, [
                'filename' => $fileName,
                'download_url' => $item->getDownloadPageUrl(),
            ]);
        } catch (UploadException $e) {
            $this->uploadLogger->warning('Upload refused', [
                'file' => $fileName,
                'reason' => $e->getMessage(),
                'code' => $e->getCode(),
                'ip' => $request->getClientIp(),
            ]);

            return $this->uploadResponse($e->getMessage(), $e->getCode(), status: self::STATUS_ERROR);
        } catch (\Throwable $e) {
            $this->uploadLogger->error('Upload failed', [
                'file' => $fileName,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return $this->uploadResponse("$fileName was not uploaded.", Response::HTTP_INTERNAL_SERVER_ERROR, status: self::STATUS_ERROR);
        }
    }

    private function uploadResponse(string $message, int $httpStatusCode, array $extraData = [], string $status = self::STATUS_SUCCESS): JsonResponse
    {
        $data = ['status' => $status, 'message' => $message];
        if ($extraData) {
            $data['extraData'] = $extraData;
        }

        $response = new JsonResponse($data, $httpStatusCode);
        // Make sure the response is not cached (as it happens for example on iOS devices)
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
