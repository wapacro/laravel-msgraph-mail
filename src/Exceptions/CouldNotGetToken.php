<?php


namespace LaravelMsGraphMail\Exceptions;


use Exception;

class CouldNotGetToken extends Exception {

    public static function serviceRespondedWithError(string $code, string $message) {
        return new static('Microsoft Identity platform responded with code ' . $code . ': ' . $message);
    }

}
