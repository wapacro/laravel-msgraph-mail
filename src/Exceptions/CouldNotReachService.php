<?php


namespace LaravelMsGraphMail\Exceptions;


use Exception;

class CouldNotReachService extends Exception {

    public static function networkError(): CouldNotReachService {
        return new static('The server couldn\'t be reached');
    }

    public static function unknownError(): CouldNotReachService {
        return new static('An unknown error occured');
    }

}
