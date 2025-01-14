<?php

namespace CEIT\core
{
    final class CAutoLoader
    {
        private $_dirs = array(
            'CORE'          =>  '/core/',
            'LIBS'          =>  '/libs/',
            'MODEL'         =>  '/mvc/models/',
            'CONTROLLER'    =>  '/mvc/controllers/',
            'VIEW'          =>  '/mvc/views/',
            'TEMPLATE'      =>  '/mvc/templates/'
        );
        
        public function __construct()
        {
            spl_autoload_register(array(
                $this,
                'autoload'
            ));
        }
        
        private function autoload($className)
        {
            try
            {
                $arrayParts = explode("\\", $className);
                $classSearch = $arrayParts[count($arrayParts) - 1];
                
                foreach($this->_dirs as $dir)
                {   
                    if(is_readable(BASE_DIR . $dir . $classSearch . '.php'))
                    {
                        require_once(BASE_DIR . $dir . $classSearch . '.php');
                        break;
                    }
                }
            }
            catch(Exception $ex)
            {
                echo $ex->getTraceAsString();
            }
        }
    }
}

?>