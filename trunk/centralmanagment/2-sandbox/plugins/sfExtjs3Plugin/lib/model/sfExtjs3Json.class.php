<?php
/**
 * sfExtjs3Json
 *
 * Instances of this class can render themself as JavaScriptObjectNotation (definitions and instances)
 *
 */
class sfExtjs3Json
{
	const ITEM_SEPARATOR = ' : ';

	protected
	 $objectName = '',
	 $items = array();

	/**
	 * Creates a new JsonObject
	 *
   * @param array $items       an associative array of key-value paris of the json-object
	 * @param string $objectName the name of the new class to be created (can be preceded with dotted namespace)
	 */
	public function __construct($items, $objectName = '')
	{
		$this->objectName = trim($objectName);

		if (!is_array($items))
		{
			throw new Exception('argument "functions" should be an array');
		}
		foreach ($items as $key => $value)
		{
			$this->addItem($key, $value);
		}
	}

	public function getItems()
	{
		return $this->items;
	}

	/**
	 * Adds a function to the extjs-object
	 *
	 * @param string $key   the name for belonging to this value
	 * @param mixed  $value an object that can be renderered to string
	 */
	public function addItem($key, $value)
	{
		if (isset($this->items[$key]))
		{
			throw new Exception('The item "'.$key.'" is already defined for object "'.$this->objectName.'".');
		}

		$this->items[$key] = $value;
	}

	/**
	 * returns the name of this object
	 *
	 */
	public function getName()
	{
		return $this->objectName;
	}

	protected function isAssociative($arr)
  {
    return array_keys($arr) != range(0, count($arr) - 1);
  }

	protected function renderValue($value)
	{
	  $js  = '';

	  if (is_array($value))
	  {
	    $js .= '[';

      $items = $value;
      if (count($items))
      {
        list($firstKey, $firstValue) = each($items);

        if ($this->isAssociative($value))
        {
          $js .= "\n".$firstKey.self::ITEM_SEPARATOR;
        }
        $js .= $this->renderValue($firstValue);

        while (list($itemKey, $itemValue) = each($items))
        {
          $js .= ",\n";
          if ($this->isAssociative($value))
          {
            $js .= $itemKey.self::ITEM_SEPARATOR;
          }
          $js .= $this->renderValue($itemValue);
        }
      }

	    $js .= ']';
	  }
	  elseif (is_bool($value))
	  {
	    $js .= $value ? 'true' : 'false';
	  }
	  else
	  {
	    $js .= $value;
	  }

	  return $js;
	}

	/**
	 * renders the object
	 *
	 * @return string
	 */
	public function render()
	{
	  $js = '';

	  if ($this->getName())
	  {
	    $js .= $this->getName().' = ';
	  }

    $js .= '{';

    $items = $this->getItems();
    if (count($items))
    {
      $js .= "\n";

      list($firstKey, $firstValue) = each($items);
      $js .= $firstKey.self::ITEM_SEPARATOR.$this->renderValue($firstValue);

      while (list($key, $value) = each($items))
      {
        $js .= ",\n".$key.self::ITEM_SEPARATOR.$this->renderValue($value);
      }

      $js .= "\n";
    }
		$js .= '}';

    if ($this->getName())
    {
      $js .= ";";
    }

		return $js;
	}

	/**
	 * @see render()
	 */
  public function __toString()
  {
    return $this->render();
  }

}

?>