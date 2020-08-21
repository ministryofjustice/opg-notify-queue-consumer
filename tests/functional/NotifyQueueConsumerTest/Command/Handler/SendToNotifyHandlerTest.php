<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Functional\Command\Handler;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use NotifyQueueConsumer\Command\Handler\SendToNotifyHandler;
use NotifyQueueConsumer\Queue\DuplicateMessageException;
use PHPUnit\Framework\TestCase;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use Alphagov\Notifications\Client as NotifyClient;
use GuzzleHttp\Client as GuzzleClient;

class SendToNotifyHandlerTest extends TestCase
{
    private const TEST_FILE_PATH = __DIR__ . '/../../../../fixtures/sample_doc.pdf';
    private Filesystem $filesystem;

//    private SendToNotifyHandler $handler;

    public function setUp(): void
    {
        // These services are defined in src/bootstrap/services.php and are included in tests/bootstrap.php
        global $filesystem, $notifyClient, $awsS3Client, $config;

        parent::setUp();

        $this->filesystem = $filesystem;
//        $this->handler = new SendToNotifyHandler($this->filesystem, $notifyClient);

        if (!$awsS3Client->doesBucketExist($config['aws']['s3']['bucket'])) {
            $awsS3Client->createBucket([
                'Bucket' => $config['aws']['s3']['bucket'],
            ]);
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @throws FileNotFoundException
     */
    public function testHandleSuccess(): void
    {
        global $config, $notifyClient;

        $content = file_get_contents(self::TEST_FILE_PATH);
        $destination = basename(self::TEST_FILE_PATH);

        $this->filesystem->put($destination, $content);

        $command = SendToNotify::fromArray(
            [
                'id' => '123',
                'uuid' => 'test-handle-success-20200819175308' . rand(1000, 100000000000),
                'filename' => $destination,
                'documentId' => '1234',
            ]
        );
        $handler = new SendToNotifyHandler($this->filesystem, $notifyClient);
        $updateDocumentStatus = $handler->handle($command);

        self::assertNotEmpty($updateDocumentStatus->getNotifyId());
        self::assertNotEmpty($updateDocumentStatus->getNotifyStatus());
    }

//    /**
//     * @throws FileNotFoundException
//     */
//    public function testDuplicateUuidThrowsExceptionFailure(): void
//    {
//        self::expectException(DuplicateMessageException::class);
//
//        $content = file_get_contents(self::TEST_FILE_PATH);
//        $destination = basename(self::TEST_FILE_PATH);
//        $this->filesystem->put($destination, $content);
//
//        // Contains a uuid we sent previously
//        $command = SendToNotify::fromArray(
//            [
//                'id' => '123',
//                'uuid' => 'test-handle-success-20200818175307',
//                'filename' => $destination,
//                'documentId' => '1234',
//            ]
//        );
//
//        $this->handler->handle($command);
//    }
}
