<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.path');

/**
 * File Manager class that implements functionality to manage and upload
 * pictures to the images directory on a Joomla site.
 */
class FileManager
{
    /**
     * Get the directories.
     * 
     * @param $dir The directory to look in
     * 
     * @returns The directories
     */
    function getCorrectedPath($dir)
    {
		$rootPath = substr(dirname(__FILE__), 0, strlen(dirname(__FILE__)) - 38);
		
		return $rootPath . $dir;
    }

    /**
     * Get the directories.
     * 
     * The returned directory structure is relative to the asked ($dir), thus each directory
	 * has "../../../" in front. This will be removed on the frontend.     
     * 
     * @param $dir The directory to look in
     * 
     * @returns The directories
     */
    function getDirectories($dir)
    {
        $this->setErrorHandling();

		$dir = ".." . DS . ".." . DS . ".." . DS . $dir;

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "get directories: " . $dir);

        $folders = null;

        $folderExists = JFolder::exists($dir);

        if ($folderExists)
        {
            $folders = JFolder::listFolderTree($dir, ".", 10);
        }
        
        return $folders;
    }

    /**
     * Get the files in a directory.
     * 
     * @param $dir The directory to look in
     * 
     * @returns The files
     */
    function getFiles($requestId, $dir)
    {
        $this->setErrorHandling();
        
		$dir = $this->getCorrectedPath($dir);

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "get files: " . $dir);
        
        $files = null;

        $folderExists = JFolder::exists($dir);

        if ($folderExists)
        {
            // TODO - exlude file names, to make it faster (maybe)
//            $files = JFolder::files($dir, '.', false, true, ['html', 'txt', 'php',..]);
            $files = JFolder::files($dir, '.', false, false);
        }

		$files[] = $requestId;

        return $files;
    }

    /**
     * Delete the files in a directory.
     * 
     * @param $dir The directory to look in
     * @param $files The files to delete
     * 
     * @returns The files
     */
    function deleteFiles($dir, $files)
    {
        $this->setErrorHandling();

		$dir = $this->getCorrectedPath($dir);
        
        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "delete files: " . $dir . " - files: " . $files);

        $result = true;
        for ($i = 0; $i < count($files); $i++)
        {
            $result = $result && JFile::delete($dir . DS . $files[$i]);
        }

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "result: " . $result);
        
        return $result;
    }

    /**
     * Rename a file.
     * 
     * @param $dir The directory to look in
     * @param $files The files to delete
     * 
     * @returns The result
     */
    function renameFile($dir, $file, $newFile)
    {
        $this->setErrorHandling();

		$dir = $this->getCorrectedPath($dir);
        
        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "rename file: " . $dir . " - file: " . $file . " - to: " . $newFile);
        
        $result = JFile::move($dir . DS . $file, $dir . DS . $newFile);

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "result: " . $result);
        
        return $result;
    }

	/**
     * Delete the directory dir.
     * 
     * @param $dir
     */
    function deleteDirectory($dir)
    {
        $this->setErrorHandling();

		$dir = $this->getCorrectedPath($dir);

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "delete directory: " . $dir);

        $result = JFolder::delete($dir);

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "result: " . $result);

        return $result;
    }

    /**
     * Create a directory with the directory name dirName inside
     * the directory dir.
     * 
     * Also inserts an empty index.html file for security reasons.
     * 
     * @param $dir
     * @param $dirName
     */
    function createDirectory($dir, $dirName)
    {
        $this->setErrorHandling();
        
		$dir = $this->getCorrectedPath($dir);

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "create directory: " . $dir . " - name: " . $dirName);

        $result = JFolder::create($dir . DS . $dirName);
        
        try
        {
			$fileData = "<html>\n<body bgcolor=\"#FFFFFF\">\n</body>\n</html>";
	        $result  = $result && JFile::write($dir . DS . $dirName . DS . "index.html", $fileData);
        }
        catch(Exception $e) 
        {
	        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "Exception when trying to create directory: " . $e);

            $result = false;
        } 

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "result: " . $result);
                
        return $result;
    }

    /**
     * Rename directory with the directory name dirName inside
     * the directory dir.
     * 
     * @param $dir
     * @param $dirName
     */
    function renameDirectory($dir, $oldDirName, $dirName)
    {
        $this->setErrorHandling();
        
		$dir = $this->getCorrectedPath($dir);

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "rename directory: " . $dir . " - old name: " . $oldDirName. " - new name: " . $dirName);

        $result = JFolder::move($dir. DS . $oldDirName, $dir . DS . $dirName);

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "result: " . $result);
                
        return $result;
    }

    /**
	 * Upload a file to a certain directory.
	 * 
	 * @param $fileName
	 * @param $filePath
	 * @param $fileData
	 */
    function upload($fileName, $filePath, $fileData)
    {
        $this->setErrorHandling();

		$filePath = $this->getCorrectedPath($filePath);
        
        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "upload file: " . $fileName . " - path: " . $filePath);
        
        // TODO - use this to create a safe file name
        //$safefilename = JFile::makeSafe($filename);

        try
        {
            $result = file_put_contents($filePath.DS.$fileName, $fileData->data);
        }
        catch(Exception $e) 
        {
            $result = false;
        } 

        return $result;
    }

    /**
     * Move the files in a directory.
     * 
     * @param $dirDest The directory to move to
     * @param $dirSrc The directory to move from
     * @param $files The files to delete
     * 
     * @returns The files
     */
    function moveFiles($dirDest, $dirSrc, $files)
    {
        $this->setErrorHandling();

		$dirDest = $this->getCorrectedPath($dirDest);
        
		$dirSrc = $this->getCorrectedPath($dirSrc);
        
        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "move files from: " . $dirSrc . " - to: " . $dirDest . " - files: " . $files);

        $result = true;
        for ($i = 0; $i < count($files); $i++)
        {
	        $result = $result && JFile::move($dirSrc . DS . $files[$i], $dirDest . DS . $files[$i]);
        }

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "result: " . $result);
        
        return $result;
    }
    
    /**
     * Logs a message and file info into debug log text file.
     * 
     * @param $info
     * @param $msg
     */
    function debug($info, $msg)
    {
//        file_put_contents("../../../logs/debuglog.txt", $info.": ".$msg."\n", FILE_APPEND);
    }
    
    /**
     * Set the error handling to ignore all errors.
     */
    function setErrorHandling()
    {
//        JError::setErrorHandling(E_ALL, 'callback', array('FileManager', 'fileErrorHandler'));
        
        JError::setErrorHandling(E_ALL, 'ignore');
    }

    /**
     * Error handler method
     * 
     * @param unknown_type $error
     * @return NULL
     */
    function &fileErrorHandler($error) 
    {
//        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "errors: "); // . $error->get('message'));

//        file_put_contents("../../../logs/debuglog.txt", "error\n", FILE_APPEND);
        
//        JError::handleIgnore($error, $options);
        return null; //$error->get('message');
    }
}