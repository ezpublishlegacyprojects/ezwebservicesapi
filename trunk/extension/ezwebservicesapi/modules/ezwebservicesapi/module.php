<?php
/**
 * An 'empty' module used to inject some new permissions into the system
 *
 * @version $Id$
 * @author G. Giunta
 * @copyright (C) G. Giunta 2010
 * @license code licensed under the GNU GPL 2.0: see README
 */

$ViewList = array();

$FunctionList = array(
    'execute' => array(
        'Webservices' => array(
            'name'=> 'Webservices',
            'values'=> array(),
            'path' => '../extension/ezwebservicesapi/classes/', // starts within 'kernel'...
            'file' => 'ezwebservicesapiexecutor.php',
            'class' => 'eZWebservicesAPIExecutor',
            'function' => 'getMethodsList',
            'parameter' => array() ),
        'SiteAccess' => array(
            'name'=> 'SiteAccess',
            'values'=> array(),
            'path' => 'classes/',
            'file' => 'ezsiteaccess.php',
            'class' => 'eZSiteAccess',
            'function' => 'siteAccessList',
            'parameter' => array() )
        )
    );

?>