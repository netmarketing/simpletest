<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id$
     */
    /**
     * does type matter
     */
    define('TYPE_MATTERS', true);
    
    /**
     *    Displays variables as text and does diffs.
	 *	  @package	SimpleTest
	 *	  @subpackage	UnitTester
     */
    class SimpleDumper {
        
        /**
         *    Do nothing constructor.
         */
        function SimpleDumper() {
        }
        
        /**
         *    Renders a variable in a shorter form than print_r().
         *    @param mixed $value      Variable to render as a string.
         *    @return string           Human readable string form.
         *    @access public
         */
        function describeValue($value) {
            $type = $this->getType($value);
            switch($type) {
                case "Null":
                    return "NULL";
                case "Boolean":
                    return "Boolean: " . ($value ? "true" : "false");
                case "Array":
                    return "Array: " . count($value) . " items";
                case "Object":
                    return "Object: of " . get_class($value);
                case "String":
                    return "String: " . $this->clipString($value, 100);
                default:
                    return "$type: $value";
            }
            return "Unknown";
        }
        
        /**
         *    Gets the string representation of a type.
         *    @param mixed $value    Variable to check against.
         *    @return string         Type.
         *    @access public
         */
        function getType($value) {
            if (! isset($value)) {
                return "Null";
            } elseif (is_bool($value)) {
                return "Boolean";
            } elseif (is_string($value)) {
                return "String";
            } elseif (is_integer($value)) {
                return "Integer";
            } elseif (is_float($value)) {
                return "Float";
            } elseif (is_array($value)) {
                return "Array";
            } elseif (is_resource($value)) {
                return "Resource";
            } elseif (is_object($value)) {
                return "Object";
            }
            return "Unknown";
        }

        /**
         *    Creates a human readable description of the
         *    difference between two variables. Uses a
         *    dynamic call.
         *    @param mixed $first        First variable.
         *    @param mixed $second       Value to compare with.
         *    @param boolean $identical  If true then type anomolies count.
         *    @return string             Description of difference.
         *    @access public
         */
        function describeDifference($first, $second, $identical = false) {
            if ($identical) {
                if (! $this->_isTypeMatch($first, $second)) {
                    return "with type mismatch as [" . $this->describeValue($first) .
                        "] does not match [" . $this->describeValue($second) . "]";
                }
            }
            $type = $this->getType($first);
            if ($type == "Unknown") {
                return "with unknown type";
            }
            $method = '_describe' . $type . 'Difference';
            return $this->$method($first, $second, $identical);
        }
        
        /**
         *    Tests to see if types match.
         *    @param mixed $first        First variable.
         *    @param mixed $second       Value to compare with.
         *    @return boolean            True if matches.
         *    @access private
         */
        function _isTypeMatch($first, $second) {
            return ($this->getType($first) == $this->getType($second));
        }

        /**
         *    Clips a string to a maximum length.
         *    @param string $value         String to truncate.
         *    @param integer $size         Minimum string size to show.
         *    @param integer $position     Centre of string section.
         *    @return string               Shortened version.
         *    @access public
         */
        function clipString($value, $size, $position = 0) {
            $length = strlen($value);
            if ($length <= $size) {
                return $value;
            }
            $position = min($position, $length);
            $start = ($size/2 > $position ? 0 : $position - $size/2);
            if ($start + $size > $length) {
                $start = $length - $size;
            }
            $value = substr($value, $start, $size);
            return ($start > 0 ? "..." : "") . $value . ($start + $size < $length ? "..." : "");
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two variables. The minimal
         *    version.
         *    @param null $first          First value.
         *    @param mixed $second        Value to compare with.
         *    @return string              Human readable description.
         *    @access private
         */
        function _describeGenericDifference($first, $second) {
            return "as [" . $this->describeValue($first) .
                    "] does not match [" .
                    $this->describeValue($second) . "]";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between a null and another variable.
         *    @param null $first          First null.
         *    @param mixed $second        Null to compare with.
         *    @param boolean $identical   If true then type anomolies count.
         *    @return string              Human readable description.
         *    @access private
         */
        function _describeNullDifference($first, $second, $identical) {
            return $this->_describeGenericDifference($first, $second);
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between a boolean and another variable.
         *    @param boolean $first       First boolean.
         *    @param mixed $second        Boolean to compare with.
         *    @param boolean $identical   If true then type anomolies count.
         *    @return string              Human readable description.
         *    @access private
         */
        function _describeBooleanDifference($first, $second, $identical) {
            return $this->_describeGenericDifference($first, $second);
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between a string and another variable.
         *    @param string $first        First string.
         *    @param mixed $second        String to compare with.
         *    @param boolean $identical   If true then type anomolies count.
         *    @return string              Human readable description.
         *    @access private
         */
        function _describeStringDifference($first, $second, $identical) {
            if (is_object($second) || is_array($second)) {
                return $this->_describeGenericDifference($first, $second);
            }
            $position = $this->_stringDiffersAt($first, $second);
            $message = "at character $position";
            $message .= " with [" .
                    $this->clipString($first, 100, $position) . "] and [" .
                    $this->clipString($second, 100, $position) . "]";
            return $message;
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between an integer and another variable.
         *    @param integer $first       First number.
         *    @param mixed $second        Number to compare with.
         *    @param boolean $identical   If true then type anomolies count.
         *    @return string              Human readable description.
         *    @access private
         */
        function _describeIntegerDifference($first, $second, $identical) {
            if (is_object($second) || is_array($second)) {
                return $this->_describeGenericDifference($first, $second);
            }
            return "because [" . $this->describeValue($first) .
                    "] differs from [" .
                    $this->describeValue($second) . "] by " .
                    abs($first - $second);
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two floating point numbers.
         *    @param float $first         First float.
         *    @param mixed $second        Float to compare with.
         *    @param boolean $identical   If true then type anomolies count.
         *    @return string              Human readable description.
         *    @access private
         */
        function _describeFloatDifference($first, $second, $identical) {
            if (is_object($second) || is_array($second)) {
                return $this->_describeGenericDifference($first, $second);
            }
            return "because " . $this->describeValue($first) .
                    "] differs from [" .
                    $this->describeValue($second) . "]";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two arrays.
         *    @param array $first         First array.
         *    @param mixed $second        Array to compare with.
         *    @param boolean $identical   If true then type anomolies count.
         *    @return string              Human readable description.
         *    @access private
         */
        function _describeArrayDifference($first, $second, $identical) {
            if (! is_array($second)) {
                return $this->_describeGenericDifference($first, $second);
            }
            if (array_keys($first) != array_keys($second)) {
                return "as key list [" .
                        implode(", ", array_keys($first)) . "] does not match key list [" .
                        implode(", ", array_keys($second)) . "]";
            }
            foreach (array_keys($first) as $key) {
                if ($identical && ($first[$key] === $second[$key])) {
                    continue;
                }
                if (! $identical && ($first[$key] == $second[$key])) {
                    continue;
                }
                return "with member [$key] " . $this->describeDifference(
                        $first[$key],
                        $second[$key],
                        $identical);
            }
            return "";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between a resource and another variable.
         *    @param resource $first       First resource.
         *    @param mixed $second         Resource to compare with.
         *    @param boolean $identical    If true then type anomolies count.
         *    @return string              Human readable description.
         *    @access private
         */
        function _describeResourceDifference($first, $second, $identical) {
            return $this->_describeGenericDifference($first, $second);
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two objects.
         *    @param object $first        First object.
         *    @param mixed $second        Object to compare with.
         *    @param boolean $identical   If true then type anomolies count.
         *    @return string              Human readable description.
         *    @access private
         */
        function _describeObjectDifference($first, $second, $identical) {
            if (! is_object($second)) {
                return $this->_describeGenericDifference($first, $second);
            }
            return $this->_describeArrayDifference(
                    get_object_vars($first),
                    get_object_vars($second),
                    $identical);
        }
        
        /**
         *    Find the first character position that differs
         *    in two strings by binary chop.
         *    @param string $first        First string.
         *    @param string $second       String to compare with.
         *    @return integer             Position of first differing
         *                                character.
         *    @access private
         */
        function _stringDiffersAt($first, $second) {
            if (! $first || ! $second) {
                return 0;
            }
            if (strlen($first) < strlen($second)) {
                list($first, $second) = array($second, $first);
            }
            $position = 0;
            $step = strlen($first);
            while ($step > 1) {
                $step = (integer)(($step + 1)/2);
                if (strncmp($first, $second, $position + $step) == 0) {
                    $position += $step;
                }
            }
            return $position;
        }
        
        /**
         *    Sends a formatted dump of a variable to a string.
         *    @param mixed $variable    Variable to display.
         *    @return string            Output from print_r().
         *    @access public
         *    @static
         */
        function dump($variable) {
            ob_start();
            print_r($variable);
            $formatted = ob_get_contents();
            ob_end_clean();
            return $formatted;
        }
        
        /**
         *    Extracts the last assertion that was not within
         *    Simpletest itself. The name must start with "assert".
         *    @param array $stack      List of stack frames.
         *    @param string $format    String formatting.
         *    @param string $prefix    Prefix of method to search for.
         *    @access public
         *    @static
         */
        function getFormattedAssertionLine($stack, $format = '%d', $prefix = 'assert') {
            foreach ($stack as $frame) {
                if (substr(@dirname($frame['file']), -10) == 'simpletest') {
                    continue;
                }
                if (strncmp($frame['function'], $prefix, strlen($prefix)) == 0) {
                    return sprintf($format, $frame['line']);
                }
            }
            return '';
        }
    }
?>