<?php
namespace Practo\CommunicatorBundle\Clients;

use Symfony\Component\HttpFoundation\Request;

class Base
{
	private $_domain;

	public function setDomain($domain=null) {
		$this->_domain = $domain;
		if (!$this->_domain && $this->request instanceof Request) {
            $this->_domain = $this->request->getSchemeAndHttpHost();
        }
	}

	public function getCommunicatorUri() {
		$domain = $this->_domain
		if (!$domain && $this->request instanceof Request) {
			$domain = $this->request->getSchemeAndHttpHost();
		}
		$parts = parse_url($domain);
		$host_parts = explode('.', $parts['host']);
		$subdomain = $host_parts[0];
		$subdomain = explode('-', $subdomain);
		$uri = 'https://communicator-' . $subdomain[1] . $host_parts[1] . $host_parts[2];
		return $uri;
	}
}

?>