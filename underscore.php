<?php
/**
 * Underscore.js 1.5.1 clone
 *
 * @author bennett.ureta@gmail.com
 */

class _ {
	/**
	 * Internal Functions
	 */

	public static function getBreaker() {
		static $breaker = null;
		if (is_null($breaker)) {
			$breaker = new StdClass();
		}
		return $breaker;
	}
	protected static function _isTraversable($obj) {
		return (is_array($obj) || ($obj instanceof Traversable));
	}
	protected static function _isCountable($obj) {
		return (is_array($obj) || ($obj instanceof Countable));
	}
	protected static function _identityIterator(&$value) {
		if ($value) {
			return $value;
		}
		$className = get_called_class();
		return "$className::identity";
	}
	protected static function _lookupIterator($value) {
		if (is_null($value)) {
			throw new InvalidArgumentException('$value cannot be null');
		}
		if (is_callable($value) || ($value instanceof Closure)) {
			return $value;
		}
		$className = get_called_class();
		return function($obj) use($value, $className) {
			$obj = $className::toArray($obj);
			return @$obj[$value];
		};
	}
	protected static function _group(&$obj, $value = null, $behavior) {
		$iterator = static::_lookupIterator($value ?: static::_identityIterator());
		$isItClosure = static::_validateFunction($iterator);
		$isBeClosure = static::_validateFunction($behavior);
		$result = array();
		static::each($obj, function($value, $index, &$list) use($iterator, $isItClosure, $behavior, $isBeClosure, &$result) {
			$key = ($isItClosure ?
						$iterator($value, $index, $list) :
						call_user_func($iterator, $value, $index, $list));
			($isBeClosure ?
				$behavior($result, $key, $value) :
				call_user_func($behavior, $result, $key, $value));
		});
		return $result;
	}
	protected static function _flatten(array $input, $shallow = false, array &$output) {
		$className = get_called_class();
		if ($shallow && static::every($input, "$className::isArray")) {
			return call_user_func_array('array_merge', $input);
		}
		foreach ($input as $value) {
			if (is_array($value)) {
				if ($shallow) {
					$output = array_merge($output, $value);
				} else {
					static::_flatten($value, $shallow, $output);
				}
			} else {
				$output[] = $value;
			}
		}
		return $output;
	}
	protected static function _validateFunction($function) {
		if ($function instanceof Closure) {
			return true;
		}
		if (is_callable($function)) {
			return false;
		}
		throw new InvalidArgumentException('$function must be callable or Closure');
	}

	/**
	 * Collections
	 */

