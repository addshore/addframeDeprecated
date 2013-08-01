<?php

namespace Addframe\Tests;

use Addframe\Family;

/**
 *
 * @since 0.0.2
 *
 * @author Addshore
 */

class FamilyTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideValidConstructionValues
	 */
	function testCanConstructFamily( $values ){
		new Family( $values[0], $values[1] );
		$this->assertTrue( true, 'Unable to construct a Family object' );
	}

	function provideValidConstructionValues(){
		return array(
			array( array( null, null ) ),
			array( array( $this->getMockUserLogin(), null) ),
			//@todo test with url
		);
	}

	function getMockUserLogin(){
		return $this->getMockBuilder( 'Addframe\UserLogin' )
			->disableOriginalConstructor()->getMock()
			->expects( $this->any() )
			->method( 'getPassword' )
			->will( $this->returnValue( 'password' ) );
	}

}