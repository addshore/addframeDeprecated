<?php

use Addframe\Mediawiki\ApiRequest;

/**
 * Class ApiRequestTest
 * @covers Addframe\Mediawiki\ApiRequest
 */

class ApiRequestTest extends PHPUnit_Framework_TestCase{

	/**
	 * @dataProvider provideConstructionData
	 */
	function testCanConstruct( $params = array(), $shouldBePosted = false, $cache = false ){
		$request = new ApiRequest( $params, $shouldBePosted , $cache );
		$this->assertInstanceOf( 'Addframe\Mediawiki\ApiRequest', $request );

		//force our expected format param..
		if( !array_key_exists( 'format', $params ) ){
			$params['format'] = 'json';
		}

		//check the params
		$this->assertEquals( $params, $request->getParameters() );
		//check the defaults
		$this->assertEquals( $cache, $request->maxCacheAge() );
		$this->assertEquals( $shouldBePosted,  $request->shouldBePosted() );
		$this->assertEquals( null, $request->getResult() );
	}

	function provideConstructionData(){
		return array(
			//data, //post, //cacheable
			array( array() ),
			array( array( 'param' => 'provideConstructionData' ) ),
			array( array( 'param' => 'provideConstructionData' ), true ),
			array( array( 'param' => 'provideConstructionData' ), true ),
			array( array( 'param' => 'provideConstructionData', 'param2' => 'value2' ), false ),
		);
	}

	/**
	 * @dataProvider provideConstructionWithParamAsArray
	 */
	function testConstructionWithParamAsArray( $params, $expected ){
		$expected['format'] = 'json';
		$request = new ApiRequest( $params );
		$this->assertEquals( $expected, $request->getParameters() );

	}

	function provideConstructionWithParamAsArray(){
		return array(
			//params, expected
			array(
				array( 'param' => array( 'val1', 'val2' ) ),
				array( 'param' => 'val1|val2' ),
			),
			array(
				array( 'param' => array( 'val1', 'val2', 'val3', 'val4' ) ),
				array( 'param' => 'val1|val2|val3|val4' ),
			),
			array(
				array( 'param' => array( 'val1', 'val2' ), 'another' => array( 'aa1', 'aa2' ) ),
				array( 'param' => 'val1|val2', 'another' => 'aa1|aa2' ),
			),
		);
	}

	/**
	 * @dataProvider provideConstructionWithParamAsNull
	 */
	function testConstructionWithParamAsNull( $params, $expected ){
		$expected['format'] = 'json';
		$request = new ApiRequest( $params );
		$this->assertEquals( $expected, $request->getParameters() );

	}

	function provideConstructionWithParamAsNull(){
		return array(
			//params, expected
			array(
				array( 'param' => 'val', 'another' => null ),
				array( 'param' => 'val' ),
			),
			array(
				array( 'param' => null, 'another' => 'val' ),
				array( 'another' => 'val' ),
			),
		);
	}

	function testSetParameter(){
		$expected = array();
		$request = new ApiRequest( $expected, false , 0 );
		$expected = array_merge( $expected, array( 'format' => 'json' ) );
		$this->assertEquals( $expected, $request->getParameters() );

		$request->setParameter( 'token', '863bb60669575ac8619662ddad5fc2ac+\\' );
		$expected = array_merge( $expected, array( 'token' => '863bb60669575ac8619662ddad5fc2ac+\\' ) );
		$this->assertEquals( $expected, $request->getParameters() );
	}

	function testSetResult(){
		$expected = array( 'testSetResult' );
		$request = new ApiRequest();
		$request->setResult( $expected );
		$this->assertEquals( $expected, $request->getResult() );
	}

	function testGetCacheData(){
		$expected = array( 'testGetCacheData' );
		$request = new ApiRequest();
		$request->setResult( $expected );
		$this->assertEquals( $expected, $request->getCacheData() );
	}

	function testHash(){
		$request1 = new ApiRequest( array() );
		$request2 = new ApiRequest( array() );
		$this->assertEquals( $request1->getHash(), $request2->getHash() );
		$request1 = new ApiRequest( array( 'key' => 'value' ) );
		$request2 = new ApiRequest( array() );
		$this->assertNotEquals( $request1->getHash(), $request2->getHash() );
		$request1 = new ApiRequest( array( 'key' => 'SomeLongValues?afg?2rq' ) );
		$request2 = new ApiRequest( array() );
		$this->assertNotEquals( $request1->getHash(), $request2->getHash() );
	}

}