	/**
	 * Iterates over a list of elements, yielding each in
	 * turn to an iterator function.
	 */
	public static function each(&$obj, $iterator) {
		if (static::_isTraversable($obj)) {
			$values = $obj;
		} elseif (is_object($obj)) {
			$values = get_object_vars($obj);
		} else {
			throw new InvalidArgumentException('each requires an array, Traversable, or plain object');
		}

		$isClosure = static::_validateFunction($iterator);
		$breaker = static::getBreaker();
		foreach ($values as $key => $value) {
			$result = ($isClosure ?
						$iterator($value, $key, $obj) :
						call_user_func($iterator, $value, $key, $obj));
			if ($result === $breaker) {
				return;
			}
		}
	}
	/**
	 * Return the results of applying the iterator to each element.
	 * Aliased to collect.
	 * Note: differs from underscore.js:100; sets $results[$index]
	 */
	public static function map(&$obj, $iterator) {
		$isClosure = static::_validateFunction($iterator);
		$results = array();
		static::each($obj, function($value, $index, &$list) use($iterator, &$results, $isClosure) {
			$results[$index] = ($isClosure ?
									$iterator($value, $index, $list) :
									call_user_func($iterator, $value, $index, $list));
		});
		return $results;
	}
	/**
	 * Reduce builds up a single result from a list of values.
	 * Aliased to foldl, inject.
	 */
	public static function reduce(&$obj, $iterator, $memo = null) {
		$isClosure = static::_validateFunction($iterator);
		if (is_null($obj)) {
			$obj = array();
		}
		$initial = !is_null($memo);
		static::each($obj, function($value, $index, &$list) use($iterator, &$memo, &$initial, $isClosure) {
			if ($initial) {
				$memo = ($isClosure ?
							$iterator($memo, $value, $index, $list) :
							call_user_func($iterator, $memo, $value, $index, $list));
			} else {
				$memo = $value;
				$initial = true;
			}
		});
		if (!$initial) {
			throw new InvalidArgumentException('reduce of empty array with no initial value');
		}
		return $memo;
	}
	/**
	 * The right-associative version of reduce.
	 * Aliased to foldr.
	 */
	public static function reduceRight(&$obj, $iterator, $memo = null) {
		$array = array_reverse(static::toArray($obj));
		return static::reduce($array, $iterator, $memo);
	}
	/**
	 * Return the first value which passes a truth test.
	 * Aliased to detect.
	 */
	public static function find(&$obj, $iterator) {
		$isClosure = static::_validateFunction($iterator);
		$result = null;
		static::some($obj, function($value, $index, &$list) use($iterator, &$result, $isClosure) {
			if ($isClosure ?
					$iterator($value, $index, $list) :
					call_user_func($iterator, $value, $index, $list)) {
				$result = $value;
				return true;
			}
		});
		return $result;
	}
	/**
	 * Return all the elements that pass a truth test.
	 * Aliased to select.
	 * Note: differs from underscore.js:175; sets $results[$index]
	 */
	public static function filter(&$obj, $iterator) {
		$isClosure = static::_validateFunction($iterator);
		$results = array();
		static::each($obj, function($value, $index, &$list) use($iterator, &$results, $isClosure) {
			if ($isClosure ?
					$iterator($value, $index, $list) :
					call_user_func($iterator, $value, $index, $list)) {
				$results[$index] = $value;
			}
		});
		return $results;
	}
	/**
	 * Return all the elements for which a truth test fails.
	 */
	public static function reject($obj, $iterator) {
		$isClosure = static::_validateFunction($iterator);
		return static::filter($obj, function($value, $index, &$list) use($iterator, $isClosure) {
			return ($isClosure ?
						!$iterator($value, $index, $list) :
						!call_user_func($iterator, $value, $index, $list));
		});
	}
	/**
	 * Determine whether all of the elements match a truth test.
	 * Aliased to all.
	 */
	public static function every($obj, $iterator = null) {
		$iterator = static::_identityIterator($iterator);
		$isClosure = static::_validateFunction($iterator);
		$breaker = static::getBreaker();
		$result = true;
		static::each($obj, function($value, $index, &$list) use($iterator, &$breaker, &$result, $isClosure) {
			if ($isClosure ?
					!$iterator($value, $index, $list) :
					!call_user_func($iterator, $value, $index, $list)) {
				$result = false;
				return $breaker;
			}
		});
		return $result;
	}
	/**
	 * Determine if at least one element in the object
	 * matches a truth test. Aliased to any.
	 */
	public static function some(&$obj, $iterator = null) {
		$iterator = static::_identityIterator($iterator);
		$isClosure = static::_validateFunction($iterator);
		$breaker = static::getBreaker();
		$result = false;
		static::each($obj, function($value, $index, &$list) use($iterator, &$breaker, &$result, $isClosure) {
			if ($result || ($result = ($isClosure ?
					$iterator($value, $index, $list) :
					call_user_func($iterator, $value, $index, $list)))) {
				return $breaker;
			}
		});
		return $result;
	}
	/**
	 * Determine if the array or object contains
	 * a given value (using ===).
	 * Aliased to includes.
	 */
	public static function contains(&$obj, $target) {
		return static::some($obj, function($value) use($target) {
			return ($value === $target);
		});
	}
	/**
	 * Invoke a method (with arguments) on every item in a collection.
	 */
	public static function invoke() {
		$args = func_get_args();
		$obj = $args[0];
		$method = $args[1];
		$args = array_slice($args, 2);
		array_unshift($args, null);
		$isClosure = ($method instanceof Closure);
		$isCallable = is_callable($method);
		return static::map($obj, function($value) use($method, &$args, $isCallable, $isClosure) {
			$iterator = (($isCallable || $isClosure) ? $method : array($value, $method));
			$args[0] = $value;
			return call_user_func_array($iterator, $args);
		});
	}
	/**
	 * Convenience version of a common use case of map:
	 * fetching a property.
	 */
	public static function pluck(&$obj, $key) {
		$className = get_called_class();
		return static::map($obj, function($value) use($key, $className) {
			$array = $className::toArray($value);
			return @$array[$key];
		});
	}
	/**
	 * Convenience version of a common use case of filter:
	 * selecting only objects containing specific key:value
	 * pairs.
	 */
	public static function where(&$obj, $attrs = null, $first = false) {
		$className = get_called_class();
		$attrs = static::toArray($attrs);
		if (static::isEmpty($attrs)) {
			return ($first ? null : array());
		}
		$func = ($first ? 'find' : 'filter');
		return static::$func($obj, function($value) use($attrs, $className) {
			$value = $className::toArray($value);
			foreach ($attrs as $key => $attr) {
				if (!isset($value[$key]) ||
					($value[$key] !== $attr)) {
					return false;
				}
			}
			return true;
		});
	}
	/**
	 * Convenience version of a common use case of find:
	 * getting the first object containing specific
	 * key:value pairs.
	 */
	public static function findWhere($obj, $attrs) {
		return static::where($obj, $attrs, true);
	}
	/**
	 * Return the maximum element or (element-based computation).
	 */
	public static function max($obj, $iterator = null) {
		if ($iterator) {
			$isClosure = static::_validateFunction($iterator);
		} else {
			if (static::_isCountable($obj) && !count($obj)) {
				return -INF;
			}
			if (is_array($obj)) {
				return max($obj);
			}
			$isClosure = false;
		}
		$result = array('computed' => -INF, 'value' => -INF);
		static::each($obj, function($value, $index, &$list) use($iterator, &$result, $isClosure) {
			$computed = ($iterator ? ($isClosure ?
							$iterator($value, $index, $list) :
							call_user_func($iterator, $value, $index, $list)) :
							$value);
			if ($computed > $result['computed']) {
				$result['computed'] = $computed;
				$result['value'] = $value;
			}
		});
		return $result['value'];
	}
	/**
	 * Return the minimum element or (element-based computation).
	 */
	public static function min($obj, $iterator = null) {
		if ($iterator) {
			$isClosure = static::_validateFunction($iterator);
		} else {
			if (static::_isCountable($obj) && !count($obj)) {
				return INF;
			}
			if (is_array($obj)) {
				return min($obj);
			}
			$isClosure = false;
		}
		$result = array('computed' => INF, 'value' => INF);
		static::each($obj, function($value, $index, &$list) use($iterator, &$result, $isClosure) {
			$computed = ($iterator ? ($isClosure ?
							$iterator($value, $index, $list) :
							call_user_func($iterator, $value, $index, $list)) :
							$value);
			if ($computed < $result['computed']) {
				$result['computed'] = $computed;
				$result['value'] = $value;
			}
		});
		return $result['value'];
	}
	/**
	 * Shuffle an array.
	 */
	public static function shuffle($obj) {
		if (is_array($obj)) {
			shuffle($obj);
			return $obj;
		}
		$index = 0;
		$shuffled = array();
		$className = get_called_class();
		static::each($obj, function($value) use($index, &$shuffled, $className) {
			$rand = $className::random($index);
			$shuffled[$index++] = @$shuffled[$rand];
			$shuffled[$rand] = $value;
		});
		return $shuffled;
	}
	/**
	 * Sort the object's values by a criterion produced by an iterator.
	 */
	public static function sortBy(&$obj, $value = null) {
		// trivial case for arrays
		if (is_array($obj)) {
			if ($value) {
				$iterator = static::_lookupIterator($value);
				usort($obj, $iterator);
			} else {
				sort($obj);
			}
			return $obj;
		}
		// complex case for objects
		$iterator = static::_lookupIterator($value);
		$isClosure = static::_validateFunction($iterator);
		$array = static::map($obj, function($value, $index, &$list) use($iterator, $isClosure) {
			return array(
				'value' => $value,
				'index' => $index,
				'criteria' => ($isClosure ?
									$iterator($value, $index, $list) :
									call_user_func($iterator, $value, $index, $list))
			);
		});
		usort($array, function($left, $right) {
			$a = $left['criteria'];
			$b = $right['criteria'];
			if ($a !== $b) {
				if (($a > $b) || (0 === $a)) {
					return 1;
				}
				if (($a < $b) || (0 === $b)) {
					return -1;
				}
			}
			return (($left['index'] < $right['index']) ? -1 : 1);
		});
		return static::pluck($array, 'value');
	}
	/**
	 * Groups the object's values by a criterion. Pass either
	 * a string attribute to group by, or a function that
	 * returns the criterion.
	 */
	public static function groupBy(&$obj, $value) {
		return static::_group($obj, $value, function(&$result, $key, $value) {
			if (!isset($result[$key])) {
				$result[$key] = array();
			}
			$result[$key][] = $value;
		});
	}
	/**
	 * Counts instances of an object that group by a certain
	 * criterion. Pass either a string attribute to count by,
	 * or a function that returns the criterion.
	 */
	public static function countBy(&$obj, $value) {
		return static::_group($obj, $value, function(&$result, $key) {
			if (!isset($result[$key])) {
				$result[$key] = 0;
			}
			++$result[$key];
		});
	}
	/**
	 * Use a comparator function to figure out the smallest
	 * index at which an object should be inserted so as to
	 * maintain order. Uses binary search.
	 */
	public static function sortedIndex(array $array, $obj, $iterator = null) {
		$iterator = ($iterator ?
						static::_lookupIterator($iterator) :
						(get_called_class() . '::identity'));
		$isClosure = static::_validateFunction($iterator);
    	$value = ($isClosure ?
					$iterator($obj) :
					call_user_func($iterator, $obj));
		$low = 0;
		$high = count($array);
		while ($low < $high) {
			$mid = floor(($low + $high) / 2);
			$midValue = ($isClosure ?
							$iterator($array[$mid]) :
							call_user_func($iterator, $array[$mid]));
			if ($midValue < $value) {
				$low = ($mid + 1);
			} else {
				$high = $mid;
			}
		}
		return $low;
	}
	/**
	 * Returns arrays and Traversable objects as-is;
	 * generic objects as their variables;
	 * everything else as an empty array.
	 */
	public static function toArray(&$obj) {
		if (static::_isTraversable($obj)) {
			return $obj;
		}
		if (is_object($obj)) {
			return get_object_vars($obj);
		}
		return array();
	}
	/**
	 * Return the number of elements in an object.
	 */
	public static function size($obj) {
		return count(static::toArray($obj));
	}

