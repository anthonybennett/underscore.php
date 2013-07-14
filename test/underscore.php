<?php
/**
 * Underscore.js clone unit tests
 *
 * @author bennett.ureta@gmail.com
 */

require_once('../underscore.php');

class _Test extends PHPUnit_Framework_TestCase {
	/**
	 * Collections
	 */

	public function testEach() {
		// keep a reference to this
		$test = &$this;

		// test iterating over an Array
		$array = array(1, 2, 3);
		_::each($array, function($value, $key, &$obj) use($array, $test) {
			$test->assertSame($array, $obj);
			switch ($key) {
				case 0: $test->assertEquals(1, $value); break;
				case 1: $test->assertEquals(2, $value); break;
				case 2: $test->assertEquals(3, $value); break;
			}
		});

		// test iterating over a Traversable
		$arrayObj = new ArrayObject(array('a' => 4, 'b' => 5, 'c' => 6));
		_::each($arrayObj, function($value, $key, &$obj) use($arrayObj, $test) {
			$test->assertSame($arrayObj, $obj);
			switch ($key) {
				case 'a': $test->assertEquals(4, $value); break;
				case 'b': $test->assertEquals(5, $value); break;
				case 'c': $test->assertEquals(6, $value); break;
			}
		});

		// test iterating over a StdClass
		$object = new StdClass();
		$object->d = 7;
		$object->e = 8;
		$object->f = 9;
		_::each($object, function($value, $key, &$obj) use($object, $test) {
			$test->assertSame($object, $obj);
			switch ($key) {
				case 'd': $test->assertEquals(7, $value); break;
				case 'e': $test->assertEquals(8, $value); break;
				case 'f': $test->assertEquals(9, $value); break;
			}
		});

		// test breaking the loop
		_::each($array, function($value, $key, &$obj) use($array, $test) {
			$obj[$key] += 10;
			return _::getBreaker();
		});
		$test->assertEquals(11, $array[0]);
		$test->assertEquals(2, $array[1]);
		$test->assertEquals(3, $array[2]);

		// test passing in an invalid argument
		try {
			$string = 'array';
			_::each($string, function(){});
		} catch(Exception $e) {
			$test->assertInstanceOf('InvalidArgumentException', $e);
		}
	}

	public function testMap() {
		// test doubling values in an array
		$array1 = array(1, 2, 3);
		$array2 = _::map($array1, function($value) {
			return ($value * 2);
		});
		$this->assertEquals(2, $array2[0]);
		$this->assertEquals(4, $array2[1]);
		$this->assertEquals(6, $array2[2]);

		// test that we preserve keys
		$array1 = array('a' => 2, 'b' => 4, 'c' => 8);
		$array2 = _::map($array1, function($value) {
			return ($value * $value);
		});
		$this->assertEquals(4, $array2['a']);
		$this->assertEquals(16, $array2['b']);
		$this->assertEquals(64, $array2['c']);
	}

	public function testReduce() {
		// test adding up the values in an array with no memo
		$array = array(1, 2, 3);
		$sum = _::reduce($array, function($memo, $value) {
			return ($memo + $value);
		});
		$this->assertEquals(6, $sum);

		// test multiplying the values in an array with a memo
		$array = array(1, 2, 3);
		$multiple = _::reduce($array, function($memo, $value) {
			return ($memo * $value);
		}, 4);
		$this->assertEquals(24, $multiple);

		// test an empty array with no memo
		try {
			$array = array();
			$result = _::reduce($array, function() {
				return 1;
			});
		} catch (Exception $e) {
			$this->assertInstanceOf('InvalidArgumentException', $e);
		}
	}

	public function testReduceRight() {
		// test reversing the characters in a string
		$array = str_split('reverse');
		$string = _::reduceRight($array, function($memo, $value) {
			return "$memo$value";
		});
		$this->assertEquals('esrever', $string);
	}

	public function testFind() {
		// test being unable to find an element
		$array = array(1, 2, 3);
		$result = _::find($array, function($value) {
			return (4 == $value);
		});
		$this->assertEquals(null, $result);

		// test finding an element
		$result = _::find($array, function($value) {
			return (2 == $value);
		});
		$this->assertEquals(2, $result);

		// test finding the first element
		$result = _::find($array, function($value) {
			return ($value % 2);
		});
		$this->assertEquals(1, $result);
	}

	public function testFilter() {
		// test filtering down to an empty list
		$array = array(1, 2, 3);
		$result = _::filter($array, function($value) {
			return (0 == $value);
		});
		$this->assertInternalType('array', $result);
		$this->assertCount(0, $result);

		// test filtering down to odd elements
		$result = _::filter($array, function($value) {
			return ($value % 2);
		});
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(3, $result[2]);
	}

