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
 * @version     2.0
 */


if (!defined('T_NAMESPACE'))
{
    /**
     * This is just for backword compatibilty with previous versions
     * Token -1 will never exists but we just want to avoid having undefined 
     * constant
     */
    define('T_NAMESPACE', -1);
    define('T_NS_SEPARATOR', -1);
    if (!defined('T_TRAIT'))
    {
        define('T_TRAIT', -1);
    }
}

/**
 * autoloadManager class
 *
 * Handles the class autoload feature
 *
 * Register the loadClass function: $autoloader->register();
 * Add a folder to process: $autoloader->addFolder('{YOUR_FOLDER_PATH}');
 *
 * Read documentation for more information.
 */
class autoloadManager
{
    /**
     * Constants used by the checkClass method
     * @var Integer
     */
    const CLASS_NOT_FOUND = 0;
    const CLASS_EXISTS    = 1;
    const CLASS_IS_NULL   = 2;

    /**
     * Folders that should be parsed
     * @var Array
     */
    private $_folders = array();

    /**
     * Excluded folders
     * @var Array
     */
    private $_excludedFolders = array();

    /**
     * Classes and their matching filename
     * @var Array
     */
    private $_classes = array();

    /**
     * Scan files matching this regex
     * @var String
     */
    private $_filesRegex = '/\.(inc|php)$/';

    /**
     * Save path (Default is null)
     * @var String
     */
    private $_saveFile = null;

    /**
     * Regenerate the autoload file or not. (default: not)
     * @var bool Defaults to false. 
     */
    private $_regenerate = false;

    /**
     * Get the path where autoload files are saved
     * 
     * @return String path where autoload files will be saved
     */
    public function getSaveFile()
    {
        return $this->_saveFile;
    }

    /**
     * Set the path where autoload files are saved
     *
     * @param String $path path where autoload files will be saved
     */
    public function setSaveFile($pathToFile)
    {
        $this->_saveFile = $pathToFile;
        if ($this->_saveFile && file_exists($this->_saveFile))
        {
            $this->_classes = include($this->_saveFile);
        } 
    }

    /**
     * Set the file regex
     *
     * @param String
     */
    public function setFileRegex($regex)
    {
        $this->_filesRegex = $regex;
    }