	/**
	 * Arrays
	 */

	/**
	 * Get the first element of an array.
	 * Passing $n will return the first N
	 * values in the array. Aliased to
	 * head and take. The guard check
	 * allows it to work with map.
	 */
	public static function first(array $array, $n = null, $guard = false) {
		return ((is_int($n) && !$guard) ?
					array_slice($array, 0, $n) :
					@$array[0]);
	}
	/**
	 * Returns everything but the last entry of the array.
	 * Especially useful on the arguments object. Passing
	 * $n will return all the values in the array, excluding
	 * the last N. The guard check allows it to work with map.
	 */
	public static function initial(array $array, $n = null, $guard = false) {
		return array_slice($array, 0, ((is_int($n) && !$guard) ? -$n : -1));
	}
	/**
	 * Get the last element of an array.
	 * Passing $n will return the last N
	 * values in the array. The guard check
	 * allows it to work with map.
	 */
	public static function last(array $array, $n = null, $guard = false) {
		if (is_int($n) && !$guard) {
			return array_slice($array, max((count($array) - $n), 0));
		}
		return end($array);
	}
	/**
	 * Returns everything but the first entry of the array.
	 * Aliased to tail and drop. Especially useful on the
	 * arguments object. Passing an $n will return the rest
	 * N values in the array. The guard check allows it to
	 * work with map.
	 */
	public static function rest(array $array, $n = null, $guard = false) {
		return array_slice($array, ((is_int($n) && !$guard) ? $n : 1));
	}
	/**
	 * Trim out all falsy values from an array.
	 */
	public static function compact(array $array) {
		return array_filter($array);
	}
	/**
	 * Return a completely flattened version of an array.
	 */
	public static function flatten(array $array, $shallow = false) {
		$result = array();
		return static::_flatten($array, $shallow, $result);
	}
	/**
	 * Return a version of the array that does not
	 * contain the specified value(s).
	 */
	public static function without() {
		$args = func_get_args();
		return static::difference($args[0], array_slice($args, 1));
	}
	/**
	 * Produce a duplicate-free version of the array.
	 * Aliased to unique.
	 */
	public static function uniq(array $array) {
		return array_unique($array);
	}
	/**
	 * Produce an array that contains the union:
	 * each distinct element from all of the
	 * passed-in arrays.
	 */
	public static function union() {
		return array_merge(static::unique(static::flatten(func_get_args(), true)));
	}
	/**
	 * Produce an array that contains every item
	 * shared between all the passed-in arrays.
	 */
	public static function intersection() {
		return array_merge(static::unique(call_user_func_array('array_intersect', func_get_args())));
	}
	/**
	 * Take the difference between one array and
	 * a number of other arrays. Only the elements
	 * present in just the first array will remain.
	 */
	public static function difference() {
		return call_user_func_array('array_diff', func_get_args());
	}
	/**
	 * Zip together multiple lists into a single
	 * array -- elements that share an index go
	 * together.
	 */
	public static function zip() {
		$args = func_get_args();
		$length = (count($args) ? max(array_map('count', $args)) : 0);
		$results = array();
		for ($i = 0; $i < $length; ++$i) {
			foreach ($args as $arg) {
				$results[$i][] = @$arg[$i];
			}
		}
		return $results;
	}
	/**
	 * Converts lists into objects. Pass either a
	 * single array of [key, value] pairs, or two
	 * parallel arrays of the same length -- one
	 * of keys, and one of the corresponding values.
	 */
	public static function object(array $list, $values = null) {
		$result = new StdClass();
		for ($i = 0, $l = count($list); $i < $l; ++$i) {
			if (is_array($values)) {
				$result->$list[$i] = $values[$i];
			} else {
				$result->$list[$i][0] = $list[$i][1];
			}
		}
		return $result;
	}
	/**
	 * Return the position of the first occurrence
	 * of an item in an array, or -1 if the item is
	 * not included in the array.
	 */
	public static function indexOf(array $array, $item) {
		$index = array_search($item, $array);
		return (is_int($index) ? $index : -1);
	}
	/**
	 * Return the position of the last occurrence
	 * of an item in an array, or -1 if the item is
	 * not included in the array.
	 */
	public static function lastIndexOf(array $array, $item, $from = null) {
		if (!is_null($from)) {
			$array = array_slice($array, 0, $from, true);
		}
		$array = array_reverse($array, true);
		return static::indexOf($array, $item);
	}
	/**
	 * Generate an integer array containing an arithmetic progression.
	 */
	public static function range($start, $stop = null, $step = 1) {
		if (is_null($stop)) {
			$stop = $start;
			$start = 0;
		}
		return range($start, ($stop - 1), $step);
	}

