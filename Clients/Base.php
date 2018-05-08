<?php
namespace Practo\CommunicatorBundle\Clients;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class Base
{
    protected $_domain;
    protected $request;

    public function setRequest(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
        if ($this->request instanceof Request) {
            $this->_domain = $this->request->getSchemeAndHttpHost();
        }
    }

    public function setDomain($domain=null)
    {
        $this->_domain = $domain;
    }

    public function getCommunicatorUri() {
        $domain = $this->_domain;
        $parts = parse_url($domain);
        $host_parts = explode('.', $parts['host']);
        $subdomain = $host_parts[0];
        $subdomain = explode('-', $subdomain);
        $uri = $parts['scheme'] . '://communicator';
        if (isset($subdomain[1])) {
            $uri .= '-' . $subdomain[1];
        }

        $uri .= '.' . $host_parts[1] . '.' . $host_parts[2];
        return $uri;
    }
}
