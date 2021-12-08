<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Command\Handler;

use Alphagov\Notifications\Client;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use NotifyQueueConsumer\Command\Model\UpdateDocumentStatus;
use NotifyQueueConsumer\Queue\DuplicateMessageException;
use UnexpectedValueException;
use Alphagov\Notifications\Exception;

class SendToNotifyHandler
{
    private Filesystem $filesystem;
    private Client $notifyClient;

    const NOTIFY_TEMPLATE_DOWNLOAD_A6_INVOICE = '9286a7db-a316-4103-a1c7-7bc1fdbbaa81';
    const NOTIFY_TEMPLATE_DOWNLOAD_FF1_INVOICE = 'daef7d83-9874-4dd8-ac60-d92646e7aaaa';
    const NOTIFY_TEMPLATE_DOWNLOAD_BS1_LETTER = '3dc53e2c-7e90-4e5f-95ef-8e7a98a6ee55';
    const NOTIFY_TEMPLATE_DOWNLOAD_BS2_LETTER = '228746a3-a445-412d-995e-ae60af86b63d';
    const NOTIFY_TEMPLATE_DOWNLOAD_RD1_LETTER = 'ed08b8c0-dcd6-4cd4-9798-779189e0abe8';
    const NOTIFY_TEMPLATE_DOWNLOAD_RD2_LETTER = '7bc45244-1545-4978-9a01-926d1291b1df';
    const NOTIFY_TEMPLATE_DOWNLOAD_RI2_LETTER = 'd17bb689-52d1-4d73-a501-9955282cfe2e';
    const NOTIFY_TEMPLATE_DOWNLOAD_RI3_LETTER = '473de8af-c59f-4b7e-8e12-240450ec3fb4';
    const NOTIFY_TEMPLATE_DOWNLOAD_RR1_LETTER = 'f93687ad-d1e3-4577-83e9-1f5db0748d38';
    const NOTIFY_TEMPLATE_DOWNLOAD_RR2_LETTER = 'd43958ef-4a93-4cd8-abda-c4001785e740';
    const NOTIFY_TEMPLATE_DOWNLOAD_RR3_LETTER = '19610ca0-0225-423a-8f83-729be739be66';

    public function __construct(Filesystem $filesystem, Client $notifyClient)
    {
        $this->filesystem = $filesystem;
        $this->notifyClient = $notifyClient;
    }

    /**
     * @param SendToNotify $sendToNotifyCommand
     * @return UpdateDocumentStatus
     * @throws FileNotFoundException
     * @throws Exception\NotifyException|Exception\ApiException|Exception\UnexpectedValueException
     */
    public function handle(SendToNotify $sendToNotifyCommand): UpdateDocumentStatus
    {
        // 1. Check if message exists using our reference - Notify doesn't ignore duplicates!
        // https://docs.notifications.service.gov.uk/php.html#get-the-status-of-multiple-messages
        if ($this->isDuplicate($sendToNotifyCommand->getUuid())) {
            throw new DuplicateMessageException();
        }

        // 2. Fetch PDF for queued item
        $pdf = $sendToNotifyCommand->getFilename();
        $contents = $this->filesystem->read($pdf);

        if ($contents === false) {
            throw new UnexpectedValueException("Cannot read PDF");
        }

        // 3. Send to notify
        $sendBy = $sendToNotifyCommand->getSendBy();
        var_dump($sendBy);
        if ($sendBy['method'] === 'email' && ($sendBy['documentType'] === 'invoice' || $sendBy['documentType'] === 'letter')) {
            switch ($sendToNotifyCommand->getLetterType()) {
                case 'a6':
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_A6_INVOICE;
                    break;
                case 'ff1':
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_FF1_INVOICE;
                    break;
                case 'bs1':
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_BS1_LETTER;
                    break;
                case 'bs2':
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_BS2_LETTER;
                    break;
                case 'rd1':
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_RD1_LETTER;
                    break;
                case 'rd2':
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_RD2_LETTER;
                    break;
                case 'ri2':
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_RI2_LETTER;
                    break;
                case 'ri3':
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_RI3_LETTER;
                    break;
                case 'rr1':
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_RR1_LETTER;
                    break;
                case 'rr2':
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_RR2_LETTER;
                    break;
                case 'rr3':
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_RR3_LETTER;
                    break;
                default:
                    $letterTemplate = null;
                    break;
            }
            $response = $this->sendEmailToNotify(
                $sendToNotifyCommand->getUuid(),
                $sendToNotifyCommand->getRecipientName(),
                $sendToNotifyCommand->getRecipientEmail(),
                $sendToNotifyCommand->getClientFirstname(),
                $sendToNotifyCommand->getClientSurname(),
                $contents,
                $letterTemplate,
            );
        } else {
            $response = $this->sendLetterToNotify($sendToNotifyCommand->getUuid(), $contents);
        }

        list('id' => $notifyId, 'status' => $notifyStatus) = $response;

        return UpdateDocumentStatus::fromArray(
            [
                'notifyId' => $notifyId,
                'notifyStatus' => $notifyStatus,
                'documentId' => $sendToNotifyCommand->getDocumentId(),
            ]
        );
    }

    /**
     * @param string $reference
     * @param string $contents
     * @return array<string,string>
     * @throws Exception\NotifyException|Exception\ApiException|Exception\UnexpectedValueException
     */
    private function sendLetterToNotify(string $reference, string $contents): array
    {
        $sendResponse = $this->notifyClient->sendPrecompiledLetter($reference, $contents);

        if (empty($sendResponse['id'])) {
            throw new UnexpectedValueException("No Notify id returned");
        }

        $statusResponse = $this->notifyClient->getNotification($sendResponse['id']);

        if (empty($statusResponse['status'])) {
            throw new UnexpectedValueException(
                sprintf("No Notify status found for the ID: %s", $sendResponse['id'])
            );
        }

        return [
            'id' => $sendResponse['id'],
            'status' => $statusResponse['status']
        ];
    }

    /**
     * @param string $reference
     * @param string $recipientName
     * @param string $recipientEmail
     * @param string $contents
     *
     * @return array<string,string>
     * @throws Exception\NotifyException|Exception\ApiException|Exception\UnexpectedValueException
     */
    private function sendEmailToNotify(
        string $reference,
        string $recipientName,
        string $recipientEmail,
        string $clientFirstName,
        string $clientSurname,
        string $contents,
        ?string $letterTemplate
    ): array {
        $data = [
            'recipient_name' => $recipientName,
            'client_first_name' => $clientFirstName,
            'client_surname' => $clientSurname,
            'link_to_file' => $this->notifyClient->prepareUpload($contents)
        ];

        $sendResponse = $this->notifyClient->sendEmail(
            $recipientEmail,
            $letterTemplate,
            $data,
            $reference
        );

        if (empty($sendResponse['id'])) {
            throw new UnexpectedValueException("No Notify id returned");
        }

        $statusResponse = $this->notifyClient->getNotification($sendResponse['id']);

        if (empty($statusResponse['status'])) {
            throw new UnexpectedValueException(
                sprintf("No Notify status found for the ID: %s", $sendResponse['id'])
            );
        }

        return [
            'id' => $sendResponse['id'],
            'status' => $statusResponse['status']
        ];
    }

    /**
     * @param string $reference
     * @return bool
     */
    private function isDuplicate(string $reference): bool
    {
        $response = $this->notifyClient->listNotifications(['reference' => $reference]);

        // NOTE we are sending one letter at a time and expecting one letter per reference
        if (!empty($response['notifications'][0]['id'])) {
            return true;
        }

        return false;
    }
}