	/**
	 * Functions
	 */

	/**
	 * Partially apply a function by creating a version that
	 * has had some of its arguments pre-filled.
	 */
	public static function partial() {
		$args = func_get_args();
		$func = array_shift($args);
		return function() use($func, $args) {
			return call_user_func_array($func, array_merge($args, func_get_args()));
		};
	}
	/**
	 * Memoize an expensive function by storing its results.
	 */
	public static function memoize($func, $hasher = null) {
		$hasher = static::_identityIterator($hasher);
		return function() use($func, $hasher) {
			static $memo = null;
			if (is_null($memo)) {
				$memo = array();
			}
			$args = func_get_args();
			$key = call_user_func_array($hasher, $args);
			if (!isset($memo[$key])) {
				$memo[$key] = call_user_func_array($func, $args);
			}
			return $memo[$key];
		};
	}
	/**
	 * Returns a function that will be executed at most
	 * one time, no matter how often you call it. Useful
	 * for lazy initialization.
	 */
	public static function once($func) {
		return function() use($func) {
			static $ran = false,
					$memo = null;
			if (!$ran) {
				$ran = true;
				$args = func_get_args();
				$memo = call_user_func_array($func, $args);
			}
			return $memo;
		};
	}
	/**
	 * Returns the first function passed as an argument
	 * to the second, allowing you to adjust arguments,
	 * run code before and after, and conditionally
	 * execute the original function.
	 */
	public static function wrap($func, $wrapper) {
		return function() use($func, $wrapper) {
			$args = func_get_args();
			array_unshift($args, $func);
			return call_user_func_array($wrapper, $args);
		};
	}
	/**
	 * Returns a function that is the composition of
	 * a list of functions, each consuming the return
	 * value of the function that follows.
	 */
	public static function compose() {
		$funcs = array_reverse(func_get_args());
		return function() use($funcs) {
			$args = func_get_args();
			foreach ($funcs as $func) {
				$args = array(call_user_func_array($func, $args));
			}
			return $args[0];
		};
	}
	/**
	 * Returns a function that will only be executed
	 * after being called N times.
	 */
	public static function after($times, $func) {
		return function() use($times, $func) {
			static $afterTimes = null;
			if (is_null($afterTimes)) {
				$afterTimes = $times;
			}
			if (--$afterTimes < 1) {
				$args = func_get_args();
				return call_user_func_array($func, $args);
			}
		};
	}

