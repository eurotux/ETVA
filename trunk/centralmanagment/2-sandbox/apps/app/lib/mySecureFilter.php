<?php

class MySecureFilter extends sfFilter
{
  public function execute($filterChain) {

    $context = $this->getContext();
    $request = $context->getRequest();

    $moduleName = $request->getParameter('module');
    $actionName = $request->getParameter('action');

    if ($context->getController()->actionExists($moduleName, $actionName)) {

        $action = $context->getController()->getAction(
            $moduleName,
            $actionName
        );

        if ($action->getSecurityValue('is_ssl', false) && !$request->isSecure()) {

            $secure_url = str_replace('http', 'https', $request->getUri());

            return $context->getController()->redirect($secure_url);

        } else if (!$action->getSecurityValue('is_ssl', false) && $request->isSecure()) {

            $not_secure_url = str_replace('https', 'http', $request->getUri());

            return $context->getController()->redirect($not_secure_url);
        }
    }

    $filterChain->execute();
  }
}

