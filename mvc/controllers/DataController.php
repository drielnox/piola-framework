<?php

namespace CEIT\mvc\controllers
{
    use \CEIT\core;
    use \CEIT\mvc\models;
    
    class DataController extends core\AController
    {
        public function __construct()
        {
            parent::__construct();
            
            if(!empty($this->_model))
            {
                $this->_model = array(
                    'PedidoItems'   =>  new models\PedidoItemModel(),
                );
            }
        }
        
        public function __destruct()
        {
            parent::__destruct();
        }
        
        public function getpreview($param)
        {
            echo $param;
        }
        
        public function getpdf($param)
        {
            $item = explode("/", $param);
            
            $modelItems = new models\PedidoItemModel();
            $modelItems->_idItem = $item[count($item) - 1];
            $this->result = $this->_model['PedidoItems']->Select($modelItems);
            //var_dump($this->result);
            
            $file = BASE_DIR . '/data/texts/' . $param . '.pdf';
            $filename = $param . '.pdf'; /* Note: Always use .pdf at the end. */

            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($file));
            header('Accept-Ranges: bytes');

            @readfile($file);
        }
        
        public function gettp($param)
        {
            $file = BASE_DIR . '/data/tps/' . $param . '.pdf';
            $filename = $param . '.pdf'; /* Note: Always use .pdf at the end. */

            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($file));
            header('Accept-Ranges: bytes');

            @readfile($file);
        }
    }
}

?>