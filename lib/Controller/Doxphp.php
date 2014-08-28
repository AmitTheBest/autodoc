<?php
/**
 *Created by Konstantin Kolodnitsky
 * Date: 25.07.14
 * Time: 14:12
 */
class Controller_Doxphp extends AbstractController{
    function init(){
        parent::init();

        $path = $this->api->locate('php','doxphp/lib/DoxPHP/Parser/Tokens.php');
        require_once($path);
        $path = $this->api->locate('php','doxphp/lib/DoxPHP/Parser/Parser.php');
        require_once($path);
        $path = $this->api->locate('php','doxphp/lib/DoxPHP/Exception/Exception.php');
        require_once($path);
        $path = $this->api->locate('php','doxphp/lib/DoxPHP/Exception/OutOfBoundsException.php');
        require_once($path);

        set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
            new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });


    }
    function getClassContent($path){
        try {
            $tokens = new DoxPHP\Parser\Tokens(file_get_contents($path));
            $parser = new DoxPHP\Parser\Parser();

            return $parser->parse($tokens);
        } catch (Exception $e) {
            throw $this->exception($e->getMessage());
        }
    }
    function convertJSON2Sphinx($blocks){
//        $blocks = json_decode($blocks);
        $out    = '';
        $indent = '   ';
        $level  = 0;

        if (null === $blocks) {
            exit(1);
        }

        foreach ($blocks as $block) {

            $ignore = false;
            foreach ($block->tags as $tag) {
                if ($tag->type == "ignore") {
                    // Ignore this block because an @ignore tag was found
                    $ignore = true;
                    break;
                }
            }

            if ($block->isPrivate || $ignore) {
                continue;
            }

            if (in_array($block->type, array('function', 'namespace'))) {
                $level = 0;
            } else {
                $level = 1;
            }

            $prefix = str_repeat($indent, $level);

            if (!in_array($block->type, array('function', 'namespace', 'class', 'interface'))) {
                $out .= $prefix;
            }

            if ("variable" === $block->type) {
                $out .= ".. php:attr:: ".$block->name."\n\n";
            } elseif ("constant" === $block->type) {
                $out .= ".. php:const:: ".$block->name."\n\n";
            } else {
                $out .= ".. php:".$block->type.":: ".$block->name."\n\n";
            }

            // no description for namespace
            if ("namespace" === $block->type) {
                continue;
            }

            if (!empty($block->description)) {
                // escape slashes
                $description = str_replace("\\", "\\\\", $prefix.$indent.$block->description);
                // indent new lines
                $description = preg_replace('/([\\n\\r]+)/', '$1'.$prefix.$indent, $description);

                $out .= $description."\n\n";
            }

            foreach ($block->tags as $tag) {
                if ("param" !== $tag->type) {
                    $out .= "\n";
                }

                $out .= $prefix.$indent.":".$tag->type;

                if ("return" === $tag->type) {
                    $out .= "s";
                }

                if ("param" !== $tag->type) {
                    $out .= ":";
                }

                if (isset($tag->types)) {
                    $out .= " ".str_replace("\\", "\\\\", implode("|", $tag->types));
                }

                if (isset($tag->name)) {
                    $out .= " $".$tag->name;
                }

                if ("param" === $tag->type) {
                    $out .= ":";
                }

                if (isset($tag->description)) {
                    $lines = str_replace("\\", "\\\\", explode("\n", $tag->description));
                    $out .= " ".$lines[0];
                }

                $out .= "\n";
            }
            $out .= "\n";
        }

        return trim(str_replace("\n\n\n", "\n\n", $out));
    }
}