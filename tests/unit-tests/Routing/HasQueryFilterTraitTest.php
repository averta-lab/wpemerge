<?php

namespace WPEmergeTests\Routing;

use Mockery;
use WPEmerge;
use WPEmerge\Requests\RequestInterface;
use WPEmerge\Routing\Conditions\UrlCondition;
use WPEmerge\Routing\HasQueryFilterTrait;
use WPEmerge\Routing\Route;
use WP_UnitTestCase;

/**
 * @coversDefaultClass \WPEmerge\Routing\HasQueryFilterTrait
 */
class HasQueryFilterTraitTest extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->subject = Mockery::mock( HasQueryFilterTraitImplementation::class )->makePartial();
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();

		unset( $this->subject );
	}

	/**
	 * @covers ::applyQueryFilter
	 */
	public function testApplyQueryFilter_NoFilter_False() {
		$request = Mockery::mock( RequestInterface::class )->shouldIgnoreMissing();

		$this->assertEquals( false, $this->subject->applyQueryFilter( $request, [] ) );
	}

	/**
	 * @covers ::applyQueryFilter
	 * @expectedException \WPEmerge\Exceptions\ConfigurationException
	 * @expectedExceptionMessage Only routes with URL condition can use queries
	 */
	public function testApplyQueryFilter_NonUrlCondition_Exception() {
		$request = Mockery::mock( RequestInterface::class )->shouldIgnoreMissing();

		$this->subject->query( function() {} );
		$this->subject->applyQueryFilter( $request, [] );
	}

	/**
	 * @covers ::applyQueryFilter
	 */
	public function testApplyQueryFilter_UnsatisfiedUrlCondition_False() {
		$request = Mockery::mock( RequestInterface::class )->shouldIgnoreMissing();
		$condition = Mockery::mock( UrlCondition::class );

		$this->subject->shouldReceive( 'getCondition' )
			->andReturn( $condition );

		$condition->shouldReceive( 'isSatisfied' )
			->andReturn( false );

		$this->subject->query( function() {} );
		$this->assertEquals( false, $this->subject->applyQueryFilter( $request, [] ) );
	}

	/**
	 * @covers ::applyQueryFilter
	 */
	public function testApplyQueryFilter_SatisfiedUrlCondition_FilteredArray() {
		$arguments = ['arg1', 'arg2'];
		$request = Mockery::mock( RequestInterface::class )->shouldIgnoreMissing();
		$condition = Mockery::mock( UrlCondition::class );
		$subject = new Route( [], $condition, function() {} );
		$subject->query( function( $query_vars, $arg1, $arg2 ) {
			return array_merge( $query_vars, [$arg1, $arg2] );
		} );

		$condition->shouldReceive( 'isSatisfied' )
			  ->andReturn( true );

		$condition->shouldReceive( 'getArguments' )
			  ->andReturn( $arguments );

		$this->assertEquals( ['arg0', 'arg1', 'arg2'], $subject->applyQueryFilter( $request, ['arg0'] ) );
	}
}

class HasQueryFilterTraitImplementation {
	use HasQueryFilterTrait;
}
