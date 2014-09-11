<?php
/**
 * Model implementation
 */
class Model_SourceFile extends SQL_Model
{
    public $table="sourcefile";
    private $path;//The path to rst file

    /**
     * init model
     *
     * @return void
     */
    function init()
    {
        parent::init();

//        $this->setSource('SQLite');

        $this->addField('file');
        $this->addField('last_imported')->type('date');
        $this->addField('contents')->type('text');
        $this->addField('doc_location');

//        $this->add('dynamic_model/Controller_AutoCreator_MySQL');
    }

    function refresh() {
        if(!$this->loaded()){
            throw $this->exception('Model is not loaded');
        }
        if(!$this['doc_location']){
            throw $this->exception('Doc location is not specified. Please hit the "Get doc location" button.');
        }

        $this->path = $this->api->locate('book','en/'.$this['doc_location'].'.rst');
        if(!$this->path){
            return 'There is no such a file';
        }

        $count_injections = 0;
        $count_injected_args = 0;
        $replaced_tags = '';
        $added_args_tags = '';
        //First parse rst file and get an array of classes, methods and variables
        $rst_content = $this->parseFile();
        $array = $this->getArray($rst_content);

        //Get class content to parse
        $class_content = $this->parseClass($this['file']);

        $count = 0;
        $rst_content_new = null;
        foreach($array[2] as $k=>$tag){
            //Each iteration we have to parse string from the scratch cause it may be changed
            $rst_content_new = $this->parseFile();
            $array_new = $this->getArray($rst_content_new);

            $count++;

            /****************************/
            /*For testing purposes only*/
//            $do = 4;//select necessary iteration
//            if($count < $do) continue;
//            if($count > $do) break;
            /*************************/

            //Define the replacement area
            //Find the start position of replacement
            $start = $this->getStartPosition($rst_content_new,$array_new[2][$k][1]);

            //Find the end position (the length) of replacement
            $end = $this->getEndPosition($rst_content_new,$start);
            $length = $end-$start;

            // If no comment present - skip
            $string_end_pos = $array_new[2][$k][1]+strlen($array_new[2][$k][0]);

            //Now get new content
            $replacement = $this->getBlockDescription($class_content,$array_new[2][$k][0]);

            //Replace the content with the new one from the class
            if($start-$string_end_pos == 2 && ($replacement && $replacement != '')){
                $rst_content_new = $this->replaceContent($rst_content_new,$replacement,$start,$length);
                $count_injections++;
                $replaced_tags .= $array_new[2][$k][0].', ';
            }

            //Check if method in php have argument(s) but not in rst
            $a = $this->findArguments($class_content,$array_new[2][$k][0]);

            if($a){
                //Add arguments
                $rst_content_new = substr_replace($rst_content_new,$a,$string_end_pos,null);
                $count_injected_args++;
                $added_args_tags .= $array_new[2][$k][0].', ';
            }

            //Finally save data to the rst file
            $this->saveRst($rst_content_new);
        }
        //Save all data to db
        if($rst_content_new){
            $this['contents'] = $rst_content_new;
        }
        $this['last_imported'] = date('Y/m/d');
        $this->save();

        $return = '';
        if($count_injections>0){
            $return .= 'Successfully injected '.$count_injections.' comments. Replaced comments for: '.$replaced_tags.'.';
        }
        if($count_injected_args>0){
            $return .= 'Successfully added arguments to the '.$count_injected_args.' methods: '.$added_args_tags.'.';
        }

        if($return != '') return $return;

        return ('Nothing to change');

    }

    private function findArguments($blocks, $tag){
        $count = 0;
        foreach ($blocks as $block) {

            if ($block->isPrivate) {
                continue;
            }

            if($block->type != 'method'){
                continue;
            }

            $count++;

            preg_match('/::([a-zA-Z_]*)/',$block->name,$out);
            $block_name = $out[1];

            /****************************/
            /*For testing purposes only*/
//            $do = 7;//select necessary iteration
//            if($count < $do) continue;
//            if($count > $do) break;
            /*************************/

            //Clear tag from arguments
            preg_match('/[a-zA-z_]*/',$tag,$out);
            $tag_cleared = $out[0];

            if($block_name === $tag_cleared){
                //Exclude if argument(s) is present in rst
                if($tag == $tag_cleared){
                    preg_match('/function\s[a-zA-z_]*([(][^)]*[)])/',$block->code,$code);
                    return $code[1];
                }
            }
        }
        return null;
    }
    private function getBlockDescription($blocks, $tag){
        $count = 0;
        foreach ($blocks as $block) {
            $block_name = $block->name;
            $count++;

            /****************************/
            /*For testing purposes only*/
//            $do = 12;//select necessary iteration
//            if($count < $do) continue;
//            if($count > $do) break;
            /*************************/

            if ($block->isPrivate) {
                continue;
            }

            if($block->type == 'variable'){
                $block_name = substr($block->name,1);
            }elseif($block->type == 'method'){
                preg_match('/::([a-zA-Z_]*)/',$block->name,$out);
                $block_name = $out[1];
            }

            //Clear tag from arguments
            preg_match('/[a-zA-z_]*/',$tag,$out);
            $tag = $out[0];

            if($block_name === $tag){
                if($block->description){
                    return $block->description;
                }else{
                    return false;
                }
            }

        }
    }
    private function parseClass($class){
        $path = $this->app->pathfinder->atk_location->getPath().'/lib/'.$class.'.php';
        $content = file_get_contents($path);
        if(!$content){
            throw $this->exception('wrong path to atk file');
        }
        $dox = $this->add('Controller_Doxphp');
        $array = $dox->getClassContent($path);
        return $array;
    }

    /**
     * @return string
     */
    private function parseFile(){
        $content = file_get_contents($this->path);
        return $content;
    }

    /**
     * @param $content
     */
    private function saveRst($content){
        file_put_contents($this->path,$content);
    }

    /**
     * @param $content
     * @return mixed
     */
    private function getArray($content){
        preg_match_all('/[.]{2}\s[p]hp:([a-zA-Z]*)\:\:\s([^\n]*)/',$content,$out,PREG_OFFSET_CAPTURE);
        return $out;
    }

    /**
     * @param $content
     * @param $replacement
     * @param $start
     * @param null $length
     * @return mixed
     */
    private function replaceContent($content,$replacement,$start,$length=null){
        $replacement = preg_replace('/^/m', '    ', $replacement);
        return substr_replace($content,$replacement."\n",$start,$length);
    }

    /**
     * @param $content
     * @param $offset
     * @return mixed
     */
    private function getStartPosition($content,$offset){
        preg_match('/[\s]{4}[a-zA-Z]/',$content,$start,PREG_OFFSET_CAPTURE,$offset);
        return $start[0][1];
    }

    /**
     * @param $content
     * @param $offset
     * @return mixed
     */
    private function getEndPosition($content,$offset){
        preg_match('/\n[a-zA-Z.]+/',$content,$end,PREG_OFFSET_CAPTURE,$offset);
        return $end[0][1];
    }
    /**
     * @return string
     * @throws BaseException
     */
    function getDocLocation(){
        if(!$this->loaded()){
            throw $this->exception('Model is not loaded');
        }
        if(!$this['file'] || $this['file'] == ''){
            throw $this->exception('Empty class name.');
        }
        $path = $this['file']::DOC;
        if(!$path)throw $this->exception('The path to the doc file is not specified.');
        $this['doc_location'] = $path;
        $this->save();
        return 'Saved';
    }
}
