<?php
/**
 * Class that implements the functions exposed as webservices, plus some helper stuff.
 * The logic exposed as webservices is stored here to make it available both via
 * ezjscore and via ggwebservices calls (i.e. independent of protocol).
 *
 * @author G. Giunta
 * @version $Id: ggezwebservicesclient.php 102 2009-09-02 09:03:34Z gg $
 * @copyright (C) G. Giunta 2010
 * @license code licensed under the GNU GPL 2.0: see README
 *
 * @todo add to ezpublish_runview a param to enable/disable following redirects/reruns?
 */

class eZWebservicesAPIExecutor
{

    const RETURN_VARIABLES = 1;
    const RETURN_RESULT = 2;

    const ERR_NOMODULE = -1;
    const ERR_NOVIEW = -2;
    const ERR_MODULEFAILED = -3;
    const ERR_FETCHFAILED = -4;
    const ERR_NOTIMPLEMENTED = -99;

    /*** WEBSERVICES ***/

    /**
    * Run an ezp module view, encapsulate results in the reponse.
    * Extra checking on current user perms to execute the view are controlled via ezwebservicesapi.ini
    * @return array|ggWebservicesFault
    *
    * @todo add to options some more filters:
    *       - on vars we want
    *       - on per-var attr filter and encoding level
    */
    static function ezpublish_view( $module, $view, $options = array(), $parameters = array(), $unordered_parameters = array(), $post_parameters = array(), $skipaccesscheck = true )
    {
        $options = array_merge( array(
            'return_type' => self::RETURN_VARIABLES,
            'encoding_depth' => 2,
            ), $options );
        // inject back into POST the params we received
        // this should work both for modules that use action parameters and for
        // modules that use eZHTTPTool to check for POST
        foreach ( $post_parameters as $name => $value )
        {
            eZHTTPTool::setPostVariable( $name, $value );
        }

        $module = eZModule::exists( $module );
        if ( $module instanceof eZModule )
        {
            if ( !$skipaccesscheck )
            {
                /// @todo 1st check on module allowed: see accessAllowed in index.app
                return new ggWebservicesFault( self::ERR_NOTIMPLEMENTED, 'Checking module access perms is not supported yet. See ezwebservicesapi.ini for more details' );
            }

            // this check is done in $module->run later, but doing it on our own
            // allows us to return a specific error msg
            if ( array_key_exists( $view, $module->attribute( 'views' ) ) )
            {
                /// @todo 2nd check on module allowed: see $policyCheckRequired in index.php

                /// @todo find a way to inject into the module execution process to avoid running the template
                /// @todo wrap this into an output buffer to avoid stuff spilling into the result
                $moduleResult = $module->run(
                    $view,
                    $parameters,
                    array(
                        /*'SiteAccessAllowed' => false,
                        'SiteAccessName' => $access['name'] */ ),
                    $unordered_parameters );

                /// @todo verify execution of hooks: is it ok or ko?

                $moduleExitStatus = $module->exitStatus();
                if ( $moduleExitStatus == eZModule::STATUS_FAILED )
                {
                    /// @todo recover error code/msg from module (are there any or is it only used by error module?)
                    return new ggWebservicesFault( self::ERR_MODULEFAILED, 'Module execution failed' );
                }
                else if ( $moduleExitStatus == eZModule::STATUS_REDIRECT )
                {
                    /// @todo run next module?
                    return new ggWebservicesFault( self::ERR_NOTIMPLEMENTED, 'Module redirects. Not supported yet' );
                }
                else if ( $moduleExitStatus == eZModule::STATUS_RERUN )
                {
                    /// @todo run next module?
                    return new ggWebservicesFault( self::ERR_NOTIMPLEMENTED, 'Module reruns. Not supported yet' );
                }
                else
                {
                    $result = array();
                    if ( $options['return_type'] & 1 )
                    {
                        $ini = ezINI::instance( 'ezwebservicesapi.ini' );
                        $maxdepth = $ini->variable( 'GeneralSettings', 'MaxRecursionDepth' );
                        // recover the variables that have been set to the template
                        $tpl = eZTemplate::factory();
                        // transform ezpo objects to arrays
                        foreach( $tpl->Variables[''] as $name => $value )
                        {
                            $result[$name] = is_array( $value ) ? ezPOInspector::to_array( $value, $options['encoding_depth']+1 ) : ezPOInspector::to_array( $value, $options['encoding_depth'] );
                        }
                    }
                    // return template results and accessory info
                    if ( $options['return_type'] & 2 )
                    {
                        $result = array_merge( $result, $moduleResult );
                    }
                    return $result;
                }
            }
            else
            {
                eZDebug::writeWarning( "View '$view' not found", __METHOD__ );
                return new ggWebservicesFault( self::ERR_NOVIEW, "View '$view' not found" );
            }

        }
        else
        {
            eZDebug::writeWarning( "Module '$module' not found", __METHOD__ );
            return new ggWebservicesFault( self::ERR_NOMODULE, "Module '$module' not found" );
        }

    }