    /**
     * Add a new folder to parse
     *
     * @param String $path Root path to process
     */
    public function addFolder($path)
    {
        if ($realpath = realpath($path) and is_dir($realpath))
        {
            $this->_folders[] = $realpath;
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
    public function excludeFolder($path)
    {
        if ($realpath = realpath($path) and is_dir($realpath))
        {
            $this->_excludedFolders[] = $realpath . DIRECTORY_SEPARATOR;
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
    public function classExists($className)
    {
        return self::CLASS_EXISTS === $this->checkClass($className, $this->_classes);
    }

    /**
     * Set the regeneration of the cached autoload files.
     * 
     * @param  bool $flag Ture or False to regenerate the cached autoload file.
     * @return void
     */
    public function setRegenerate($flag)
    {
        $this->_regenerate = $flag;
    }

    /**
     * Get the regeneration of the cached autoload files.
     *
     * @return bool
     */
    public function getRegenerate()
    {
        return $this->_regenerate;
    }

    /**
     * Method used by the spl_autoload_register
     *
     * @param String $className Name of the class
     * @param Boolean $regenerate Indicates if the files should be regenerated
     */
    public function loadClass($className)
    {
        // check if the class already exists in the cache file
        $loaded = $this->checkClass($className, $this->_classes);
        if (true === $this->_regenerate || !$loaded)
        {
            // parse the folders returns the list of all the classes
            // in the application
            $this->refresh();

            // recheck if the class exists again in the reloaded classes
            $loaded = $this->checkClass($className, $this->_classes);
            if (!$loaded)
            {
                // set it to null to flag that it was not found
                // This behaviour fixes the problem with infinite
                // loop if we have a class_exists() for an inexistant
                // class. 
                $this->_classes[$className] = null;
            }
            // write to a single file
            if ($this->getSaveFile())
            {
                $this->saveToFile($this->_classes);
            }
        }
    }

    /**
     * checks if a className exists in the class array
     * 
     * @param  mixed  $className    the classname to check
     * @param  array  $classes      an array of classes
     * @return int    errorCode     1 if the class exists
     *                              2 if the class exists and is null
     *                              (there have been an attempt done)
     *                              0 if the class does not exist
     */
    private function checkClass($className, array $classes)
    {
        if (array_key_exists($className, $classes))
        {
            $classPath = $classes[$className];
            // return true if the 
            if (null === $classPath)
            {
                return self::CLASS_IS_NULL;
            }    
            elseif (file_exists($classPath))
            {
                require($classes[$className]);
                return self::CLASS_EXISTS;
            }
        }    
        return self::CLASS_NOT_FOUND;
    }    


    /**
     * Parse every registred folders, regenerate autoload files and update the $_classes
     */
    private function parseFolders()
    {
        $classesArray = array();
        foreach ($this->_folders as $folder)
        {
            $classesArray = array_merge($classesArray, $this->parseFolder($folder));
        }
        return $classesArray;
    }

    /**
     * Parse folder and update $_classes array
     *
     * @param String $folder Folder to process
     * @return Array Array containing all the classes found
     */
    private function parseFolder($folder)
    {
        $classes = array();
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));

        foreach ($files as $file)
        {
            if ($file->isFile() && preg_match($this->_filesRegex, $file->getFilename()))
            {
                $len = strlen($folder);
                foreach ($this->_excludedFolders as $folder)
                {
                    if (0 === strncmp($folder, $file->getPathname(), $len))
                    {
                        continue 2;
                    }
                }

                if ($classNames = $this->getClassesFromFile($file->getPathname()))
                {
                    foreach ($classNames as $className)
                    {
                        // Adding class to map
                        $classes[$className] = $file->getPathname();
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
    private function getClassesFromFile($file)
    {
        $namespace = null;
        $classes = array();
        $tokens = token_get_all(file_get_contents($file));
        $nbtokens = count($tokens);

        for ($i = 0 ; $i < $nbtokens ; $i++)
        {
            switch ($tokens[$i][0])
            {
                case T_NAMESPACE:
                    $i+=2;
                    while ($tokens[$i][0] === T_STRING || $tokens[$i][0] === T_NS_SEPARATOR)
                    {
                        $namespace .= $tokens[$i++][1];
                    }
                    break;
                case T_INTERFACE:
                case T_CLASS:
                case T_TRAIT:
                    $i+=2;
                    if ($namespace)
                    {
                        $classes[] = $namespace . '\\' . $tokens[$i][1];
                    } 
                    else 
                    {
                        $classes[] = $tokens[$i][1];
                    }
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
    private function saveToFile(array $classes)
    {
        // Write header and comment
        $content  = '<?php ' . PHP_EOL;
        $content .= '/** ' . PHP_EOL;
        $content .= ' * AutoloadManager Script' . PHP_EOL;
        $content .= ' * ' . PHP_EOL;
        $content .= ' * @authors      Al-Fallouji Bashar & Charron Pierrick' . PHP_EOL;
        $content .= ' * ' . PHP_EOL;
        $content .= ' * @description This file was automatically generated at ' . date('Y-m-d [H:i:s]') . PHP_EOL;
        $content .= ' * ' . PHP_EOL;
        $content .= ' */ ' . PHP_EOL;

        // Export array
        $content .= 'return ' . var_export($classes, true) . ';';
        file_put_contents($this->getSaveFile(), $content);
    }

    /**
     * Returns previously registered classes
     * 
     * @return array the list of registered classes
     */
    public function getRegisteredClasses()
    {
        return $this->_classes;
    }    

    /**
     * Refreshes an already generated cache file
     * This solves problems with previously unexistant classes that
     * have been made available after.
     * The optimize functionnality will look at all null values of 
     * the available classes and does a new parse. if it founds that 
     * there are classes that has been made available, it will update
     * the file.
     * 
     * @return bool true if there has been a change to the array, false otherwise
     */
    public function refresh()
    {
        $existantClasses = $this->_classes;
        $nullClasses = array_filter($existantClasses, array('self','_getNullElements'));
        $newClasses = $this->parseFolders();

        // $newClasses will override $nullClasses if the same key exists
        // this allows new added classes (that were flagged as null) to be 
        // added
        $this->_classes = array_merge($nullClasses, $newClasses);
        return true;
    }

    /**
     * Generate the autoload file
     * 
     * @return void
     */
    public function generate()
    {
        if ($this->getSaveFile())
        {
            $this->refresh();
            $this->saveToFile($this->_classes);
        }
    }

    /**
     * returns null elements (used in an array filter)
     *
     * @param mixed $element the element to check
     * @return boolean true if element is null, false otherwise
     */
    private function _getNullElements($element)
    { 
        return null === $element; 
    }

    /**
     * Registers this autoloadManager on the SPL autoload stack.
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Removes this autoloadManager from the SPL autoload stack.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }
}
