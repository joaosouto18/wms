<?php

namespace Wms;

class Git
{
    public static function getCurrent() {
        if (self::getCurrentTag() === false) {
            return self::getCurrentBranch();
        } else {
            return self::getCurrentTag();
        }
    }

    public static function getCurrentBranch() {
        $doc_root = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '',$_SERVER['SCRIPT_FILENAME']);
        $stringfromfile = file($doc_root ."/../.git/HEAD");
        $firstLine = $stringfromfile[0]; //get the string from the array
        $explodedstring = explode("/", $firstLine, 3); //seperate out by the "/" in the string
        $branchname = $explodedstring[2];
        return $branchname;
    }

    public static function  getCurrentTag() {

        $doc_root = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '',$_SERVER['SCRIPT_FILENAME']);

        $HEAD_hash = file_get_contents($doc_root ."/../.git/HEAD");

        $files = glob($doc_root ."/../.git/refs/tags/*");
        foreach(array_reverse($files) as $file) {
            $contents = file_get_contents($file);

            if($HEAD_hash === $contents)
            {
                return(basename($file));
            }
        }
        return false;
    }
}