    /**
    * Run an ezp module fetch function, encapsulate results in the reponse.
    * @return mixed
    */
    static function ezpublish_fetch( $module, $fetch, $parameters = array(), $results_filter = array(), $encode_depth = 1 )
    {
        // To discriminate better between a missing module, missing fetch function,
        // etc, we would need to copy and paste here much code from the classes
        // eZFunctionHandler and eZModuleFunctionInfo.
        // We leave that as exerices for the reader...
        $results = eZFunctionHandler::execute( $module, $fetch, $parameters );
        if ( $results !== null )
        {
            if( is_array( $results ) )
            {
                foreach( $results as $key => $val )
                {
                    $results[$key] = ezPOInspector::to_array( $val, $encode_depth, $results_filter );
                }
                return $results;
            }
            else
            {
                // either scalar, or single ezpo object
                return ezPOInspector::to_array( $results, $encode_depth, $results_filter );
            }
        }
        // logging of error that led to a null here is already done by called code
        return new ggWebservicesFault( self::ERR_FETCHFAILED, "Failed executing fetch function $module/$fetch" );
    }

    /**
     * Returns an array, in ezPOInspector::objInspect format, describing the
     * desired persistent object, as defined by its class and id - or the single
     * po attribute
     * Example calls:
     * eZWebservicesAPIExecutor::inspect( array( 'ezcontentobjecttreenode', 2 ) )
     * eZWebservicesAPIExecutor::inspect( array( 'ezcontentobjecttreenode', 2, 'data_map', 'name', 'contentclass_attribute' ) )
     *
     * @param string $classname
     * @param string $objid (an integer or a suite of integers imploded with ',' depending on the obj definition)
     * @param string attribute
     */
    static function ezpublish_inspect( $classname, $keys, $params=array() )
    {

        $classdef = ezPODocScanner::definition( $classname );
        if ( !$classdef || !$classdef['persistent'] )
        {
            /// @todo log warning
            return false;
        }
        $classname = ezPODocScanner::findClassNameGivenLowerCaseName( $classname );
        if ( !$classname )
        {
            /// @todo log warning
            return false;
        }
        if ( !method_exists( $classname, 'fetch' ) )
        {
            /// @todo log warning
            return false;
        }
        if ( count( $classdef['keys'] ) > 1 )
        {
            /** Use hardcoded knowledge to invoke fetch functions, as some do not
             take input parameters in correct order.
             * Nb: we assume we received the correct number of key elements
             */
            $keys = explode( ',', $keys );
            switch( $classdef )
            {
                case 'eZContentObjectAttribute':
                    // declares 4 keys whereas it only has 2 in its class def!
                    /// @todo support calls using only 2 items in the keys?
                    $obj = call_user_func_array( "$classname::fetch", array( $keys[0], $keys[2] ) );
                    break;
                case 'eZContentClass':
                case 'eZContentClassAttribute':
                    // add a stupid 'asobject' 2nd param in the fetch
                    $obj = call_user_func_array( "$classname::fetch", array( $keys[0], true, $keys[1] ) );
                    break;
                case 'eZBinaryFile':
                case 'eZContentClassClassGroup':
                case 'eZMedia':
                    // simple case: fetch and keys definition agree
                    $obj = call_user_func_array( "$classname::fetch", $keys );
                    break;
                default:
                    return false;
            }

        }
        else
        {
            $obj = $classname::fetch( $keys );
        }
        if ( !$obj )
        {
            return false;
        }

        // dig down the attribute chain
        for( $i = 0; $i < count( $params ); $i++ )
        {
            if ( is_array( $obj ) )
            {
                if ( isset( $obj[$params[$i]] ) )
                {
                    $obj = $obj[$params[$i]];

                }
                else
                {
                    /// @todo log warning
                    return false;
                }
            }
            else
            {
                if ( !method_exists( $obj, 'attribute' ) )
                {
                    /// @todo log warning
                    return false;
                }
                $obj = $obj->attribute( (string) $params[$i] );
            }
        }

        if ( !class_exists( 'ezPOInspector' ) )
        {
            eZDebug::writeError( 'Cannot execute ws ezp.inspect: php class ezPOInspector not available. Check that extension ezpersistentobject_inspector is enabled', __METHOD__ );
            return false;
        }
        return ezPOInspector::objInspect( $obj );
    }

    /*** HELPERS ***/

    /**
    * Return the filename (incl. full path) of the file used to cache the
    * dynamically-generated definition of webservices
    */
    static function initializeFileName()
    {
        $sys = eZSys::instance();
        return $sys->cacheDirectory() . '/ezwebservicesapi/initialize.php';
    }

