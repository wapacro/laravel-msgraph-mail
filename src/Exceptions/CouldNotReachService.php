<?php


namespace LaravelMsGraphMail\Exceptions;


use Exception;

class CouldNotReachService extends Exception {

    public static function networkError() {
        return new static('The server couldn\'t be reached');
    }

    public static function unknownError() {
        return new static('An unknown error occured');
    }

}
