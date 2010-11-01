<?php
/**
 * A 'helper' file, that will be included once by eZJSCore.
 * What it does is to create dynamically a subclass of ezWebservicesAPIJSCFunctions,
 * called ezWebservicesAPIJSCFunctionsExtended, that will have some extra methods
 * injected into it in a way that later they can be checked using method_exists,
 * as ezjscore does.
 * The source code is stored in a file in the cache system, so that the autoload
 * mechanism will not bother with us.
 *
 * What a long filename, isn't it?
 *
 * @version $Id$
 * @copyright 2010
 */


// Now the dynamic part:
// create one ws call per module view and one per fetch function
// Since it takes quite a while, we cache the results for later reuse
$cachedfile = eZWebservicesAPIExecutor::initializeFileName( 'ezjscore_' );
$clusterFileHandler = eZClusterFileHandler::instance();
if ( !$clusterFileHandler->fileExists( $cachedfile ) )
{
    eZDebug::writeWarning( "$cachedfile not found, regenerating it", __METHOD__ );
    $code = "<?php \n" . eZWebservicesAPIExecutor::generateJSCInitializeFile() . "\n?>";
    $clusterFileHandler->fileStoreContents( $cachedfile, $code );
}
$clusterFileHandler->fileFetch( $cachedfile );
include( $cachedfile );

?>