    /**
     * Create definition of ws used for views, fetchfunctions
     * @return string the php code for inclusion in initialize.php
     *
     * @todo parse view files to spot direct usage of post vars
     */
    static function generateInitializeFile( $doviews=true, $dofetches=true )
    {
        $vws = '';
        $fws = '';
        $ini = ezINI::instance( 'ezwebservicesapi.ini' );
        $skip = $ini->variable( 'ws_runview', 'SkipViews' );
        foreach( eZModuleScanner::getModuleList() as $modulename => $path )
        {

            /// @todo !important optimize: do not load $module, include directly module.php
            $module = eZModule::exists( $modulename );
            if ( $module instanceof eZModule )
            {
                if ( $doviews )
                {
                    if ( in_array( $modulename, $skip ) )
                    {
                       continue;
                    }
                    foreach( $module->attribute( 'views' ) as $viewname => $view )
                    {
                        if ( in_array( "$modulename/$viewname", $skip ) )
                        {
                            continue;
                        }

                        $view = array_merge( array(
                            'params' => array(),
                            'unordered_params' => array(),
                            'post_action_parameters' => array(),
                            'single_post_actions' => array(),
                            ), $view );
                        $param_array = array( "'struct'" ); // 1st param: options
                        $help = 'struct $options';
                        foreach( $view['params'] as $p ) // positional params
                        {
                            $param_array[] = "'mixed'";
                            $help .= ", mixed $p";
                        }
                        if ( count( $view['unordered_params'] ) )
                        {
                            $param_array[] = "'struct'"; // unordered params
                            /// @todo add description of params into help text
                            $help .= ', struct $unordered_parameters';
                        }
                        // POST params are always added, just in case they are used by module code
                        /*if ( count( $view['post_action_parameters'] ) > 0 || count( $view['single_post_actions'] ) > 0 )
                        {
                            $param_array[] = "'struct'"; // post params
                            /// @todo add description of params into help text
                            $help .= ', struct $post_parameters';
                        }*/
                        //$p_s = count( $view['unordered'] ) ? implode( ', ', array_fill( 0, count( $view['unordered'], "'mixed'" ) ) ) : '';
                        $vws .= "
\$server->registerFunction( 'ezp.view.$modulename.$viewname', array( " . implode( ', ', $param_array ) . " ), 'struct', 'Executes the view $modulename/$viewname. Params: $help. See http://ez.no/doc/ez_publish/technical_manual/4_x/reference/modules/$modulename/views/$view' );
\$server->registerFunction( 'ezp.view.$modulename.$viewname', array( " . implode( ', ', $param_array ) . ", 'struct' ), 'struct', 'Executes the view $modulename/$viewname. Params: $help, struct \$post_parameters. See http://ez.no/doc/ez_publish/technical_manual/4_x/reference/modules/$modulename/views/$view' );
function ezp_view_{$modulename}_$viewname( \$parameters ) { return eZWebservicesAPIExecutor::ezpublish_view( '$modulename', '$viewname', \$parameters ); }
";
                    }
                }
                if ( $dofetches )
                {
                    $functions = eZFunctionHandler::moduleFunctionInfo( $modulename );
                    foreach( $functions->FunctionList as $function => $dummy )
                    {
                        $fws .= "
\$server->registerFunction( 'ezp.fetch.$modulename.$function', array( 'struct' ), 'mixed', 'Runs the fetch function $modulename/$function. See: http://ez.no/doc/ez_publish/technical_manual/4_x/reference/modules/$modulename/fetch_functions/$function' );
\$server->registerFunction( 'ezp.fetch.$modulename.$function', array( 'struct', 'array' ), 'mixed', 'Runs the fetch function $modulename/$function, filtering output columns. See: http://ez.no/doc/ez_publish/technical_manual/4_x/reference/modules/$modulename/fetch_functions/$function' );
\$server->registerFunction( 'ezp.fetch.$modulename.$function', array( 'struct', 'array', 'int' ), 'mixed', 'Runs the fetch function $modulename/$function, filtering output columns and limiting encoding depth. See: http://ez.no/doc/ez_publish/technical_manual/4_x/reference/modules/$modulename/fetch_functions/$function' );
function ezp_fetch_{$modulename}_$function( \$parameters, \$results_filter=array(), \$encode_depth=1 ) { return eZWebservicesAPIExecutor::ezpublish_fetch( '$modulename', '$function', \$parameters, \$results_filter, \$encode_depth ); }
";
                    }
                }
            }
        }

        return "\n// EZPUBLISH VIEWS\n" . $vws . "\n// EZPUBLISH FETCHES\n" . $fws;
    }

}

?>