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

        $this->add('dynamic_model/Controller_AutoCreator_SQLite');
    }

    function refresh() {
        //Parse .rst file
        $path = $this->api->locate('book',$this['file']);
        if(!$path){
            return 'There is no such a file';
        }

        $content = file_get_contents($path);

        $content = preg_match_all('/\:php\:class\:\`([a-zA-z]*)\`/',$content,$out);
//        $content = preg_replace('/\:php\:class\:\`[a-zA-z]*\`/','hui',$content);
//var_dump($out);


        //with parser
//        $rstparser = $this->add('Controller_RstParser');
//        $content = $rstparser->getFileContent($path);
        return $out;



        //Parse atk class file
        $path = $this->api->locate('php','Order.php');
        if(!$path){
            return 'There is no such a file';
        }

        $dox = $this->add('Controller_Doxphp');

        $json = $dox->getClassContent('vendor/atk4/atk4/'.$path);

        $sphinx = $dox->convertJSON2Sphinx($json);
        return $sphinx;

        //php bin/doxphp  < path/to/atk4/lib/Order.php
        //php bin/doxphp  < path/to/atk4/lib/Order.php | php bin/doxphp2sphinx
    }
}
