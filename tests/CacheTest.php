<?php

/**
 * This test will take 2+ seconds due to sleeps included
 * todo would be nice to split this test up a bit...
 */

use Addframe\Mediawiki\ApiRequest;
use Addframe\Cache;

/**
 * Class CacheTest
 * @covers Addframe\Cache
 */

class CacheTest extends PHPUnit_Framework_TestCase{

	function testCacheTrip(){
		// setup 2 requests
		$request1 = $this->getRandomRequest();
		$request2 = $this->getRandomRequest();

		// assert neither result is currently in the cache
		$this->assertFalse( Cache::has( $request1 ) );
		$this->assertFalse( Cache::has( $request2 ) );
		$this->assertNull( Cache::age( $request1 ) );
		$this->assertNull( Cache::age( $request2 ) );

		// assert the first cache is added correctly
		Cache::add( $request1 );
		$this->assertTrue( Cache::has( $request1 ) );
		$this->assertEquals( $request1->getResult(), Cache::get( $request1 ) );
		sleep(1); //sleep so the age has a decent value
		$this->assertGreaterThanOrEqual( 1, Cache::age( $request1 ) );

		// assert the second cache is added correctly (and the first is still there)
		Cache::add( $request2 );
		$this->assertTrue( Cache::has( $request1 ) );
		$this->assertEquals( $request1->getResult(), Cache::get( $request1 ) );
		$this->assertTrue( Cache::has( $request2 ) );
		$this->assertEquals( $request2->getResult(), Cache::get( $request2 ) );
		sleep(1); //sleep so the age has a decent value
		$this->assertGreaterThanOrEqual( 2, Cache::age( $request1 ) );
		$this->assertGreaterThanOrEqual( 1, Cache::age( $request2 ) );

		// remove the first result and make sure the second is still there
		Cache::remove( $request1 );
		$this->assertFalse( Cache::has( $request1 ) );
		$this->assertTrue( Cache::has( $request2 ) );
		$this->assertEquals( $request2->getResult(), Cache::get( $request2 ) );

		// clear the cache and assert neither result is there
		Cache::clear();
		$this->assertFalse( Cache::has( $request1 ) );
		$this->assertFalse( Cache::has( $request2 ) );

		//assert get returns null if the file doesn't exist..
		$this->assertNull( Cache::get( $request1 ) );
		$this->assertNull( Cache::get( $request2 ) );
	}

	function getRandomRequest(){
		$request = new ApiRequest( array( rand( 0, 99999999 ) ) );
		$request->setResult( array( 'Note' => 'This cached result was generated in a test' ) );
		return $request;
	}

}