	public function testReject() {
		// test rejecting down to an empty list
		$array = array(1, 2, 3);
		$result = _::reject($array, function($value) {
			return ($value < 4);
		});
		$this->assertInternalType('array', $array);
		$this->assertCount(0, $result);

		// test rejecting down to odd elements
		$result = _::reject($array, function($value) {
			return (2 == $value);
		});
		$this->assertInternalType('array', $array);
		$this->assertCount(2, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(3, $result[2]);
	}

	public function testEvery() {
		// test no element in an array passes our filter
		$array = array(1, 2, 3);
		$result = _::every($array, function($value) {
			return (4 == $value);
		});
		$this->assertEquals(false, $result);

		// test one element in an array passes our filter
		$result = _::every($array, function($value) {
			return (2 == $value);
		});
		$this->assertEquals(false, $result);

		// test all elements in an array pass our filter
		$result = _::every($array, function($value) {
			return ($value < 4);
		});
		$this->assertEquals(true, $result);

		// test not passing in any filter
		$result = _::every($array);
		$this->assertEquals(true, $result);
	}

	public function testSome() {
		// test no element in an array passes our filter
		$array = array(1, 2, 3);
		$result = _::some($array, function($value) {
			return (4 == $value);
		});
		$this->assertEquals(false, $result);

		// test one element in an array passes our filter
		$result = _::some($array, function($value) {
			return (2 == $value);
		});
		$this->assertEquals(true, $result);

		// test all elements in an array pass our filter
		$result = _::some($array, function($value) {
			return ($value < 4);
		});
		$this->assertEquals(true, $result);

		// test not passing in any filter
		$result = _::some($array);
		$this->assertEquals(true, $result);
	}

	public function testContains() {
		// test that the element is not in the array
		$array = array(1, 2, 3);
		$result = _::contains($array, 4);
		$this->assertEquals(false, $result);

		// test that the element is in the array
		$result = _::contains($array, 2);
		$this->assertEquals(true, $result);
	}

	public function testInvoke() {
		// test invoking a Closure on an array
		$array = array(1, 2, 3);
		$result = _::invoke($array, function($value, $number) {
			return ($value + $number);
		}, 5);
		$this->assertInternalType('array', $result);
		$this->assertCount(3, $result);
		$this->assertEquals(6, $result[0]);
		$this->assertEquals(7, $result[1]);
		$this->assertEquals(8, $result[2]);

		// test invoking a method on an array of objects
		$one = new InvokeClass(4);
		$two = new InvokeClass(5);
		$three = new InvokeClass(6);
		$array = array($one, $two, $three);
		$result = _::invoke($array, 'callMe', 5);
		$this->assertInternalType('array', $result);
		$this->assertCount(3, $result);
		$this->assertEquals(9, $result[0]);
		$this->assertEquals(10, $result[1]);
		$this->assertEquals(11, $result[2]);
	}

	public function testPluck() {
		// test plucking from an array
		$array = array(
			array('a' => 5, 'b' => 'this'),
			array('a' => 4, 'b' => 'is'),
			array('a' => 3, 'b' => 'a'),
			array('a' => 2, 'b' => 'test'),
			array('a' => 1, 'b' => '!')
		);
		$result = _::pluck($array, 'b');
		$this->assertInternalType('array', $result);
		$this->assertCount(5, $result);
		$this->assertEquals('this', $result[0]);
		$this->assertEquals('is', $result[1]);
		$this->assertEquals('a', $result[2]);
		$this->assertEquals('test', $result[3]);
		$this->assertEquals('!', $result[4]);

		// test plucking from an object
		foreach ($array as $i => $item) {
			$array[$i] = (object)$item;
		}
		$result = _::pluck($array, 'a');
		$this->assertInternalType('array', $result);
		$this->assertCount(5, $result);
		$this->assertEquals(5, $result[0]);
		$this->assertEquals(4, $result[1]);
		$this->assertEquals(3, $result[2]);
		$this->assertEquals(2, $result[3]);
		$this->assertEquals(1, $result[4]);
	}

	public function testWhere() {
		// test not matching anything in an array
		$array = array(
			array('a' => 1, 'b' => 'testing'),
			array('a' => 2, 'b' => 'testing'),
			array('a' => 3, 'b' => 'one'),
			array('a' => 2, 'b' => 'two'),
			array('a' => 4, 'b' => 'three'),
		);
		$result = _::where($array);
		$this->assertInternalType('array', $result);
		$this->assertCount(0, $result);

		// test matching a single field in an array
		$result = _::where($array, array('b' => 'testing'));
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals(1, $result[0]['a']);
		$this->assertEquals('testing', $result[0]['b']);
		$this->assertEquals(2, $result[1]['a']);
		$this->assertEquals('testing', $result[1]['b']);

		// test finding the first element by a single field in an array
		$result = _::where($array, array('b' => 'testing'), true);
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals(1, $result['a']);
		$this->assertEquals('testing', $result['b']);

		// test matching objects in an array
		foreach ($array as $i => $item) {
			$array[$i] = (object)$item;
		}
		$result = _::where($array, array('a' => 2));
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals(2, $result[1]->a);
		$this->assertEquals('testing', $result[1]->b);
		$this->assertEquals(2, $result[3]->a);
		$this->assertEquals('two', $result[3]->b);
	}

	public function testToArray() {
		// test that an Array is idempotent
		$array1 = array(1, 2, 3);
		$array2 = _::toArray($array1);
		$this->assertSame($array1, $array2);

		// test that a Traversable is idempotent
		$arrayObj1 = new ArrayObject(array(1, 2, 3));
		$arrayObj2 = _::toArray($arrayObj1);
		$this->assertSame($arrayObj1, $arrayObj2);

		// test that a StdClass returns an array
		$object = new StdClass();
		$object->d = 7;
		$object->e = 8;
		$object->f = 9;
		$array = _::toArray($object);
		$this->assertInternalType('array', $array);
		$this->assertCount(3, $array);
		$this->assertEquals(7, $array['d']);
		$this->assertEquals(8, $array['e']);
		$this->assertEquals(9, $array['f']);

		// test that anything else returns an empty array
		$string = 'array';
		$array = _::toArray($string);
		$this->assertInternalType('array', $array);
		$this->assertCount(0, $array);
	}

	public function testFindWhere() {
		// test finding the first element by a single field in an array
		$array = array(
			array('a' => 1, 'b' => 'testing'),
			array('a' => 2, 'b' => 'testing'),
			array('a' => 3, 'b' => 'one'),
			array('a' => 2, 'b' => 'two'),
			array('a' => 4, 'b' => 'three'),
		);
		$result = _::findWhere($array, array('b' => 'testing'), true);
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals(1, $result['a']);
		$this->assertEquals('testing', $result['b']);
	}

	public function testMax() {
		// test an empty array
		$array = array();
		$result = _::max($array);
		$this->assertEquals(-INF, $result);

		// test an array with elements
		$array = array(1, 2, 3);
		$result = _::max($array);
		$this->assertEquals(3, $result);

		// test an empty countable
		$arrayObj = new ArrayObject();
		$result = _::max($arrayObj);
		$this->assertEquals(-INF, $result);

		// test a countable with elements
		$arrayObj = new ArrayObject(array(1, 2, 3));
		$result = _::max($arrayObj);
		$this->assertEquals(3, $result);

		// test an array with elements and an iterator
		$array = array('one', 'two', 'three', 'four');
		$result = _::max($array, function($value) {
			return strlen($value);
		});
		$this->assertEquals('three', $result);
	}

	public function testMin() {
		// test an empty array
		$array = array();
		$result = _::min($array);
		$this->assertEquals(INF, $result);

		// test an array with elements
		$array = array(1, 2, 3);
		$result = _::min($array);
		$this->assertEquals(1, $result);

		// test an empty countable
		$arrayObj = new ArrayObject();
		$result = _::min($arrayObj);
		$this->assertEquals(INF, $result);

		// test a countable with elements
		$arrayObj = new ArrayObject(array(1, 2, 3));
		$result = _::min($arrayObj);
		$this->assertEquals(1, $result);

		// test an array with elements and an iterator
		$array = array('one', 'two', 'three', 'four');
		$result = _::min($array, function($value) {
			return strlen($value);
		});
		$this->assertEquals('one', $result);
	}

	public function testShuffle() {
		$test = function($array, $runs = 100) {
			$shuffled = 0;
			foreach (range(0, $runs) as $run) {
				$result = _::shuffle($array);
				if (($array[0] != $result[0]) ||
					($array[1] != $result[1]) ||
					($array[2] != $result[2]) ||
					($array[3] != $result[3]) ||
					($array[4] != $result[4])) {
					++$shuffled;
				}
			}
			return ($shuffled / $runs);
		};

		// test shuffling an array "runs" times
		$array = array(1, 2, 3, 4, 5);
		$result = $test($array);
		$this->assertGreaterThan(0.9, $result);

		// test shuffling an object "runs" times
		$object = new ArrayObject($array);
		$result = $test($object);
		$this->assertGreaterThan(0.9, $result);
	}

	public function testSortBy() {
		// test sorting an array without an iterator
		$array = array(2, 5, 3, 1, 4);
		$result = _::sortBy($array);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
		$this->assertEquals(3, $result[2]);
		$this->assertEquals(4, $result[3]);
		$this->assertEquals(5, $result[4]);

		// test sorting an array with an iterator
		$result = _::sortBy($array, function($left, $right) {
			return (($left == $right) ? 0 : (($left > $right) ? -1 : 1));
		});
		$this->assertEquals(5, $result[0]);
		$this->assertEquals(4, $result[1]);
		$this->assertEquals(3, $result[2]);
		$this->assertEquals(2, $result[3]);
		$this->assertEquals(1, $result[4]);

		// test sorting an object without a value function
		$arrayObj = new ArrayObject($array);
		try {
			$result = _::sortBy($arrayObj);
		} catch (Exception $e) {
			$this->assertInstanceOf('InvalidArgumentException', $e);
		}

		// test sorting an object with a value function
		$result = _::sortBy($arrayObj, function($value) {
			return (5 - $value);
		});
		$this->assertInternalType('array', $result);
		$this->assertCount(5, $result);
		$this->assertEquals(5, $result[0]);
		$this->assertEquals(4, $result[1]);
		$this->assertEquals(3, $result[2]);
		$this->assertEquals(2, $result[3]);
		$this->assertEquals(1, $result[4]);
	}

	public function testGroupBy() {
		// test grouping words by length
		$array = array('one', 'two', 'three');
		$result = _::groupBy($array, function($value) {
			return strlen($value);
		});
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertCount(2, $result[3]);
		$this->assertCount(1, $result[5]);
		$this->assertEquals('one', $result[3][0]);
		$this->assertEquals('two', $result[3][1]);
		$this->assertEquals('three', $result[5][0]);
	}

	public function testCountBy() {
		// test grouping words by length
		$array = array('one', 'two', 'three');
		$result = _::CountBy($array, function($value) {
			return strlen($value);
		});
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals(2, $result[3]);
		$this->assertEquals(1, $result[5]);
	}

	public function testSortedIndex() {
		// test with array of integers
		$array = array(10, 20, 30, 40, 50);
		$value = 35;
		$result = _::sortedIndex($array, $value);
		$this->assertEquals(3, $result);

		// test with array of objects
		$guys = array(
			(object)array('name' => 'Tom', 'age' => 20),
			(object)array('name' => 'Dick', 'age' => 30)
		);
		$guy = (object)array('name' => 'Harry', 'age' => 10);
		$result = _::sortedIndex($guys, $guy, 'age');
		$this->assertEquals(0, $result);
	}

	public function testSize() {
		// test with an array
		$array = array(1, 2, 3, 4, 5);
		$result = _::size($array);
		$this->assertEquals(5, $result);

		// test with an object
		$object = (object)array('one' => 1, 'two' => 2, 'three' => 3);
		$result = _::size($object);
		$this->assertEquals(3, $result);
	}

	/**
	 * Arrays
	 */

	public function testFirst() {
		// test getting the first element
		$array = array(1, 2, 3, 4, 5);
		$result = _::first($array);
		$this->assertEquals(1, $result);

		// test getting the first three elements
		$result = _::first($array, 3);
		$this->assertInternalType('array', $result);
		$this->assertCount(3, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
		$this->assertEquals(3, $result[2]);
	}

	public function testInitial() {
		// test getting all but the last element
		$array = array(1, 2, 3, 4, 5);
		$result = _::initial($array);
		$this->assertInternalType('array', $result);
		$this->assertCount(4, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
		$this->assertEquals(3, $result[2]);
		$this->assertEquals(4, $result[3]);

		// test getting all but the last three elements
		$result = _::initial($array, 3);
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
	}

	public function testLast() {
		// test getting the last element
		$array = array(1, 2, 3, 4, 5);
		$result = _::last($array);
		$this->assertEquals(5, $result);

		// test getting the last three elements
		$result = _::last($array, 3);
		$this->assertInternalType('array', $result);
		$this->assertCount(3, $result);
		$this->assertEquals(3, $result[0]);
		$this->assertEquals(4, $result[1]);
		$this->assertEquals(5, $result[2]);
	}

	public function testRest() {
		// test getting all but the first element
		$array = array(1, 2, 3, 4, 5);
		$result = _::rest($array);
		$this->assertInternalType('array', $result);
		$this->assertCount(4, $result);
		$this->assertEquals(2, $result[0]);
		$this->assertEquals(3, $result[1]);
		$this->assertEquals(4, $result[2]);
		$this->assertEquals(5, $result[3]);

		// test getting all but the first three elements
		$result = _::rest($array, 3);
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals(4, $result[0]);
		$this->assertEquals(5, $result[1]);
	}

	public function testCompact() {
		// test filtering out falsy values
		$array = array(0, 1, null, 2, false, 3, '', 4, 'test');
		$result = _::compact($array);
		$this->assertInternalType('array', $result);
		$this->assertCount(5, $result);
		$this->assertEquals(1, $result[1]);
		$this->assertEquals(2, $result[3]);
		$this->assertEquals(3, $result[5]);
		$this->assertEquals(4, $result[7]);
		$this->assertEquals('test', $result[8]);
	}

	public function testFlatten() {
		// test flattening an array completely
		$array = array(
			array(1, 2, 3),
			array(4, 5, 6),
			array(7 => array(8, 9, 0))
		);
		$result = _::flatten($array);
		$this->assertInternalType('array', $result);
		$this->assertCount(9, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
		$this->assertEquals(3, $result[2]);
		$this->assertEquals(4, $result[3]);
		$this->assertEquals(5, $result[4]);
		$this->assertEquals(6, $result[5]);
		$this->assertEquals(8, $result[6]);
		$this->assertEquals(9, $result[7]);
		$this->assertEquals(0, $result[8]);

		// test flattening an array shallowly
		$result = _::flatten($array, true);
		$this->assertInternalType('array', $result);
		$this->assertCount(7, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
		$this->assertEquals(3, $result[2]);
		$this->assertEquals(4, $result[3]);
		$this->assertEquals(5, $result[4]);
		$this->assertEquals(6, $result[5]);
		$this->assertInternalType('array', $result[6]);
		$this->assertCount(3, $result[6]);
		$this->assertEquals(8, $result[6][0]);
		$this->assertEquals(9, $result[6][1]);
		$this->assertEquals(0, $result[6][2]);
	}

	public function testWithout() {
		// test removing a non-existent element
		$array = array(1, 2, 3, 4, 5);
		$result = _::without($array, 6);
		$this->assertInternalType('array', $result);
		$this->assertCount(5, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
		$this->assertEquals(3, $result[2]);
		$this->assertEquals(4, $result[3]);
		$this->assertEquals(5, $result[4]);

		// test removing a single element
		$result = _::without($array, 3);
		$this->assertInternalType('array', $result);
		$this->assertCount(4, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
		$this->assertEquals(4, $result[3]);
		$this->assertEquals(5, $result[4]);

		// test removing multiple elements
		$result = _::without($array, 1, 2, 3);
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals(4, $result[3]);
		$this->assertEquals(5, $result[4]);

		// test removing all elements
		$result = _::without($array, 1, 2, 3, 4, 5);
		$this->assertInternalType('array', $result);
		$this->assertCount(0, $result);
	}

	public function testUniq() {
		// test removing repeated elements
		$array = array(1, 2, 3, 4, 5, 4, 3, 2);
		$result = _::uniq($array);
		$this->assertInternalType('array', $result);
		$this->assertCount(5, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
		$this->assertEquals(3, $result[2]);
		$this->assertEquals(4, $result[3]);
		$this->assertEquals(5, $result[4]);
	}

	public function testUnion() {
		// test merging two arrays
		$array1 = array(1, 2, 3, 5, 6);
		$array2 = array(1, 4, 5, 7, 8);
		$result = _::union($array1, $array2);
		$this->assertInternalType('array', $result);
		$this->assertCount(8, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
		$this->assertEquals(3, $result[2]);
		$this->assertEquals(5, $result[3]);
		$this->assertEquals(6, $result[4]);
		$this->assertEquals(4, $result[5]);
		$this->assertEquals(7, $result[6]);
		$this->assertEquals(8, $result[7]);
	}

	public function testIntersection() {
		// test merging two arrays
		$array1 = array(1, 2, 3, 5, 6);
		$array2 = array(1, 4, 5, 7, 8);
		$result = _::intersection($array1, $array2);
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(5, $result[1]);
	}

	public function testDifference() {
		// test removing a non-existent element
		$array = array(1, 2, 3, 4, 5);
		$result = _::difference($array, array(6));
		$this->assertInternalType('array', $result);
		$this->assertCount(5, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
		$this->assertEquals(3, $result[2]);
		$this->assertEquals(4, $result[3]);
		$this->assertEquals(5, $result[4]);

		// test removing a single element
		$result = _::difference($array, array(3));
		$this->assertInternalType('array', $result);
		$this->assertCount(4, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
		$this->assertEquals(4, $result[3]);
		$this->assertEquals(5, $result[4]);

		// test removing multiple elements
		$result = _::difference($array, array(1, 2, 3));
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals(4, $result[3]);
		$this->assertEquals(5, $result[4]);

		// test removing all elements
		$result = _::difference($array, array(1, 2, 3, 4, 5));
		$this->assertInternalType('array', $result);
		$this->assertCount(0, $result);
	}

	public function testZip() {
		// test combining array values
		$array1 = array(1, 2, 3);
		$array2 = array('Tom', 'Dick', 'Harry');
		$array3 = array(true, false, null);
		$result = _::zip($array1, $array2, $array3);
		$this->assertInternalType('array', $result);
		$this->assertCount(3, $result);
		$this->assertInternalType('array', $result[0]);
		$this->assertEquals(1, $result[0][0]);
		$this->assertEquals('Tom', $result[0][1]);
		$this->assertEquals(true, $result[0][2]);
		$this->assertInternalType('array', $result[1]);
		$this->assertEquals(2, $result[1][0]);
		$this->assertEquals('Dick', $result[1][1]);
		$this->assertEquals(false, $result[1][2]);
		$this->assertInternalType('array', $result[2]);
		$this->assertEquals(3, $result[2][0]);
		$this->assertEquals('Harry', $result[2][1]);
		$this->assertEquals(null, $result[2][2]);
	}

	public function testObject() {
		// test turning an array into an object directly
		$array = array(array('one', 1), array('two', 2), array('three', 3));
		$result = _::object($array);
		$this->assertInstanceOf('StdClass', $result);
		$this->assertEquals(1, $result->one);
		$this->assertEquals(2, $result->two);
		$this->assertEquals(3, $result->three);

		// test turning an array into an object with values
		$array = array('one', 'two', 'three');
		$values = array(1, 2, 3);
		$result = _::object($array, $values);
		$this->assertInstanceOf('StdClass', $result);
		$this->assertEquals(1, $result->one);
		$this->assertEquals(2, $result->two);
		$this->assertEquals(3, $result->three);
	}

	public function testIndexOf() {
		// test finding an element's index
		$array = array(1, 2, 3, 4, 5);
		$result = _::indexOf($array, 4);
		$this->assertEquals(3, $result);

		// test not finding an element's index
		$result = _::indexOf($array, 6);
		$this->assertEquals(-1, $result);
	}

	public function testLastIndexOf() {
		// test finding index of last occurence of an element
		$array = array(1, 2, 3, 1, 2, 3, 1, 2, 3);
		$result = _::lastIndexOf($array, 3);
		$this->assertEquals(8, $result);

		// test not finding index of last occurence of an element
		$result = _::lastIndexOf($array, 4);
		$this->assertEquals(-1, $result);

		// test finding index of last occurence of an element,
		// using a "from" value
		$array = array(1, 2, 3, 1, 2, 3, 1, 2, 3);
		$result = _::lastIndexOf($array, 3, 6);
		$this->assertEquals(5, $result);
	}

	public function testRange() {
		// test range with only one parameter
		$result = _::range(5);
		$this->assertInternalType('array', $result);
		$this->assertCount(5, $result);
		$this->assertEquals(0, $result[0]);
		$this->assertEquals(1, $result[1]);
		$this->assertEquals(2, $result[2]);
		$this->assertEquals(3, $result[3]);
		$this->assertEquals(4, $result[4]);

		// test range with two parameters
		$result = _::range(1, 4);
		$this->assertInternalType('array', $result);
		$this->assertCount(3, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(2, $result[1]);
		$this->assertEquals(3, $result[2]);

		// test range with three parameters
		$result = _::range(1, 10, 2);
		$this->assertInternalType('array', $result);
		$this->assertCount(5, $result);
		$this->assertEquals(1, $result[0]);
		$this->assertEquals(3, $result[1]);
		$this->assertEquals(5, $result[2]);
		$this->assertEquals(7, $result[3]);
		$this->assertEquals(9, $result[4]);
	}

	/**
	 * Functions
	 */

	public function testPartial() {
		// test filling in an argument
		$function = function($a, $b, $c) {
			return (($a + $b) * $c);
		};
		$expected = $function(5, 6, 7);
		$partialFunction = _::partial($function, 5);
		$result = $partialFunction(6, 7);
		$this->assertEquals($expected, $result);
	}

	public function testMemoize() {
		// test memoizing a Fibonacci function
		$expected = Fibonacci::compute(25);
		$memoizedFibonacci = _::memoize('Fibonacci::compute');
		$start = microtime(true);
		$firstResult = $memoizedFibonacci(25);
		$firstRunTime = (microtime(true) - $start);
		$this->assertEquals($expected, $firstResult);
		$start = microtime(true);
		$secondResult = $memoizedFibonacci(25);
		$secondRunTime = (microtime(true) - $start);
		$this->assertEquals($firstResult, $secondResult);
		$this->assertLessThan($firstRunTime, $secondRunTime);

		// test memoizing with a hasher;
		// intentionally cause a collision
		$function = function($value) {
			return ($value * 2);
		};
		$hasher = function($value) {
			return floor($value / 2.0);
		};
		$memoizedFunction = _::memoize($function, $hasher);
		$expected1 = $function(2);
		$result1 = $memoizedFunction(2);
		$this->assertEquals($expected1, $result1);
		$expected2 = $function(3);
		$result2 = $memoizedFunction(3);
		$this->assertEquals($expected1, $result2);
		$this->assertFalse($expected2 == $result2);
	}

	public function testOnce() {
		// test running a function once
		$function = function() {
			static $n = null;
			if (is_null($n)) {
				$n = 1;
			} else {
				++$n;
			}
			return $n;
		};
		$onceFunction = _::once($function);
		$result1 = $function();
		$this->assertEquals(1, $result1);
		$result2 = $function();
		$this->assertEquals(2, $result2);
		$result3 = $onceFunction();
		$this->assertEquals(3, $result3);
		$result4 = $onceFunction();
		$this->assertEquals(3, $result4);
	}

	public function testWrap() {
		// test wrapping a function
		$function = function() {
			return microtime(true);
		};
		$wrapper = function($function, $time) {
			$start = $function();
			sleep($time);
			return round($function() - $start);
		};
		$wrappedFunction = _::wrap($function, $wrapper);
		$result = $wrappedFunction(1);
		$this->assertEquals(1, $result);
	}

	public function testCompose() {
		// test composing a few functions
		$function1 = function($value) {
			return ($value + 1);
		};
		$function2 = function($value) {
			return ($value * 2);
		};
		$function3 = function($value) {
			return ($value * $value);
		};
		$expected1 = $function3($function2($function1(1)));
		$composition1 = _::compose($function3, $function2, $function1);
		$result1 = $composition1(1);
		$this->assertEquals($expected1, $result1);
		$expected2 = $function2($function3($function1(1)));
		$composition2 = _::compose($function2, $function3, $function1);
		$result2 = $composition2(1);
		$this->assertEquals($expected2, $result2);
	}

	public function testAfter() {
		// test running a function after N times
		$function = function() {
			static $n = null;
			if (is_null($n)) {
				$n = 1;
			} else {
				++$n;
			}
			return $n;
		};
		$afterFunction = _::after(3, $function);
		$result1 = $afterFunction();
		$this->assertNull($result1);
		$result2 = $afterFunction();
		$this->assertNull($result2);
		$result3 = $afterFunction();
		$this->assertEquals(1, $result3);
		$result4 = $afterFunction();
		$this->assertEquals(2, $result4);
	}

	/**
	 * Objects
	 */

	public function testKeys() {
		// test with an array
		$array = array('one' => 1, 'two' => 2, 'three' => 3);
		$expected = array('one', 'two', 'three');
		$result = _::keys($array);
		$this->assertEquals($expected, $result);

		// test with an object
		$object = (object)$array;
		$result = _::keys($object);
		$this->assertEquals($expected, $result);
	}

	public function testValues() {
		// test with an array
		$array = array('one' => 1, 'two' => 2, 'three' => 3);
		$expected = array(1, 2, 3);
		$result = _::values($array);
		$this->assertEquals($expected, $result);

		// test with an object
		$object = (object)$array;
		$result = _::values($object);
		$this->assertEquals($expected, $result);
	}

	public function testPairs() {
		// test with an array
		$array = array('one' => 1, 'two' => 2, 'three' => 3);
		$expected = array(
			array('one', 1),
			array('two', 2),
			array('three', 3)
		);
		$result = _::pairs($array);
		$this->assertEquals($expected, $result);

		// test with an object
		$object = (object)$array;
		$result = _::pairs($object);
		$this->assertEquals($expected, $result);
	}

	public function testInvert() {
		// test with an array
		$array = array('one' => 1, 'two' => 2, 'three' => 3);
		$expected = array(1 => 'one', 2 => 'two', 3 => 'three');
		$result = _::invert($array);
		$this->assertEquals($expected, $result);

		// test with an object
		$object = (object)$array;
		$result = _::invert($object);
		$this->assertEquals($expected, $result);
	}

	public function testPick() {
		// test picking properties off an array
		$array = array('one' => 1, 'two' => 2, 'three' => 3);
		$expected = array('one' => 1, 'three' => 3);
		$result = _::pick($array, 'one', 'three');
		$this->assertEquals($expected, $result);

		// test picking properties off an object
		$object = (object)$array;
		$expected = (object)$expected;
		$result = _::pick($object, 'one', 'three');
		$this->assertEquals($expected, $result);
	}

	public function testOmit() {
		// test picking properties off an array
		$array = array('one' => 1, 'two' => 2, 'three' => 3);
		$expected = array('three' => 3);
		$result = _::omit($array, 'one', 'two');
		$this->assertEquals($expected, $result);

		// test picking properties off an object
		$object = (object)$array;
		$expected = (object)$expected;
		$result = _::omit($object, 'one', 'two');
		$this->assertEquals($expected, $result);
	}

	public function testDefaults() {
		// test defaulting values in an array
		$defaults = array('one' => 1, 'two' => 2, 'three' => 3);
		$array = array('two' => null, 'three' => 4);
		$expected = array('two' => 2, 'three' => 4, 'one' => 1);
		$result = _::defaults($array, $defaults);
		$this->assertEquals($expected, $result);

		// test defaulting values in an object
		$defaults = (object)$defaults;
		$array = (object)$array;
		$expected = (object)$expected;
		$result = _::defaults($array, $defaults);
		$this->assertEquals($expected, $result);
	}

	public function testTap() {
		// test tapping into an object with a function
		$object = (object)array('one' => 1, 'two' => 2, 'three' => 3);
		$function = function(&$obj) {
			$obj->four = 4;
		};
		$result = _::tap($object, $function);
		$this->assertSame($object, $result);
		$this->assertEquals(4, $result->four);
	}

	/**
	 * Utilities
	 */

	public function testIsEqual() {
		// test if two things are approximately equal
		$a = 5;
		$b = '5';
		$c = 'five';
		$result = _::isEqual($a, $b);
		$this->assertEquals(true, $result);

		// test if two things are exactly equal
		$result = _::isEqual($a, $a, true);
		$this->assertEquals(true, $result);

		// test if two things are different
		$result = _::isEqual($a, $c);
		$this->assertEquals(false, $result);
	}

	public function testIsEmpty() {
		// test that null is empty
		$result = _::isEmpty(null);
		$this->assertEquals(true, $result);

		// test that an empty array is empty
		$result = _::isEmpty(array());
		$this->assertEquals(true, $result);

		// test that a non-empty array is not empty
		$result = _::isEmpty(array(1, 2, 3));
		$this->assertEquals(false, $result);

		// test that an empty string is empty
		$result = _::isEmpty('');
		$this->assertEquals(true, $result);

		// test that a non-empty string is not empty
		$result = _::isEmpty('test');
		$this->assertEquals(false, $result);

		// test that an object with no properties is empty
		$object = new StdClass();
		$result = _::isEmpty($object);
		$this->assertEquals(true, $result);

		// test that an object with properties is not empty
		$object->a = 1;
		$result = _::isEmpty($object);
		$this->assertEquals(false, $result);

		// test that anything else is empty per PHP
		$result = _::isEmpty(0);
		$this->assertEquals(true, $result);

		// test that anything else is empty per PHP
		$result = _::isEmpty(1);
		$this->assertEquals(false, $result);
	}

	public function testIsArray() {
		// test that an array is an array
		$array = array('one' => 1, 'two' => 2);
		$result = _::isArray($array);
		$this->assertTrue($result);

		// test that an object is not an array
		$object = (object)$array;
		$result = _::isArray($object);
		$this->assertFalse($result);
	}

	public function testIsObject() {
		// test that an array is not an object
		$array = array('one' => 1, 'two' => 2);
		$object = (object)$array;
		$result = _::isObject($object);
		$this->assertTrue($result);

		// test that an object is an object
		$result = _::isObject($array);
		$this->assertFalse($result);
	}

	public function testIsCallable() {
		// test that a callback is callable
		$result = _::isCallable('Fibonacci::compute');
		$this->assertTrue($result);

		// test that a Closure is callable
		$result = _::isCallable(function(){});
		$this->assertTrue($result);

		// test that an integer is not callable
		$result = _::isCallable(5);
		$this->assertFalse($result);
	}

	public function testIsString() {
		// test that a string is a string
		$result = _::isString('test');
		$this->assertTrue($result);

		// test that an integer is not a string
		$result = _::isString(1);
		$this->assertFalse($result);
	}

	public function testIsNumber() {
		// test that an integer is a number
		$result = _::isNumber(1);
		$this->assertTrue($result);

		// test that a float is a number
		$result = _::isNumber(1.23);
		$this->assertTrue($result);

		// test that a numeric string is a number
		$result = _::isNumber('-1.23');
		$this->assertTrue($result);

		// test that a boolean is not a number
		$result = _::isNumber(false);
		$this->assertFalse($result);
	}

	public function testIsBoolean() {
		// test that true is a boolean
		$result = _::isBoolean(true);
		$this->assertTrue($result);

		// test that false is a boolean
		$result = _::isBoolean(false);
		$this->assertTrue($result);

		// test that null is not a boolean
		$result = _::isBoolean(null);
		$this->assertFalse($result);
	}

	public function testIsNull() {
		// test that null is null
		$result = _::isNull(null);
		$this->assertTrue($result);

		// test that false is not null
		$result = _::isNull(false);
		$this->assertFalse($result);
	}

	public function testHas() {
		// test that an array has a key
		$array = array('one' => 1, 'two' => 2, 'three' => 3);
		$result = _::has($array, 'two');
		$this->assertTrue($result);

		// test that an object has a property
		$object = (object)$array;
		$result = _::has($object, 'two');
		$this->assertTrue($result);

		// test that an object does not have a property
		$result = _::has($object, 'four');
		$this->assertFalse($result);
	}

	public function testIdentity() {
		// test that identity works for scalars
		$number = 5;
		$result = _::identity($number);
		$this->assertSame($number, $result);

		// test that identity works for scalars
		$object = new StdClass();
		$object->x = 'y';
		$result = _::identity($object);
		$this->assertSame($object, $result);
	}

	public function testTimes() {
		// test that a function has run N times
		$function = function() {
			static $n = null;
			if (is_null($n)) {
				$n = 1;
			} else {
				++$n;
			}
			return $n;
		};
		$result = _::times(5, $function);
		$this->assertEquals(array(1, 2, 3, 4, 5), $result);
	}

	public function testRandom() {
		// test random with one parameter
		$results = array();
		foreach (range(0, 100) as $run) {
			$results[] = _::random(10);
		}
		$this->assertContainsOnly('int', $results);
		$this->assertGreaterThanOrEqual(0, min($results));
		$this->assertLessThanOrEqual(10, max($results));
	}

	public function testEscape() {
		// test escaping a string
		$string = 'Tom "&/Or" \'Jerry\' <tnj@site.tld>';
		$expected = 'Tom &quot;&amp;&#x2F;Or&quot; &#x27;Jerry&#x27; &lt;tnj@site.tld&gt;';
		$result = _::escape($string);
		$this->assertEquals($expected, $result);

		// test unescaping a string
		$result = _::escape($result, false);
		$this->assertEquals($string, $result);
	}

	public function testUnescape() {
		// test unescaping a string
		$string = 'Tom &quot;&amp;&#x2F;Or&quot; &#x27;Jerry&#x27; &lt;tnj@site.tld&gt;';
		$expected = 'Tom "&/Or" \'Jerry\' <tnj@site.tld>';
		$result = _::unescape($string);
		$this->assertEquals($expected, $result);
	}

	public function testResult() {
		// test getting a property from an object
		$object = (object)array(
			'one' => 1,
			'two' => 2,
			'three' => function() {
				return 3;
			}
		);
		$result = _::result($object, 'one');
		$this->assertEquals(1, $result);

		// test getting the result a method on an object
		$result = _::result($object, 'three');
		$this->assertEquals(3, $result);
	}

	public function testUniqueId() {
		// test getting a unique id without a prefix
		$result = _::uniqueId();
		$this->assertEquals(1, $result);
		$result = _::uniqueId();
		$this->assertEquals(2, $result);

		// test getting a unique id with a prefix
		$result = _::uniqueId('test');
		$this->assertEquals('test3', $result);
		$result = _::uniqueId('another');
		$this->assertEquals('another4', $result);
	}
}

class InvokeClass {
	public $a;

	public function __construct($a) {
		$this->a = $a;
	}

	public function callMe($obj, $value) {
		return ($this->a + $value);
	}
}

class Fibonacci {
	public static function compute($n) {
		if ($n < 2) {
			return 1;
		}

		return (static::compute($n - 1) +
				static::compute($n - 2));
	}
}
?>