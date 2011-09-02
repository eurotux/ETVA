<?php

/**
 * 
 * @package    centralM
 * @subpackage form
 * @author     Your name here 
 * @version    SVN: $Id: BaseForm.class.php 20147 2009-07-13 11:46:57Z FabianLange $
 */
class myBaseForm extends sfFormSymfony
{

  public function getFormattedErrors($errorSchema = null)
  {
    if (null === $errorSchema)
    {
      $errorSchema = $this->getErrorSchema();
    }

    $errorArray = array();
    foreach ($errorSchema as $field => $error)
    {
      if ($error instanceof sfValidatorErrorSchema && $nestedErrorSchema = $error->getErrors())
      {
        return $this->getFormattedErrors($nestedErrorSchema);
      }
      else
      {
        $msg = sfContext::getInstance()->getI18N()->__($error->getMessageFormat(), $error->getArguments());
        
        array_push($errorArray, sprintf('<strong>%s:</strong> %s', $field, $msg));
      }
    }

    return $errorArray;
  }


  function getLabelForField($field, $form = null)
  {
    if (null === $form)
    {
      $form = clone $this;
    }

    if ($form->offsetExists($field))
    {
      return $form[$field]->renderLabel();
    }

    foreach ($form->getEmbeddedForms() as $embeddedForm)
    {
      return $this->getLabelForField($field, $embeddedForm);
    }
  }

}
