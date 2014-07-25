<?php
/**
 *Created by Konstantin Kolodnitsky
 * Date: 25.07.14
 * Time: 14:12
 */
class Controller_RstParser extends AbstractController{
    private $parser;
    function init(){
        parent::init();

        $this->parser = new Gregwar\RST\Parser;

    }
    function getFileContent($path){
        $string = file_get_contents($path);
        $document = $this->parser->parse($string);
        return $document;
    }
}