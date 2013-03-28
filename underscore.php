<?php
/**
 * Underscore.js clone
 *
 * @author anthony@dynamit.us
 */

class _ {
	/*
	 * INTERNAL FUNCTIONS
	 */

	protected static function _getBreaker() {
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
	protected static function _lookupIterator($value) {
		$className = get_called_class();
		return (is_callable($value) ? $value : function($obj) use($value, $className) {
			$obj = $className::toArray($obj);
			return (isset($obj[$value]) ? $obj[$value] : null);
		});
	}
	protected static function _group($obj, $value = null, callable $behavior) {
		$className = get_called_class();
		$iterator = static::_lookupIterator($value ?: "$className::identity");
		$result = array();
		static::each($obj, function($value, $index) use($iterator, $result) {
			$key = call_user_func($iterator, $value, $index, $obj);
			$behavior($result, $key, $value);
		});
		return $result;
	}
	protected static function _flatten(array $input, $shallow = false, array &$output) {
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

	/*
	 * COLLECTIONS
	 */

	/**
	 * Iterates over a list of elements, yielding each in
	 * turn to an iterator function. Aliased to forEach.
	 */
	public static function each($obj, callable $iterator) {
		if (static::_isTraversable($obj)) {
			$breaker = static::_getBreaker();
			foreach ($obj as $key => $value) {
				if (call_user_func($iterator, $value, $key, $obj) === $breaker) {
					return;
				}
			}
		}
	}
	/**
	 * Return the results of applying the iterator to each element.
	 * Aliased to collect.
	 */
	public static function map($obj, callable $iterator) {
		$results = array();
		static::each($obj, function($value, $index, $list) use($iterator, $results) {
			$results[] = call_user_func($iterator, $value, $index, $list);
		});
		return $results;
	}
	/**
	 * Reduce builds up a single result from a list of values.
	 * Aliased to foldl, inject.
	 */
	public static function reduce($obj, callable $iterator, $memo = null) {
		if (is_null($obj)) {
			$obj = array();
		}
		$initial = !is_null($memo);
		static::each($obj, function($value, $index, $list) use($iterator, $memo, $initial) {
			if ($initial) {
				$memo = call_user_func($iterator, $memo, $value, $index, $list);
			} else {
				$memo = $value;
				$initial = true;
			}
		});
		return ($initial ? $memo : null);
	}
	/**
	 * The right-associative version of reduce.
	 * Aliased to foldr.
	 */
	public static function reduceRight($obj, callable $iterator, $memo = null) {
		return static::reduce(array_reverse(static::toArray($obj)), $iterator, $memo);
	}
	/**
	 * Return the first value which passes a truth test.
	 * Aliased to detect.
	 */
	public static function find($obj, callable $iterator) {
		$result = null;
		static::some($obj, function($value, $index, $list) use($iterator, $result) {
			if (call_user_func($iterator, $value, $index, $list)) {
				$result = $value;
				return true;
			}
		});
		return $result;
	}
	/**
	 * Return all the elements that pass a truth test.
	 * Aliased to select.
	 */
	public static function filter($obj, callable $iterator) {
		$results = array();
		static::each($obj, function($value, $index, $list) use($iterator, $results) {
			if (call_user_func($iterator, $value, $index, $list)) {
				$results[] = $value;
			}
		});
		return $results;
	}
	/**
	 * Return all the elements for which a truth test fails.
	 */
	public static function reject($obj, callable $iterator) {
		return static::filter($obj, function($value, $index, $list) use($iterator) {
			return !call_user_func($iterator, $value, $index, $list);
		});
	}
	/**
	 * Determine whether all of the elements match a truth test.
	 * Aliased to all.
	 */
	public static function every($obj, callable $iterator = null) {
		if (!$iterator) {
			$className = get_called_class();
			$iterator = "$className::identity";
		}
		$breaker = static::_getBreaker();
		$result = true;
		static::each($obj, function($value, $index, $list) use($iterator, $breaker, $result) {
			if (!call_user_func($iterator, $value, $index, $list)) {
				$result = false;
				return $breaker;
			}
		});
		return $result;
	}
	/**
	 * Determine if at least one element in the
	 * object matches a truth test.
	 * Aliased to any.
	 */
	public static function some($obj, callable $iterator) {
		if (!$iterator) {
			$className = get_called_class();
			$iterator = "$className::identity";
		}
		$breaker = static::_getBreaker();
		$result = false;
		static::each($obj, function($value, $index, $list) use($breaker, $result) {
			if ($result || ($result = call_user_func($iterator, $value, $index, $list))) {
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
	public static function contains($obj, $target) {
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
		$isCallable = is_callable($method);
		return static::map($obj, function($value) use($method, $args, $isCallable) {
			$iterator = ($isCallable ? $method : array($value, $method));
			return call_user_func($iterator, $value, $args);
		});
	}
	/**
	 * Convenience version of a common use case of map:
	 * fetching a property.
	 */
	public static function pluck($obj, $key) {
		return static::map($obj, function($value) use($key) {
			$value = static::toArray($value);
			return (isset($value[$key]) ? $value[$key] : null);
		});
	}
	/**
	 * Convenience version of a common use case of filter:
	 * selecting only objects containing specific key:value
	 * pairs.
	 */
	public static function where($obj, $attrs, $first = false) {
		$attrs = static::toArray($attrs);
		if (static::isEmpty($attrs)) {
			return ($first ? null : array());
		}
		$iterator = function($value) use($attrs) {
			$value = static::toArray($value);
			foreach ($attrs as $key => $attr) {
				if (!isset($value[$key]) ||
					($value[$key] !== $attr)) {
					return false;
				}
			}
			return true;
		};
		$func = ($first ? 'find' : 'filter');
		return static::$func($obj, $iterator);
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
	public static function max($obj, callable $iterator = null) {
		if (static::_isCountable($obj) && count($obj)) {
			return max($iterator ? static::map($obj, $iterator) : $obj);
		}
		return null;
	}
	/**
	 * Return the minimum element or (element-based computation).
	 */
	public static function min($obj, callable $iterator = null) {
		if (static::_isCountable($obj) && count($obj)) {
			return min($iterator ? static::map($obj, $iterator) : $obj);
		}
		return null;
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
		static::each($obj, function($value) use($index, $shuffled) {
			$rand = static::random($index);
			$shuffled[$index++] = @$shuffled[$rand];
			$shuffled[$rand] = $value;
		});
		return $shuffled;
	}
	/**
	 * Sort the object's values by a criterion produced by an iterator.
	 */
	public static function sortBy($obj, callable $value = null) {
		$iterator = static::_lookupIterator($value);
		// trivial case for arrays
		if (is_array($obj)) {
			if ($value) {
				usort($obj, $iterator);
			} else {
				sort($obj);
			}
			return $obj;
		}
		// complex case for objects
		$array = static::map($obj, function($value, $index, $list) {
			return array(
				'value' => $value,
				'index' => $index,
				'criteria' => call_user_func($iterator, $value, $index, $list)
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
	public static function groupBy($obj, $value) {
		return static::_group($obj, $value, function(&$result, $key, $value) {
			if (!isset($result[$key])) {
				$result[$key] = array();
			}
			$result[$key][$value];
		});
	}
	/**
	 * Counts instances of an object that group by a certain
	 * criterion. Pass either a string attribute to count by,
	 * or a function that returns the criterion.
	 */
	public static function countBy($obj, $value) {
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
		$iterator = ($iterator ? static::_lookupIterator($iterator) : static::identity());
    	$value = call_user_func($iterator, $obj);
		$low = 0;
		$high = count($array);
		while ($low < $high) {
			$mid = floor(($low + $high) / 2);
			if (call_user_func($iterator, $array[$mid]) < $value) {
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
	public static function toArray($obj) {
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

	/*
	 * ARRAYS
	 */

	/**
	 * Get the first element of an array.
	 * Passing $n will return the first N
	 * values in the array. Aliased to
	 * head and take. The guard check
	 * allows it to work with map.
	 */
	public static function first(array $array, $n = null, $guard = false) {
		if (is_int($n) && !$guard) {
			return array_slice($array, 0, $n);
		}
		return (isset($array[0]) ? $array[0] : null);
	}
	/**
	 * Returns everything but the last entry of the array.
	 * Especially useful on the arguments object. Passing
	 * $n will return all the values in the array, excluding
	 * the last N. The guard check allows it to work with map.
	 */
	public static function initial(array $array, $n = null, $guard = false) {
		$end = ((is_int($n) && !$guard) ? $n : 1);
		return array_slice($array, 0, -$end);
	}
	/**
	 * Get the last element of an array.
	 * Passing $n will return the last N
	 * values in the array. The guard check
	 * allows it to work with map.
	 */
	public static function last(array $array, $n = null, $guard = false) {
		if (is_int($n) && !$guard) {
			$start = max((count($array) - $n), 0);
			return array_slice($array, $start);
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
		return array_slice($array, (is_int($n) && !$guard ? $n : 1));
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
	public static function uniq($array) {
		return array_unique($array);
	}
	/**
	 * Produce an array that contains the union:
	 * each distinct element from all of the
	 * passed-in arrays.
	 */
	public static function union() {
		return static::unique(call_user_func_array('array_merge', func_get_args()));
	}
	/**
	 * Produce an array that contains every item
	 * shared between all the passed-in arrays.
	 */
	public static function intersection() {
		return static::unique(call_user_func_array('array_intersect', func_get_args()));
	}
	/**
	 * Take the difference between one array and
	 * a number of other arrays. Only the elements
	 * present in just the first array will remain.
	 */
	public static function difference() {
		$args = func_get_args();
		return array_diff($args[0], array_merge(array_slice($args, 1)));
	}
	/**
	 * Zip together multiple lists into a single
	 * array -- elements that share an index go
	 * together.
	 */
	public static function zip() {
		$args = func_get_args();
		$length = max(array_map('count', $args));
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
	public static function object($list, $values = null) {
		$result = new StdClass();
		for ($i = 0, $l = count($list); $i < $l; ++$i) {
			if ($values) {
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
	public static function indexOf($array, $item) {
		$index = array_search($array, $item);
		return (is_int($index) ? $index : -1);
	}
	/**
	 * Return the position of the last occurrence
	 * of an item in an array, or -1 if the item is
	 * not included in the array.
	 */
	public static function lastIndexOf($array, $item, $from = 0) {
		return static::indexOf(array_reverse($array, true));
	}
	/**
	 * Generate an integer array containing an arithmetic progression.
	 */
	public static function range($start, $stop = null, $step = 1) {
		if (is_null($stop)) {
			$stop = $start;
			$start = 0;
		}
		return range($start, $stop, $step);
	}

	/*
	 * FUNCTIONS
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
	public static function memoize(callable $func, callable $hasher = null) {
		if (is_null($hasher)) {
			$className = get_called_class();
			$hasher = "$className::identity";
		}
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
	public static function once(callable $func) {
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
	public static function wrap(callable $func, $wrapper) {
		return function() use($func, $wrapper) {
			$args = func_get_args();
			array_unshift($func);
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
	public static function after($times, callable $func) {
		if ($times < 1) {
			return call_user_func($func);
		}
		return function() use($times) {
			if (--$times < 1) {
				$args = func_get_args();
				return call_user_func_array($func, $args);
			}
		};
	}

	/*
	 * OBJECT
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
	 * The values must be serializable.
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
		$keys = array_slice($args);
		foreach ($keys as $key) {
			$copy[$key] = $obj[$key];
		}
		return $copy;
	}
	/**
	 * Return a copy of the object without the
	 * blacklisted properties.
	 */
	public static function omit() {
		$copy = array();
		$args = func_get_args();
		$obj = static::toArray($args[0]);
		$keys = array_fill_keys(array_slice($args), true);
		foreach ($obj as $key => $value) {
			if (!isset($keys[$key])) {
				$copy[$key] = $obj[$key];
			}
		}
		return $copy;
	}
	/**
	 * Fill in a given object with default properties.
	 */
	public static function defaults() {
		$args = func_get_args();
		$obj = array_shift($args);
		$is_array = is_array($obj);
		$is_object = is_object($obj);
		foreach ($args as $source) {
			if ($source) {
				foreach (static::toArray($source) as $prop => $value) {
					if ($is_array && !@$obj[$prop]) {
						$obj[$prop] = $value;
					} elseif ($is_object && !@$obj->$prop) {
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
	public static function tap($obj, callable $interceptor) {
		call_user_func($interceptor, $obj);
		return $obj;
	}

	/*
	 * UTILITIES
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
			return (0 == count($obj));
		}
		if (is_string($obj)) {
			return (0 == strlen($obj));
		}
		if (is_object($obj)) {
			return true;
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
	 	 return is_callable($obj);
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
	 	 	 		((is_string($obj) && is_numeric($obj)));
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
	public static function times($n, callable $iterator) {
		$accum = array();
		for ($i = 0; $i < $n; ++$i) {
			$accum[$i] = call_user_func($iterator, $i);
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
		return str_replace($entityMap['to'], $entityMap['from'], $string);
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
					call_user_func($value, $object) :
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

	/*
	 * ALIASES
	 */

	public static function for_each($obj, callable $iterator) {
		static::each($obj, $iterator);
	}
	public static function collect($obj, callable $iterator) {
		return static::map($obj, $iterator);
	}
	public static function foldl($obj, callable $iterator, $memo = null) {
		return static::reduce($obj, $iterator, $memo);
	}
	public static function inject($obj, callable $iterator, $memo = null) {
		return static::reduce($obj, $iterator, $memo);
	}
	public static function foldr($obj, callable $iterator, $memo = null) {
		return static::reduceRight($obj, $iterator, $memo);
	}
	public static function detect($obj, callable $iterator) {
		return static::find($obj, $iterator);
	}
	public static function select($obj, callable $iterator) {
		return static::filter($obj, $iterator);
	}
	public static function all($obj, callable $iterator = null) {
		return static::every($obj, $iterator);
	}
	public static function any($obj, $iterator) {
		return static::some($obj, $iterator);
	}
	public static function includes($obj, $target) {
		return static::contains($obj, $target);
	}
	public static function head($array, $n = null, $guard = false) {
		return static::first($array, $n, $guard);
	}
	public static function take($array, $n = null, $guard = false) {
		return static::first($array, $n, $guard);
	}
	public static function tail($array, $n = null, $guard = false) {
		return static::rest($array, $n, $guard);
	}
	public static function drop($array, $n = null, $guard = false) {
		return static::rest($array, $n, $guard);
	}
	public static function unique($array) {
		return static::uniq($array);
	}
}
?>