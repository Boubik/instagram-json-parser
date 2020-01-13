<?php

/**
 * will load file in root folder of program
 * @param String $filename filen name with end (.txt, etc)
 * @param String $mode mode (w, a, etc)
 * @return String text in file
 */
function load_file($filename, $mode = "r")
{

    $handle = fopen($filename, $mode);
    $text = "";
    while (($line = fgets($handle)) !== false) {
        $text = $text . $line;
    }
    return $text;
}
