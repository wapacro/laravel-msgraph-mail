<?php


namespace LaravelMsGraphMail;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Mail\Transport\Transport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use LaravelMsGraphMail\Exceptions\CouldNotGetToken;
use LaravelMsGraphMail\Exceptions\CouldNotReachService;
use LaravelMsGraphMail\Exceptions\CouldNotSendMail;
use Swift_Attachment;
use Swift_Mime_SimpleMessage;

class MsGraphMailTransport extends Transport {

    /**
     * @var string
     */
    protected string $tokenEndpoint = 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token';

    /**
     * @var string
     */
    protected string $apiEndpoint = 'https://graph.microsoft.com/v1.0/users/{from}/sendMail';

    /**
     * @var array
     */
    protected array $config;

    /**
     * @var Client|ClientInterface
     */
    protected ClientInterface $http;

    /**
     * MsGraphMailTransport constructor
     * @param array $config
     * @param ClientInterface|null $client
     */
    public function __construct(array $config, ClientInterface $client = null) {
        $this->config = $config;
        $this->http = $client ?? new Client();
    }

    /**
     * Send given email message
     * @param Swift_Mime_SimpleMessage $message
     * @param null $failedRecipients
     * @return int
     * @throws CouldNotSendMail
     * @throws CouldNotReachService
     * @throws CouldNotGetToken
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null) {
        $this->beforeSendPerformed($message);
        $payload = $this->getPayload($message);
        $url = str_replace('{from}', urlencode($payload['from']['emailAddress']['address']), $this->apiEndpoint);

        try {
            $this->http->post($url, [
                'headers' => $this->getHeaders(),
                'json' => [
                    'message' => $payload,
                ],
            ]);

            $this->sendPerformed($message);
            return $this->numberOfRecipients($message);
        } catch (BadResponseException $e) {
            // The API responded with 4XX or 5XX error
            $response = json_decode((string)$e->getResponse()->getBody());
            throw CouldNotSendMail::serviceRespondedWithError($response->error->code, $response->error->message);
        } catch (ConnectException $e) {
            // A connection error (DNS, timeout, ...) occurred
            throw CouldNotReachService::networkError();
        }
    }

    /**
     * Transforms given SwiftMailer message instance into
     * Microsoft Graph message object
     * @param Swift_Mime_SimpleMessage $message
     * @return array
     */
    protected function getPayload(Swift_Mime_SimpleMessage $message) {
        $from = $message->getFrom();
        $priority = $message->getPriority();
        $attachments = $message->getChildren();

        return array_filter([
            'subject' => $message->getSubject(),
            'sender' => $this->toRecipientCollection($from)[0],
            'from' => $this->toRecipientCollection($from)[0],
            'replyTo' => $this->toRecipientCollection($message->getReplyTo()),
            'toRecipients' => $this->toRecipientCollection($message->getTo()),
            'ccRecipients' => $this->toRecipientCollection($message->getCc()),
            'bccRecipients' => $this->toRecipientCollection($message->getBcc()),
            'importance' => $priority === 3 ? 'Normal' : ($priority < 3 ? 'Low' : 'High'),
            'body' => [
                'contentType' => Str::contains($message->getContentType(), 'html') ? 'html' : 'text',
                'content' => $message->getBody(),
            ],
            'attachments' => $this->toAttachmentCollection($attachments),
        ]);
    }

    /**
     * Transforms given SimpleMessage recipients into
     * Microsoft Graph recipients collection
     * @param array|string $recipients
     * @return array
     */
    protected function toRecipientCollection($recipients) {
        $collection = [];

        // If the provided list is empty
        // return an empty collection
        if (!$recipients) {
            return $collection;
        }

        // Some fields yield single e-mail
        // addresses instead of arrays
        if (is_string($recipients)) {
            $collection[] = [
                'emailAddress' => [
                    'name' => null,
                    'address' => $recipients,
                ],
            ];

            return $collection;
        }

        foreach ($recipients as $address => $name) {
            $collection[] = [
                'emailAddress' => [
                    'name' => $name,
                    'address' => $address,
                ],
            ];
        }

        return $collection;
    }

    /**
     * Transforms given SwiftMailer children into
     * Microsoft Graph attachment collection
     * @param $attachments
     * @return array
     */
    protected function toAttachmentCollection($attachments) {
        $collection = [];

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof Swift_Attachment) {
                continue;
            }

            $collection[] = [
                'name' => $attachment->getFilename(),
                'contentType' => $attachment->getContentType(),
                'contentBytes' => base64_encode($attachment->getBody()),
                'size' => strlen($attachment->getBody()),
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'isInline' => false,
            ];

        }

        return $collection;
    }

    /**
     * Returns header collection for API request
     * @return string[]
     * @throws CouldNotGetToken
     * @throws CouldNotReachService
     */
    protected function getHeaders() {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ];
    }

    /**
     * Returns API access token
     * @return string
     * @throws CouldNotReachService
     * @throws CouldNotGetToken
     */
    protected function getAccessToken() {
        try {
            return Cache::remember('mail-msgraph-accesstoken', 45, function () {
                $url = str_replace('{tenant}', $this->config['tenant'] ?? 'common', $this->tokenEndpoint);
                $response = $this->http->post($url, [
                    'form_params' => [
                        'client_id' => $this->config['client'],
                        'client_secret' => $this->config['secret'],
                        'scope' => 'https://graph.microsoft.com/.default',
                        'grant_type' => 'client_credentials',
                    ],
                ]);

                $response = json_decode((string)$response->getBody());
                return $response->access_token;
            });
        } catch (BadResponseException $e) {
            // The endpoint responded with 4XX or 5XX error
            $response = json_decode((string)$e->getResponse()->getBody());
            throw CouldNotGetToken::serviceRespondedWithError($response->error, $response->error_description);
        } catch (ConnectException $e) {
            // A connection error (DNS, timeout, ...) occurred
            throw CouldNotReachService::networkError();
        } catch (Exception $e) {
            // An unknown error occurred
            throw CouldNotReachService::unknownError();
        }
    }

}
