<?php
/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {

        /* PUT data comes in on the stdin stream */
        $input = fopen("php://input", "r");

        /* Open a file for writing */
        $fp = fopen($path, "w");

        /* Read the data 1 KB at a time
           and write to the file */
        while ($data = fread($input, 1024))
          fwrite($fp, $data);

        /* Close the streams */
        fclose($fp);
        fclose($input);

        return true;
    }

    function getName() {
        if(isset($_SERVER['HTTP_X_FILE_NAME']))
        {
            return $_SERVER['HTTP_X_FILE_NAME'];
        }

        return $_REQUEST['qqfile'];
    }

    function getSize() {
        if (isset($_SERVER['HTTP_X_FILE_SIZE'])){
            return (int)$_SERVER['HTTP_X_FILE_SIZE'];
        }
        elseif (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];
        } else {
            throw new Exception('Getting content length is not supported.');
        }
    }
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}