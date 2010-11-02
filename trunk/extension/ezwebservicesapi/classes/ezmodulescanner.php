<?php
/**
 * Class that scans all active module definitions
 * has been copied to ggsysinfo, too...
 *
 * @version $Id$
 * @author G. Giunta
 * @copyright (C) G. Giunta 2010
 * @license code licensed under the GNU GPL 2.0: see README
 */

class eZModuleScanner
{

    /**
    * Finds all available modules in the system
    * @return array $modulename => $path
    */
    static function getModuleList()
    {
        $out = array();
        foreach ( eZModule::globalPathList() as $path )
        {
            foreach ( scandir( $path ) as $subpath )
            {
                if ( $subpath != '.' && $subpath != '..' && is_dir( $path . '/' . $subpath ) && file_exists( $path . '/' . $subpath . '/module.php' ) )
                {
                    $out[$subpath] = $path . '/' . $subpath . '/module.php';
                }
            }
        }
        return $out;
    }

    /**
    * @return array
    */
    /*static function analyzeModule( $path )
    {

    }*/
}

?>