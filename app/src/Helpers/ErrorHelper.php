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
     * @param string $debugMessage
     * @param string $message
     * @param array $errorInfo
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
     * @return array
     */
    public function getErrorMessage(): array
    {
        return $this->err;
    }
}