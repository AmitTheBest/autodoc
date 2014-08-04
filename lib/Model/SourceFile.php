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

        //with parser
//        $rstparser = $this->add('Controller_RstParser');
//        $content = $rstparser->getFileContent($path);
//        return $out;


//        $dox = $this->add('Controller_Doxphp');

//        $json = $dox->getClassContent('vendor/atk4/atk4/'.$path);

//        $sphinx = $dox->convertJSON2Sphinx($json);
//        return $sphinx;

        //php bin/doxphp  < path/to/atk4/lib/Order.php
        //php bin/doxphp  < path/to/atk4/lib/Order.php | php bin/doxphp2sphinx

        //Parse atk class file
        //Parse .rst file
        if(!$this['doc_location']){
            throw $this->exception('Doc location is not specified. Please hit the "Get doc location button".');
        }
        $path = $this->api->locate('book','en/'.$this['doc_location'].'.rst');
        if(!$path){
            return 'There is no such a file';
        }

        //Find the pattern '.. php:class:class::CLASSNAME'
        $content = file_get_contents($path);
        $content = preg_match_all('/[.]{2}\s[p]hp:class::\s([a-zA-z]*)/',$content,$out);

//        return $out;
        foreach($out[1] as $k=>$v){
            if($v === $this['file']){
                $this['last_imported'] = date('d/m/Y');
                $this->save();
                return 'Successfuly injected';
            }
        }



        return 'Nothing changed';
    }
    function createClassInstance(){
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
