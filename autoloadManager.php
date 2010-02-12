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
 * @version     1.2
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
     * Folders that should be parsed
     * @var Array
     */
    private static $_folders = array();

    /**
     * Excluded folders
     * @var Array
     */
    private static $_excludedFolders = array();

    /**
     * Classes and their matching filename
     * @var Array
     */
    private static $_classes = array();

    /**
     * Scan files matching this regex
     * @var String
     */
    private static $_filesRegex = '/\.(inc|php)$/';

    /**
     * Save path (Default is current dir)
     * @var String
     */
    private static $_savePath = '.';

    /**
     * Get the path where autoload files are saved
     * 
     * @return String path where autoload files will be saved
     */
    public static function getSavePath()
    {
        return self::$_savePath;
    }

    /**
     * Set the path where autoload files are saved
     *
     * @param String $path path where autoload files will be saved
     */
    public static function setSavePath($path)
    {
        self::$_savePath = realpath($path);
    }

    /**
     * Set the file regex
     *
     * @param String
     */
    public static function setFileRegex($regex)
    {
        self::$_filesRegex = $regex;
    }
     
    /**
     * Add a new folder to parse
     *
     * @param String $path Root path to process
     */
    public static function addFolder($path)
    {
        if($realpath = realpath($path) and is_dir($realpath))
        {
            self::$_folders[] = $realpath;

            $autoloadFile = self::getSavePath() . DIRECTORY_SEPARATOR  . md5($realpath) . '.php';

            if(file_exists($autoloadFile))
            {
                $_autoloadManagerArray = require_once($autoloadFile);
    
                self::$_classes = array_merge(self::$_classes, $_autoloadManagerArray);
            }
        } 
        else
        {
            throw new Exception('Failed to open dir : ' . $path);
        }
    }

    /**
     * Exclude a folder from the parsing
     *
     * @param String $path Folder to exclude
     */
    public static function excludeFolder($path)
    {
        if($realpath = realpath($path) and is_dir($realpath))
        {
            self::$_excludedFolders[] = $realpath . DIRECTORY_SEPARATOR;
        } 
        else 
        {
            throw new Exception('Failed to open dir : ' . $path);
        }
    }

    /**
     * Checks if the class has been defined  
     *
     * @param String $className Name of the class
     * @return Boolean true if class exists, false otherwise.
     */
    public static function classExists($className)
    {
        return array_key_exists($className, self::$_classes);
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
        } 
        elseif(true === $regenerate)
        {
            self::parseFolders();
            self::loadClass($className, false);
        }
    }

    /**
     * Parse every registred folders, regenerate autoload files and update the $_classes
     */
    private static function parseFolders()
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
            if($file->isFile() && preg_match(self::$_filesRegex, $file->getFilename()))
            {
                $len = strlen($folder);
                foreach(self::$_excludedFolders as $folder)
                {
                    if(0 === strncmp($folder, $file->getPathname(), $len))
                    {
                        continue 2;
                    }
                }

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
     * File is generated under the _savePath folder.
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

        file_put_contents(self::getSavePath() . DIRECTORY_SEPARATOR  . md5($folder) . '.php', $content);
    }
}
