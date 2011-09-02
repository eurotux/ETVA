<?php


abstract class myBaseFormPropel extends sfFormPropel
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
          //$this->getLabelForField($field)
     //   $this->getContext()->getI18N()->__($error->getMessageFormat(), $error->getArguments());
        
        //array_push($errorArray, sprintf('<strong>%s:</strong> %s', $field, $error->getMessage()));
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
