<?php

namespace Addframe\Test\Unit;

use Addframe\Mediawiki\Api\EditRequest;

/**
 * Class EditRequestTest
 * @covers Addframe\Mediawiki\Api\EditRequest
 */
class EditRequestTest extends MediawikiTestCase {

	public function testCanConstruct( ){
		$class = '\Addframe\Mediawiki\Api\EditRequest';
		$object = new $class();
		$this->assertInstanceOf( $class, $object );

	}

	public function testEditRequest(){
		$query = new EditRequest();
		$params = $query->getParameters();
		$this->assertArrayHasKey( 'action', $params );
		$this->assertEquals( 'edit', $params['action'] );

		$query = new EditRequest( array ( 'text' => 'FooBar' ) );
		$params = $query->getParameters();
		$this->assertArrayHasKey( 'action', $params );
		$this->assertEquals( 'edit', $params['action'] );
		$this->assertArrayHasKey( 'text', $params );
		$this->assertEquals( 'FooBar', $params['text'] );
		$this->assertArrayHasKey( 'md5', $params );
		$this->assertEquals( md5( 'FooBar' ), $params['md5'] );

		$query = new EditRequest( array ( 'prependtext' => 'AtTheStart', 'appendtext' => 'AtTheEnd' ) );
		$params = $query->getParameters();
		$this->assertArrayHasKey( 'action', $params );
		$this->assertEquals( 'edit', $params['action'] );
		$this->assertArrayHasKey( 'prependtext', $params );
		$this->assertEquals( 'AtTheStart', $params['prependtext'] );
		$this->assertArrayHasKey( 'appendtext', $params );
		$this->assertEquals( 'AtTheEnd', $params['appendtext'] );
		$this->assertArrayHasKey( 'md5', $params );
		$this->assertEquals( md5( 'AtTheStart' . 'AtTheEnd' ), $params['md5'] );
	}

}