<?php
/**
 * Model implementation
 */
class Model_SourceFile extends Model
{
    public $table="sourcefile";

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

        $path = $this->api->locate('book','en/'.$this['doc_location'].'.rst');
        if(!$path){
            return 'There is no such a file';
        }

        $classes = $this->parseFile($path);

//        return $out;
        foreach($classes[1] as $class){
            if($class === $this['file']){
                $content = $this->parseClass($class);
//                var_dump($content);
//                return $content;
                $this['contents'] = $content;
                $this['last_imported'] = date('d/m/Y');
                $this->save();
                return 'Successfuly injected';
            }
        }



        return 'Nothing changed';
    }
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
    private function parseFile($path){
        $content = file_get_contents($path);
        preg_match_all('/[.]{2}\s[p]hp:class::\s([a-zA-z]*)/',$content,$out);
        return $out;
    }
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