	/**
	 * Objects
	 */

	/**
	 * Retrieve the names of an object's properties.
	 */
	public static function keys($obj) {
		return array_keys(static::toArray($obj));
	}
	/**
	 * Retrieve the values of an object's properties.
	 */
	public static function values($obj) {
		return array_values(static::toArray($obj));
	}
	/**
	 * Convert an object into a list of [key, value] pairs.
	 */
	public static function pairs($obj) {
		$pairs = array();
		foreach (static::toArray($obj) as $key => $value) {
			$pairs[] = array($key, $value);
		}
		return $pairs;
	}
	/**
	 * Invert the keys and values of an object.
	 */
	public static function invert($obj) {
		return array_flip(static::toArray($obj));
	}
	/**
	 * Return a copy of the object only containing
	 * the whitelisted properties.
	 */
	public static function pick() {
		$copy = array();
		$args = func_get_args();
		$obj = static::toArray($args[0]);
		$keys = array_slice($args, 1);
		foreach ($keys as $key) {
			$copy[$key] = $obj[$key];
		}
		return (is_object($args[0]) ? (object)$copy : $copy);
	}
	/**
	 * Return a copy of the object without the
	 * blacklisted properties.
	 */
	public static function omit() {
		$copy = array();
		$args = func_get_args();
		$obj = static::toArray($args[0]);
		$keys = array_fill_keys(array_slice($args, 1), true);
		foreach ($obj as $key => $value) {
			if (!isset($keys[$key])) {
				$copy[$key] = $obj[$key];
			}
		}
		return (is_object($args[0]) ? (object)$copy : $copy);
	}
	/**
	 * Fill in a given object with default properties.
	 */
	public static function defaults() {
		$args = func_get_args();
		$obj = array_shift($args);
		$isArray = is_array($obj);
		$isObject = is_object($obj);
		foreach ($args as $source) {
			if ($source) {
				foreach (static::toArray($source) as $prop => $value) {
					if ($isArray && !@$obj[$prop]) {
						$obj[$prop] = $value;
					} elseif ($isObject && !@$obj->$prop) {
						$obj->$prop = $value;
					}
				}
			}
		}
		return $obj;
	}
	/**
	 * Invokes interceptor with the obj, and then returns obj.
	 * The primary purpose of this method is to "tap into" a
	 * method chain, in order to perform operations on
	 * intermediate results within the chain.
	 */
	public static function tap(&$obj, $interceptor) {
		$isClosure = static::_validateFunction($interceptor);
		if ($isClosure) {
			$interceptor($obj);
		} else {
			// $obj = call_user_func($interceptor, $obj); ?
			call_user_func($interceptor, $obj);
		}
		return $obj;
	}

