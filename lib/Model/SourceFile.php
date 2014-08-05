<?php
/**
 * Model implementation
 */
class Model_SourceFile extends Model
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

        $this->setSource('SQLite');

        $this->addField('file');
        $this->addField('last_imported')->type('date');
        $this->addField('contents')->type('text');
        $this->addField('doc_location');

        $this->add('dynamic_model/Controller_AutoCreator_SQLite');
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

        $rst_content = $this->parseFile();
        $classes = $this->getClassesArray($rst_content);

        $this->replaceClassComment($rst_content,$classes);
    }

    /**
     * Replaces or adds a description.....
     * @param $rst_content
     * @param $classes
     * @return string
     */
    private function replaceClassComment($rst_content,$classes){
        foreach($classes[1] as $class){
            if($class[0] === $this['file']){
                $pos_class_start = $class[1];// The start position of class NAME in the pattern in rst file

                //Get class comment
                $replacement = $this->parseClass($class[0]);

                //Find the start position of replacement
                $pos_star_replacement = strpos($rst_content,'    ',$pos_class_start);

                //Find the end position (the length) of replacement
                preg_match('/\n([a-zA-Z]+)/',$rst_content,$out,PREG_OFFSET_CAPTURE,$pos_class_start);
                $pos_end_replacement = $out[0][1];
                $length = $pos_end_replacement-$pos_star_replacement;

                //Replace the content
                $rst_content = substr_replace($rst_content,$replacement."\n\n",$pos_star_replacement,$length);

                //Save new content to rst file
                $this->saveRst($rst_content);

                //Save all data to db
                $this['contents'] = $replacement;
                $this['last_imported'] = date('d/m/Y');
                $this->save();
                echo ('Successfully injected');
            }
        }
    }

    /**
     * @param $content
     */
    private function saveRst($content){
        $q = file_put_contents($this->path,$content);
    }

    /**
     * Returns a string of properties and methods
     * @param $class
     * @return mixed
     * @throws BaseException
     */
    private function parseClass($class){
        $path = $this->app->pathfinder->atk_location->getPath().'/lib/'.$class.'.php';
        $content = file_get_contents($path);
        if(!$content){
            throw $this->exception('wrong path to atk file');
        }
        $dox = $this->add('Controller_Doxphp');
        $json = $dox->getClassContent($path);
        $sphinx = $dox->convertJSON2Sphinx($json);
        return $sphinx;
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
     * @return mixed
     */
    private function getClassesArray($content){
        preg_match_all('/[.]{2}\s[p]hp:class::\s([a-zA-z]*)/',$content,$out,PREG_OFFSET_CAPTURE);
        return $out;
    }

    /**
     * @return string
     * @throws BaseException
     */
    function getDocLocation(){
        if(!$this->loaded()){
            throw $this->exception('Model is not loaded');
        }
        $file = new $this['file'];
        if(!$file){
            throw $this->exception('Wrong class name.');
        }
        $file = substr($file->_doc,29);
        $file = str_replace('.html','',$file);
        $this['doc_location'] = $file;
        $this->save();
        return 'Saved';
    }
}
