<?php


namespace LaravelMsGraphMail\Exceptions;


use Exception;

class CouldNotSendMail extends Exception {

    public static function invalidConfig(): CouldNotSendMail {
        return new static('The mail.php configuration is missing from address, transport, client and/or secret key configuration');
    }

    public static function serviceRespondedWithError(?string $code, ?string $message): CouldNotSendMail {
        return new static('Microsoft Graph API responded with code ' . $code . ': ' . $message);
    }

}
