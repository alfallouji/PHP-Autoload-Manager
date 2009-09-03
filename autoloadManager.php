<?php
/**
 * Note : Code is released under the GNU LGPL
 *
 * Please do not change the header of this file 
 *
 * This library is free software; you can redistribute it and/or modify it under the terms of the GNU 
 * Lesser General Public License as published by the Free Software Foundation; either version 2 of 
 * the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 *
 * See the GNU Lesser General Public License for more details.
 */

/**
 * File:        autoloadManager.php
 * 
 * @author      Al-Fallouji Bashar & Charron Pierrick
 * @version     1.1
 */

/**
 * autoloadManager class
 *
 * Handles the class autoload feature
 * 
 * Register the loadClass function: spl_autoload_register('autoloadManager::loadClass');
 * Add a folder to process: autoloadManager::addFolder('{YOUR_FOLDER_PATH}');
 * 
 * Read documentation for more information.
 */
class autoloadManager
{    
    /**
     * Contains all the folders that should be parsed
     * @var Array 
     */    
    private static $_folders = array();

    /**
     * Contains all the classes and their matching filename 
     * @param Array  
     */    
    private static $_classes = array(); 

    /**
     * Add a new folder to parse
     * 
     * @param String $path Root path to process
     */
    public static function addFolder($path)
    {
        self::$_folders[] = $path; 
        
        $autoloadFile = AUTOLOAD_SAVE_PATH . DIRECTORY_SEPARATOR  . md5($path) . '.php';
                
        if(file_exists($autoloadFile))
        {       
            $_autoloadManagerArray = require_once($autoloadFile);  
            
            self::$_classes = array_merge(self::$_classes, $_autoloadManagerArray);
        }
    }

    /**
     * Method used by the spl_autoload_register
     * 
     * @param String $className Name of the class
     * @param Boolean $regenerate Indicates if the files should be regenerated
     */
    public static function loadClass($className, $regenerate = true)
    {
        if(array_key_exists($className, self::$_classes) && file_exists(self::$_classes[$className]))
        {            
            require_once(self::$_classes[$className]);
            return;            
        }

        // Regenerate array of classes and store files
        if(true === $regenerate)
        {
            self::parseFolders();
            self::loadClass($className, false);
        }
        
        return;
    }
    
    /** 
     * Parse every registred folders, regenerate autoload files and update the $_classes     
     */
    public static function parseFolders()
    {   
        foreach(self::$_folders as $folder)
        {        
            $classes = self::parseFolder($folder);
            self::saveToFile($classes, $folder);
        }
    }    
                
    /**
     * Parse folder and update $_classes array
     * 
     * @param String $folder Folder to process
     * @return Array Array containing all the classes found
     */
    private static function parseFolder($folder)
    {
        $classes = array();
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));

        foreach ($files as $file)
        {
            if (!$file->isFile() || '.php' !== substr($file->getFilename(), -4))
              continue;

            if($classNames = self::getClassesFromFile($file->getPathname()))
            {
                foreach($classNames as $className)
                {
                    // Adding class to map
                    $classes[$className] = $file->getPathname();
                    self::$_classes[$className] = $classes[$className]; 
                }
            }
        }    
        return $classes;
    }       

    /**
     * Extract the classname contained inside the php file
     *
     * @param String $file Filename to process
     * @return Array Array of classname(s) and interface(s) found in the file
     */
    private static function getClassesFromFile($file)
    {
        $classes = array();
        $tokens = token_get_all(file_get_contents($file));
        $nbtokens = count($tokens);

        for($i = 0 ; $i < $nbtokens ; $i++)
        {
            switch($tokens[$i][0])
            {
                case T_INTERFACE:
                case T_CLASS:
                        $i+=2;
                        $classes[] = $tokens[$i][1];
                        break;
            }
        }
        
        return $classes;
    }
    
    /**
     * Generate a file containing an array.
     * File is generated under the AUTOLOAD_SAVE_PATH folder.
     * 
     * @param Array $classes Contains all the classes found and the corresponding filename (e.g. {$className} => {fileName})
     * @param String $folder Folder to process
     */
    private static function saveToFile(array $classes, $folder)
    {
        // Write header and comment
        $content  = '<?php ' . PHP_EOL;        
        $content .= '/** ' . PHP_EOL;
        $content .= ' * AutoloadManager Script' . PHP_EOL;
        $content .= ' * ' . PHP_EOL;
        $content .= ' * @authors      Al-Fallouji Bashar & Charron Pierrick' . PHP_EOL;
        $content .= ' * ' . PHP_EOL;
        $content .= ' * @description This file was automatically generated at ' . date("Y-m-d [H:i:s]") . PHP_EOL;
        $content .= ' * ' . PHP_EOL;
        $content .= ' */ ' . PHP_EOL;    
        
        // Export array
        $content .= 'return ' . var_export($classes, true) . ';';
                
        file_put_contents(AUTOLOAD_SAVE_PATH . DIRECTORY_SEPARATOR  . md5($folder) . '.php', $content);       
    }    
}
