<?php
/**
 * Created by PhpStorm.
 * User: luc
 * Date: 29/11/13
 * Time: 1.10
 */

class Utils {
    /**
     * Check a string of base64 encoded data to make sure it has actually
     * been encoded.
     *
     * @param $encodedString string Base64 encoded string to validate.
     * @return Boolean Returns true when the given string only contains
     * base64 characters; returns false if there is even one non-base64 character.
     */
    function checkBase64Encoded($encodedString) {
        $length = strlen($encodedString);

        // Check every character.
        for ($i = 0; $i < $length; ++$i) {
            $c = $encodedString[$i];
            if (
                ($c < '0' || $c > '9')
                && ($c < 'a' || $c > 'z')
                && ($c < 'A' || $c > 'Z')
                && ($c != '+')
                && ($c != '/')
                && ($c != '=')
            ) {
                // Bad character found.
                return false;
            }
        }
        // Only good characters found.
        return true;
    }

    function extract_emails_from($string){
        preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $string, $matches);
        return $matches[0];
    }

    function checkWinFileSystem($path) {
        $path = str_replace("/","-",$path);
        $path = str_replace("\\","-",$path);
        $path = str_replace("\"","-",$path);
        $path = str_replace(":","-",$path);
        $path = str_replace("?","-",$path);
        $path = str_replace("*","-",$path);
        $path = str_replace("<","-",$path);
        $path = str_replace(">","-",$path);
        $path = str_replace("|","-",$path);
        return $path;
    }
} 