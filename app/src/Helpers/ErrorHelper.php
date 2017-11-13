<?php

namespace App\Helpers;

/**
 * Class ErrorHelper
 * @package App\Helpers
 */
class ErrorHelper
{
    /**
     * @var array $err Contains useful information for the user when an error appears.
     */
    private $err = [
        'message' => '',
        'debugMessage' => '',
        'errorInfo' => ''
    ];

    /**
     * Set an error message for the user, even for debugging purposes.
     *
     * @param string $debugMessage The message to display to the admin or
     *        to the developer. It contains also the function that caused the
     *        error.
     * @param string $message The message to display to the user.
     * @param array $errorInfo Return the internal $errorInfo[2].
     */
    public function setErrorMessage($debugMessage = 'Errore nell\'elaborazione dei dati.',
                                    $message = '',
                                    array $errorInfo = ['', '', ''])
    {
        $this->err['message'] = $message;
        $this->err['debugMessage'] = debug_backtrace()[1]['function'] . ': ' . $debugMessage;
        $this->err['errorInfo'] = $errorInfo[2];
    }

    /**
     * Return the internal error array.
     *
     * @return array
     */
    public function getErrorMessage(): array
    {
        return $this->err;
    }
}