<?php
/**
 * Implementation of the webservices for eZJSCore
 *
 * @author Gaetano Giunta
 * @copyright (c) 2010 G. Giunta
 * @license code licensed under the GNU GPL 2.0: see README
 *
 * @todo add dynamically to this class all methods coming from views/fetches/operations,
 */

class ezWebservicesAPIJSCFunctions
{

    static function viewall( $params )
    {
        if ( count( $params ) < 2 )
        {
            /// @todo log warning
            return false;
        }
        $module = (string)$params[0];
        $view = (string)$params[1];
        $return_type = eZWebservicesAPIExecutor::RETURN_VARIABLES;
        $parameters = isset( $params[2] ) ? (array)$params[2] : array();
        $unordered_parameters = isset( $params[3] ) ? (array)$params[3] : array();
        $post_parameters = isset( $params[4] ) ? (array)$params[4] : array();

        $ini = ezINI::instance( 'ezwebservicesapi.ini' );
        $skipaccesscheck = ( $ini->variable( 'ws_runview', 'SkipViewAccessCheck' ) == 'enabled' );
        return eZWebservicesAPIExecutor::ezpublish_view( $module, $view, $return_type, $parameters, $unordered_parameters, $post_parameters, $skipaccesscheck );
    }

    static function fetchall( $params )
    {
        if ( count( $params ) < 3 )
        {
            /// @todo log warning
            return false;
        }
        $module = $params[0];
        $fetch = $params[1];
        $parameters = $params[2];
        $results_filter = isset( $params[3] ) ? (array)$params[3] : array();
        $encode_depth = isset( $params[4] ) ? (int)$params[4] : 1;

        return eZWebservicesAPIExecutor::ezpublish_fetch( $module, $fetch, $parameters, $results_filter, $encode_depth );
    }

    static function operationall( $params )
    {
        if ( count( $params ) < 2 )
        {
            /// @todo log warning
            return false;
        }
        $module = $params[0];
        $operation = $params[1];
        $parameters = isset( $params[2] ) ? (array)$params[2] : array();

        return eZWebservicesAPIExecutor::ezpublish_operation( $module, $operation, $parameters );
    }

    static function inspect( $params )
    {
        if ( count( $params ) < 2 )
        {
            /// @todo log warning
            return false;
        }
        array_shift( $params );
        array_shift( $params );
        return eZWebservicesAPIExecutor::ezpublish_inspect( (string)$params[0], (string)$params[1], $params );
    }

}

?>