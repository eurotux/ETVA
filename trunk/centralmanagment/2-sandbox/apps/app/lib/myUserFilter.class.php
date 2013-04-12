<?php

class myUserFilter extends sfFilter
{
    /*
     * this filter updates vnc token so prevents timeout http proxy connect for vnc applet time
     */
    public function execute($filterChain)
    {
 
        // Filters don't have direct access to the user object.
        // You will need to use the context object to get them
        $user    = $this->getContext()->getUser();
        
        if ($user->isAuthenticated()){
            $user->initVncToken();
        }
        
        // Execute next filter
        $filterChain->execute();
    }

}
