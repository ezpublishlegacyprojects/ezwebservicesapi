<?php
/**
 *
 * @author Gaetano Giunta
 * @copyright (c) 2010 G. Giunta
 * @license code licensed under the GPL License: see README
 *
 * @todo add dynamically to this class all methods coming from views/fetches,
 * @todo implement viewall and fetchall
 */

class ezWebservicesAPIJSCFunctions
{

    static function inspect( $params )
    {
        return eZWebservicesAPIExecutor::ezpublish_inspect( $params );
    }

    static function viewall( $params )
    {
        eZDebug::writeError( 'WS ezp.viewall not yet available via ezjscore calling', __MEHOD__ );
        return false;
    }

    static function fetchall( $params )
    {
        eZDebug::writeError( 'WS ezp.fetchall not yet available via ezjscore calling', __MEHOD__ );
        return false;
    }
}

?>