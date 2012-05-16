<?php
class myVersionFilter extends sfFilter
{
    public function execute ($filterChain){
        
        // context variables
        $context    = $this->getContext();
        $user       = $context->getUser();
        $request    = $context->getRequest();
        $uri        = $request->getUri();

        // Code to execute before the action execution
        // return $this->getContext()->getController()->forward('sfGuardAuth', 'updatesys');

        //only run once per request
         
        $find = 'sfGuardAuth/updatesys';
         
        // perform the search
        $position = strpos($uri, $find);
         
//        if ($position === false)
//            error_log("Not found");
//        else
//            error_log("Match found at location $position");


        if( $this->isFirstCall() && $position === false){
            if(update::checkDbVersion() != "OK" && $user->isAuthenticated()){
    
                //deauthenticate user and redirect him
//                $context->getUser()->signOut();
//                $filterChain->execute();
                return $context->getController()->redirect('sfGuardAuth/updatesys');
//                return $context->getController()->forward('sfGuardAuth','updatesys');
            }
        }

        // Execute next filter in the chain
        $filterChain->execute();
    } 
}

?>
