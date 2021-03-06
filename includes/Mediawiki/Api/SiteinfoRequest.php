<?php

namespace Addframe\Mediawiki\Api;

/**
 * Class SiteinfoRequest meta=siteinfo
 */
class SiteinfoRequest extends QueryRequest{

	public function __construct( $params = array (), $shouldPost = false ) {

		$this->addAllowedParams( array( 'meta', 'siprop', 'sifilteriw', 'sishowalldb', 'sinumberingroup', 'siinlanguagecode' ) );
		$this->addParams( array( 'meta' => 'siteinfo' ) );

		parent::__construct( $params, $shouldPost );
	}
}