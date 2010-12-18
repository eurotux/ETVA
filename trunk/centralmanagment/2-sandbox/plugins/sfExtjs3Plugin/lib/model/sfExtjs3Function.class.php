<?php
/**
 * sfExtjs3Function
 *
 * Instances of this class can render themself as JavaScript functions
 *
 */
class sfExtjs3Function
{
  protected
   $arguments,
   $content;

  /**
   * Creates a new JsObject
   *
   * @param array $arguments  the arguments that this functions should accept
   * @param mixed $content    anything that can be rendered as a string (arrays will be imploded)
   */
  public function __construct($arguments, $content)
  {
    if (!is_array($arguments))
    {
      throw new Exception('arguments should be an array');
    }

  	$this->arguments = $arguments;
  	$this->content   = $content;
  }

  /**
   * Renders this function as a js-text
   *
   * @return string the js-representation of this function
   */
  public function render()
  {
    $content = $this->content;

    // convert array to string by imploding it
    if (is_array($content))
    {
      $content = implode("\n", $content);
    }

  	$js  = 'function(';
  	$js .= implode(', ', $this->arguments);
  	$js .= ') {';
  	if ($content)
  	{
  	  $js .= "\n";
  	}
  	$js .= $content;
      if ($content)
    {
      $js .= "\n";
    }
  	$js .= '}';

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