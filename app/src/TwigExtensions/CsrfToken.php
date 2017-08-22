<?php

namespace App\TwigExtensions;

class CsrfToken extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /**
     * @var \Slim\Csrf\Guard
     */
    private $csrf;

    public function __construct(\Slim\Csrf\Guard $csrf)
    {
        $this->csrf = $csrf;
    }

    /**
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'csrf',
                [$this, 'getHtml'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function getHtml()
    {
        $token = $this->getGlobals()['csrf_token'];

        return <<<EOT
<input type="hidden" name="{$token['keys']['name']}" value="{$token['name']}">
<input type="hidden" name="{$token['keys']['value']}" value="{$token['value']}">
EOT;
    }

    public function getGlobals()
    {
        // CSRF token name and value
        $csrfNameKey = $this->csrf->getTokenNameKey();
        $csrfValueKey = $this->csrf->getTokenValueKey();
        $csrfName = $this->csrf->getTokenName();
        $csrfValue = $this->csrf->getTokenValue();

        return [
            'csrf'   => [
                'keys' => [
                    'name'  => $csrfNameKey,
                    'value' => $csrfValueKey
                ],
                'name'  => $csrfName,
                'value' => $csrfValue
            ]
        ];
    }

    public function getName()
    {
        return 'slim/csrf';
    }
}
