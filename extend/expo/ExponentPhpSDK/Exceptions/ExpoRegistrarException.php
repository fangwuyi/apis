<?php

namespace ExponentPhpSDK\Exceptions;

class ExpoRegistrarException extends ExpoException
{
    /**
     * Invalid token exception
     *
     * @return static
     */
    public static function invalidToken()
    {
        return array('The token provided is not a valid expo push notification token.', 422);
    }

    /**
     * Register token exception
     *
     * @return static
     */
    public static function couldNotRegisterInterest()
    {
        return array('Could not register the token provided for the interest, due to internal error.', 500);
    }

    /**
     * Empty interests exception
     *
     * @return static
     */
    public static function emptyInterests()
    {
        return array('No interests found for this notification, make sure interests are already registered.', 404);
    }

    /**
     * Could not remove interest exception
     *
     * @return static
     */
    public static function couldNotRemoveInterest()
    {
        return array('Could not remove interest, due to internal error.', 500);
    }
}
