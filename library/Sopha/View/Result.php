<?php

/**
 * Sopha - A PHP 5.x Interface to CouchDB 
 * 
 * @package    Sopha
 * @subpackage View
 * @author     Shahar Evron
 * @version    $Id$
 * @license    LICENSE.txt - New BSD License 
 */

class Sopha_View_Result implements Countable, ArrayAccess, SeekableIterator 
{
    /**
     * Possible return types
     */
    const RETURN_ARRAY     = 1;
    const RETURN_JSON      = 2;
    const RETURN_OBJECT    = 4;
    
    protected $metadata    = array();

    protected $rows        = array();
    
    protected $pointer     = 0;
    
    protected $return_type = 1;
    
    protected $doc_class   = null;
    
    public function __construct(array $result, $return = self::RETURN_ARRAY)
    {
        if (! isset($result['rows']) || ! is_array($result['rows'])) {
            require_once 'Sopha/View/Result/Exception.php';
            throw new Sopha_View_Result_Exception("Result does not seem to be a " . 
                "valid view result data");
        }
        
        $this->rows = $result['rows'];
        unset($result['rows']);
        
        $this->metadata = $result;
        
        $this->return_type = $return;
        
        if ($return == self::RETURN_JSON) {
            require_once 'Zend/Json.php';
            
        } elseif (is_string($return)) {
            require_once 'Zend/Loader.php';
            try {
                Zend_Loader::loadClass($return);
                
                // Make sure that class is a subclass of Sopha_Document
                // We do that now instead of in run-time
                $testObj = new $return;
                if (! $testObj instanceof Sopha_Document) {
                    require_once 'Sopha/View/Result/Exception.php';
                    throw new Sopha_View_Result_Exception("$return is not as subclass of " . 
                        "Sopha_Document as expected");
                }
                
                $this->doc_class = $return;
                $this->return_type = self::RETURN_OBJECT;
                
            } catch (Zend_Exception $e) {
                require_once 'Sopha/View/Result/Exception.php';
                throw new Sopha_View_Result_Exception("Unable to load class $return");
            }
            
        } elseif ($return != self::RETURN_ARRAY) {
            require_once 'Sopha/View/Result/Exception.php';
            throw new Sopha_View_Result_Exception("Invalid return type: $return");
        }
    }
    
    /**
     * SPL Countable Interface
     */
    
    /**
     * Count the number of rows in the result
     * 
     * @return integer
     */
    public function count()
    {
        return $this->metadata['total_rows'];
    }

    /**
     * SPL SeekableIterator Interface (inherits from Iterator)
     */
    
    /**
     * Get the current element
     * 
     * @return mixed
     */
    public function current()
    {
        $ret = null;
        
        if (isset($this->rows[$this->pointer])) {
            
            switch($this->return) {
                case self::RETURN_ARRAY:
                    $ret =  $this->rows[$this->pointer];
                    break;
                    
                case self::RETURN_JSON:
                    $ret =  Zend_Json::encode($this->rows[$this->pointer]);
                    break;
                    
                case self::RETURN_OBJECT:
                    $ret =  new $this->doc_class($this->rows[$this->pointer]);
                    break;
            }
                
        } else {
            throw new OutOfBoundsException('Pointer points to invalid index');
        }
        
        return $ret;
    }
    
    public function key()
    {
        return $this->pointer;
    }
    
    public function next()
    {
        $this->pointer += 1;
    }
    
    public function rewind()
    {
        $this->pointer = 0;
    }
    
    public function valid()
    {
        return isset($this->rows[$this->pointer]);
    }
    
    public function seek($index)
    {
        if (isset($this->rows[$index])) {
            $this->pointer = $index; 
        } else {
            throw new OutOfBoundsException('Invalid Seek Position');
        }
    }
    
    /**
     * SPL ArrayAccess Interface
     */
    
    public function offsetExists($offset)
    {
        return isset($this->rows[$offset]);
    }
    
    public function offsetGet($offset)
    {
        if (isset($this->rows[$offset])) {
            return $this->rows[$offset];
        } else {
            return null;
        }
    }
    
    public function offsetSet($offset, $value)
    {
        require_once 'Sopha/View/Result/Exception.php';
        throw new Sopha_View_Result_Exception("Trying to write to read-only result set");
    }
    
    public function offsetUnset($offset)
    {
        require_once 'Sopha/View/Result/Exception.php';
        throw new Sopha_View_Result_Exception("Trying to write to read-only result set");
    }
    
    /**
     * Metadata access overloading
     */
    
    public function __get($key) 
    {
        if (isset($this->metadata[$key])) {
            return $this->metadata[$key];
        } else {
            return null;
        }
    }
    
    public function __isset($key)
    {
        return isset($this->metadata[$key]);
    }
    
    public function __set($key, $value)
    {
        require_once 'Sopha/View/Result/Exception.php';
        throw new Sopha_View_Result_Exception("Trying to write to read-only result set");
    }
    
    public function __unset($key) 
    {
        require_once 'Sopha/View/Result/Exception.php';
        throw new Sopha_View_Result_Exception("Trying to write to read-only result set");
    }
}