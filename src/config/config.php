<?php


return array(

    /*
     |--------------------------------------------------------------------------
     | StatCounter Username
     |--------------------------------------------------------------------------
     |
     | Here you will need to provide the username that you use to log into
     | StatCounter. This will be used to access your project stats via the 
     | StatCounter API. You will need to set the password value as well, which
     | is set below this one.
     |
     */

    'username' => 'your-username-here',

    /*
     |--------------------------------------------------------------------------
     | StatCounter API Password
     |--------------------------------------------------------------------------
     |
     | Here you will need to provide the API password for StatCounter. This is
     | NOT the same as your login password. You may set one here:
     | 
     |   http://api.statcounter.com/password
     |
     | This will be used along with the above username to authenticate the 
     | queries sent to the StatCounter API.
     |
     */
    
    'api-password' => 'your-password-here',
    
    /*
     |--------------------------------------------------------------------------
     | Default StatCounter Project Name
     |--------------------------------------------------------------------------
     |
     | Here you may specify which of the projects, if you have multiple, below 
     | you wish to use as your default project for all StatCounter queries. Of 
     | course you may use many projects at once using the Stat library.
     |
     */
  
    'default' => 'your-project-name',

    /*
     |--------------------------------------------------------------------------
     | StatCounter Projects
     |--------------------------------------------------------------------------
     |
     | The projects you wish manage with the Stat library, each being 
     | represented by a name of your choosing (i.e. website's name) and the 
     | project ID used by StatCounter. You will use the name when specifying 
     | projects other than the default project.
     |
     | StatCounter's project ID can be found in your project's config section 
     | under the 'Security Code' page. This is needed for both the API queries 
     | and the tracker display.
     |
     */
    
    'projects' => [
    
        'your-project-name' => 'your-project-id-here',
    
    ],
    
    /*
     |--------------------------------------------------------------------------
     | StatCounter Project Security Code
     |--------------------------------------------------------------------------
     |
     | The security codes array should match the projects array, or at least
     | contain the project name/security code pair for the projects your wish
     | to display trackers for.
     | 
     | The security code is used in the tracker by StatCounter to validate your
     | tracker's page hits. You can find it in your project's config section 
     | under 'Security Code'.
     |
     */
    
    'security-codes' => [
    
        'your-project-name' => 'your-project-security-code-here'
    
    ],
    
);