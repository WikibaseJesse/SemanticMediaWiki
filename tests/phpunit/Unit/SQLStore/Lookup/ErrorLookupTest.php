<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\ErrorLookup;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\Lookup\ErrorLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class ErrorLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $connection;
	private $iteratorFactory;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->iteratorFactory = $this->getMockBuilder( '\SMW\IteratorFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ErrorLookup::class,
			new ErrorLookup( $this->store )
		);
	}

	public function testFindErrorsByType() {

		$idTable = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( [ 'getConnection', 'getPropertyTables', 'findDiTypeTableId', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$store->expects( $this->any() )
			->method( 'findDiTypeTableId' )
			->will( $this->onConsecutiveCalls( '_foo', '_bar' ) );

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$this->connection->expects( $this->any() )
			->method( 'tableName' )
			->will( $this->returnArgument( 0 ) );

		$query = new \SMW\MediaWiki\Connection\Query( $this->connection );

		$resultWrapper = $this->getMockBuilder( '\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$instance = new ErrorLookup(
			$store
		);

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = $this->getMockBuilder( '\SMWDIBlob' )
			->disableOriginalConstructor()
			->getMock();

		$instance->findErrorsByType( 'foo' );

		$this->assertEquals(
			'SELECT t2.s_id AS s_id, t3.o_hash AS o_hash, t3.o_blob AS o_blob ' .
			'FROM smw_object_ids AS t0 ' .
			'INNER JOIN _foo AS t1 ON t0.smw_id=t1.s_id ' .
			'INNER JOIN smw_di_blob AS t2 ON t1.o_id=t2.s_id ' .
			'INNER JOIN smw_di_blob AS t3 ON t3.s_id=t2.s_id ' .
			'WHERE (t0.smw_iw!=:smw) AND (t0.smw_iw!=:smw-delete) AND ' .
			'(t1.p_id=) AND (t2.p_id=) AND (t2.o_hash=foo) AND (t3.p_id=)',
			$query->build()
		);
	}

}