	/**
	 * Utilities
	 */

	/**
	 * Perform a deep comparison to check if two objects are equal.
	 */
	public static function isEqual($a, $b, $strict = false) {
		return ($strict ? ($a === $b) : ($a == $b));
	}
	/**
	 * Is a given array, string, or object empty?
	 * An "empty" object has no properties.
	 */
	public static function isEmpty($obj) {
		if (is_null($obj)) {
			return true;
		}
		if (is_array($obj)) {
			return !count($obj);
		}
		if (is_string($obj)) {
			return !strlen($obj);
		}
		if (is_object($obj)) {
			return !count(get_object_vars($obj));
		}
		return empty($obj);
	}
	/**
	 * Is a given value an array?
	 */
	public static function isArray($obj) {
		return is_array($obj);
	}
	/**
	 * Is a given value an object?
	 */
	public static function isObject($obj) {
		return is_object($obj);
	}
	/**
	 * Is a given value callable?
	 */
	public static function isCallable($obj) {
		return (is_callable($obj) || ($obj instanceof Closure));
	}
	/**
	 * Is a given value a string?
	 */
	public static function isString($obj) {
		return is_string($obj);
	}
	/**
	 * Is a given value a number?
	 */
	public static function isNumber($obj) {
		return (is_int($obj) || is_float($obj) ||
				(is_string($obj) && is_numeric($obj)));
	}
	/**
	 * Is a given value a Boolean?
	 */
	public static function isBoolean($obj) {
		return is_bool($obj);
	}
	/**
	 * Is a given value null?
	 */
	public static function isNull($obj) {
		return is_null($obj);
	}
	/**
	 * Determines if an object has a given property.
	 */
	public static function has($obj, $key) {
		return in_array($key, static::keys($obj));
	}
	/**
	 * Given a value, returns it as-is.
	 */
	public static function identity($value) {
		return $value;
	}
	/**
	 * Run a function n times.
	 */
	public static function times($n, $iterator) {
		$isClosure = static::_validateFunction($iterator);
		$accum = array();
		for ($i = 0; $i < $n; ++$i) {
			$accum[] = ($isClosure ? $iterator($i) :
						call_user_func($iterator, $i));
		}
		return $accum;
	}
	/**
	 * Return a random integer between min and max (inclusive).
	 */
	public static function random($min, $max = null) {
		if (is_null($max)) {
			$max = $min;
			$min = 0;
		}
		return rand($min, $max);
	}
	/**
	 * Functions for escaping and unescaping strings to/from HTML interpolation.
	 */
	public static function escape($string, $forward = true) {
		static $entityMap = null;
		if (is_null($entityMap)) {
			$entityMap = array(
				array('&', '<', '>', '"', "'", '/'),
				array('&amp;', '&lt;', '&gt;', '&quot;', '&#x27;', '&#x2F;')
			);
		}
		if (is_null($string)) {
			return '';
		}
		if (!is_string($string)) {
			$string = strval($string);
		}
		if ($forward) {
			list($from, $to) = $entityMap;
		} else {
			list($to, $from) = $entityMap;
		}
		return str_replace($from, $to, $string);
	}
	public static function unescape($string) {
		return static::escape($string, false);
	}
	/**
	 * If the value of the named property is a
	 * function then invoke it; otherwise, return it.
	 */
	public static function result($obj, $prop) {
		if (is_null($obj)) {
			return null;
		}
		$value = ((is_array($obj) && isset($obj[$prop])) ?
						$obj[$prop] :
					((is_object($obj) && isset($obj->$prop)) ?
						$obj->$prop :
						null));
		return (is_callable($value) ?
					call_user_func($value) :
					$value);
	}
	/**
	 * Generate a unique integer id (within request).
	 */
	protected static $idCounter = 0;
	public static function uniqueId($prefix = null) {
		$id = ++static::$idCounter;
		return (is_string($prefix) ? "$prefix$id" : $id);
	}

	/**
	 * Aliases
	 */

	public static function __callStatic($name, $arguments) {
		switch ($name) {
			case 'forEach':
			case 'for_each': $method = 'each';        break;
			case 'collect':  $method = 'map';         break;
			case 'foldl':
			case 'inject':   $method = 'reduce';      break;
			case 'foldr':    $method = 'reduceRight'; break;
			case 'detect':   $method = 'find';        break;
			case 'select':   $method = 'filter';      break;
			case 'all':      $method = 'every';       break;
			case 'any':      $method = 'some';        break;
			case 'includes': $method = 'contains';    break;
			case 'head':
			case 'take':     $method = 'first';       break;
			case 'tail':
			case 'drop':     $method = 'first';       break;
			case 'unique':   $method = 'uniq';        break;
			default:
				throw new BadMethodCallException("Undefined method: $name");
			break;
		}
		return call_user_func_array("static::$method", $arguments);
	}
}
?>