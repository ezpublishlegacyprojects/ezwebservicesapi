<?php
/**
 * Implementation of the webservices for eZJSCore
 *
 * @version $Id$
 * @author G. Giunta
 * @copyright (C) G. Giunta 2010
 * @license code licensed under the GNU GPL 2.0: see README
 *
 * @todo add dynamically to this class all methods coming from views/fetches/operations,
 */

class ezWebservicesAPIJSCFunctions
{

    static function view( $params )
    {
        if ( count( $params ) < 2 )
        {
            /// @todo log warning
            throw new Exception( 'Missing parameters' );
        }
        $module = (string)$params[0];
        $view = (string)$params[1];

        // check perms using our own set of access functions
        $user = eZUser::currentUser();
        $access = eZWebservicesAPIExecutor::checkAccess( "view_{$module}_$view", $user );
        if ( !$access )
        {
            eZDebug::writeWarning( "Unauthorized access to ws view_{$module}_$view attempted. User: ". $user->attribute( 'contentobject_id' ), __METHOD__ );
            throw new Exception( 'Insufficient permissions' );
        }

        // we do hand-decoding from json here, since ezjscore has no such capability of its own
        // (unless we analyze $_POST and set on std param names)
        $parameters = isset( $params[2] ) ? json_decode( $params[2], true ) : array();
        $unordered_parameters = isset( $params[3] ) ? json_decode( $params[3], true ) : array();
        $post_parameters = isset( $params[4] ) ? json_decode( $params[4], true ) : array();

        $ini = ezINI::instance( 'ezwebservicesapi.ini' );
        $skipaccesscheck = ( $ini->variable( 'ws_runview', 'SkipViewAccessCheck' ) == 'enabled' );
        return eZWebservicesAPIExecutor::ezpublish_view( $module, $view, $parameters, $unordered_parameters, $post_parameters, $skipaccesscheck );
    }

    static function fetch( $params )
    {
        if ( count( $params ) < 3 )
        {
            /// @todo log warning
            throw new Exception( 'Missing parameters' );
        }
        $module = $params[0];
        $fetch = $params[1];

        // check perms using our own set of access functions
        $user = eZUser::currentUser();
        $access = eZWebservicesAPIExecutor::checkAccess( "fetch_{$module}_$fetch", $user );
        if ( !$access )
        {
            eZDebug::writeWarning( "Unauthorized access to ws fetch_{$module}_$fetch attempted. User: ". $user->attribute( 'contentobject_id' ), __METHOD__ );
            throw new Exception( 'Insufficient permissions' );
        }

        // we do hand-decoding from json here, since ezjscore has no such capability of its own
        // (unless we analyze $_POST and set on std param names)
        $parameters = json_decode( $params[2], true );
        $results_filter = isset( $params[3] ) ? json_decode( $params[3], true ) : array();
        $encode_depth = isset( $params[4] ) ? (int)$params[4] : 1;

        return eZWebservicesAPIExecutor::ezpublish_fetch( $module, $fetch, $parameters, $results_filter, $encode_depth );
    }

    static function operation( $params )
    {
        if ( count( $params ) < 2 )
        {
            /// @todo log warning
            throw new Exception( 'Missing parameters' );
        }
        $module = $params[0];
        $operation = $params[1];

        // check perms using our own set of access functions
        $user = eZUser::currentUser();
        $access = eZWebservicesAPIExecutor::checkAccess( "operation_{$module}_$operation", $user );
        if ( !$access )
        {
            eZDebug::writeWarning( "Unauthorized access to ws operation_{$module}_$operation attempted. User: ". $user->attribute( 'contentobject_id' ), __METHOD__ );
            throw new Exception( 'Insufficient permissions' );
        }

        // we do hand-decoding from json here, since ezjscore has no such capability of its own
        $parameters = isset( $params[2] ) ? json_decode( $params[2], true ) : array();

        return eZWebservicesAPIExecutor::ezpublish_operation( $module, $operation, $parameters );
    }

    static function inspect( $params )
    {
        if ( count( $params ) < 2 )
        {
            /// @todo log warning
            throw new Exception( 'Missing parameters' );
        }

        // check perms using our own set of access functions
        $user = eZUser::currentUser();
        $access = eZWebservicesAPIExecutor::checkAccess( 'inspect', $user );
        if ( !$access )
        {
            eZDebug::writeWarning( "Unauthorized access to ws inspect attempted. User: ". $user->attribute( 'contentobject_id' ), __METHOD__ );
            throw new Exception( 'Insufficient permissions' );
        }
        $class = (string)array_shift( $params );
        $keys = (string)array_shift( $params );
        return eZWebservicesAPIExecutor::ezpublish_inspect( $class, $keys, $params );
    }

}

?>