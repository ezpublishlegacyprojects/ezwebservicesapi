<?php
/**
 * Define the webservices exposed by this extension and their implementation
 *
 * @author G. Giunta
 * @version $Id: ggezwebservicesclient.php 102 2009-09-02 09:03:34Z gg $
 * @copyright (C) G. Giunta 2010
 * @license code licensed under the GNU GPL 2.0: see README
 */

// The ws ezp.viewall has multiple signatures
$server->registerFunction(
    'ezp.viewall', // name of exposed webservice AND php function at the same time
    array( 'string', 'string' ),  // input params array. Keys are not really used, as param validation is positional. Use null instead of an array to avoid type validation
    'struct', // type of return value
    'Runs any eZ Publish module/view. Params: string $module, string $view' );
$server->registerFunction(
    'ezp.viewall',
    array( 'string', 'string', 'struct' ),
    'struct',
    'Runs any eZ Publish module/view. Params: string $module, string $view, struct $options' );
$server->registerFunction(
    'ezp.viewall',
    array( 'string', 'string', 'struct', 'array' ),
    'struct',
    'Runs any eZ Publish module/view. Params: string $module, string $view, struct $options, array $parameters' );
$server->registerFunction(
    'ezp.viewall',
    array( 'string', 'string', 'struct', 'array', 'struct' ),
    'struct',
    'Runs any eZ Publish module/view. Params: string $module, string $view, struct $options, array $parameters, struct $unordered_parameters' );
$server->registerFunction(
    'ezp.viewall',
    array( 'string', 'string', 'struct', 'array', 'struct', 'struct' ),
    'struct',
    'Runs any eZ Publish module/view. Params: string $module, string $view, struct $options, array $parameters, struct $unordered_parameters, struct $post_parameters' );


$server->registerFunction(
    'ezp.fetchall',
    array( 'string', 'string', 'struct' ),
    'struct', // type of return value
    'Runs any eZ Publish fetch function. Params: string $module, string $fetch_function, struct $fetch_parameters' );
$server->registerFunction(
    'ezp.fetchall',
    array( 'string', 'string', 'struct', 'array' ),
    'struct', // type of return value
    'Runs any eZ Publish fetch function. Params: string $module, string $fetch_function, struct $fetch_parameters, array $results_filter' );
$server->registerFunction(
    'ezp.fetchall',
    array( 'string', 'string', 'struct', 'array', 'int' ),
    'struct', // type of return value
    'Runs any eZ Publish fetch function. Params: string $module, string $fetch_function, struct $fetch_parameters, array $results_filter, int $encode_depth' );


$server->registerFunction(
    'ezp.inspect', // name of exposed webservice AND php function at the same time
    null,  // input params array. Keys are not really used, as param validation is positional. Use null instead of an array to avoid type validation
    'struct', // type of return value
    'Inspects any eZ Publish persistent object. Params: string $class, string $id (if the key is built fropm many columns, use a comma-concatenation), [string $attribute]* (used to get back only an attribute of the object. Recursivity allowed' );

// These stub functions are only used to allow registering a class method with a friendlier ws name than using php classes

function ezp_viewall( $module, $view, $return_type = eZWebservicesAPIExecutor::RETURN_VARIABLES, $parameters = array(), $unordered_parameters = array(), $post_parameters = array() )
{
    // since this ws is pretty powerful, allow finer-grained perms checking...
    $ini = ezINI::instance( 'ezwebservicesapi.ini' );
    $skipaccesscheck = ( $ini->variable( 'ws_runview', 'SkipViewAccessCheck' ) == 'enabled' );
    return eZWebservicesAPIExecutor::ezpublish_view( $module, $view, $return_type, $parameters, $unordered_parameters, $post_parameters, $skipaccesscheck );
}

function ezp_fetchall( $module, $fetch, $parameters = array(), $results_filter = array(), $encode_depth = 1 )
{
    return eZWebservicesAPIExecutor::ezpublish_fetch( $module, $fetch, $parameters, $results_filter, $encode_depth );
}

/// This could be simpler if we could have a fixed signature, but number of params is variable...
function ezp_inspect()
{
    if ( func_num_args() < 2 )
    {
        /// @todo log error, return an error?
        return false;
    }
    $params = func_get_args();
    $classname = (string)array_shift( $params );
    $keys = (string)array_shift( $params );
    return eZWebservicesAPIExecutor::ezpublish_inspect( $classname, $keys, $params );
}

// Now the dynamic part:
// create one ws call per module view and one per fetch function
// Since it takes quite a while, we cache the results for later reuse
$cachedfile = eZWebservicesAPIExecutor::initializeFileName();
$clusterFileHandler = eZClusterFileHandler::instance();
if ( !$clusterFileHandler->fileExists( $cachedfile ) )
{
    eZDebug::writeWarning( "$cachedfile not found", __METHOD__ );
    $code = "<?php \n" . eZWebservicesAPIExecutor::generateInitializeFile() . "\n?>";
    $clusterFileHandler->fileStoreContents( $cachedfile, $code );
}
$clusterFileHandler->fileFetch( $cachedfile );
include( $cachedfile );

?>
