<?php

namespace CEIT\mvc\controllers
{
    use \CEIT\core;
    use \CEIT\mvc\models;
    use \CEIT\mvc\views;
    
    final class TextosController extends core\AController implements core\ICrud
    {
        public function __construct()
        {
            parent::__construct();
            
            if(empty($this->_model))
            {
                $this->_model = array(
                    'Textos'        =>  new models\TextoModel(),
                    'Carreras'      =>  new models\CarreraModel(),
                    'Niveles'       =>  new models\NivelModel(),
                    'Materias'      =>  new models\MateriaModel(),
                    "Contenidos"    =>  new models\ContenidoModel(),
                );
            }
            
            if(empty($this->_view))
            {
                $this->_view = new views\TextosView();
            }
        }
        
        public function __destruct()
        {
            if($this->_ajaxRequest)
            {
                $this->_view->json($this->result);
            }
            else if($this->_renderRaw)
            {
                $this->_view->renderRaw($this->result);
            }
            else
            {
                $this->_view->render($this->_template, $this->_dataCollection);
            }
            
            parent::__destruct();
            
            unset($this->result);
        }
        
        public function create()
        {
            $this->_template = BASE_DIR . "/mvc/templates/textos/{$this->_action}.html";
            
            if(!empty($_POST))
            {
                //var_dump($_POST, $_FILES);
                
                if(isset($_POST["btnGuardar"]))
                {
                    $nombre = filter_input(INPUT_POST, "txtNombre", FILTER_SANITIZE_SPECIAL_CHARS);
                    $cod_texto = filter_input(INPUT_POST, "txtCodigo", FILTER_SANITIZE_NUMBER_INT);
                    $carrera = filter_input(INPUT_POST, "ddlCarrera", FILTER_SANITIZE_NUMBER_INT);
                    $nivel = filter_input(INPUT_POST, "ddlNivel", FILTER_SANITIZE_NUMBER_INT);
                    $materia = filter_input(INPUT_POST, "ddlMateria", FILTER_SANITIZE_NUMBER_INT);
                    $contenido = filter_input(INPUT_POST, "ddlTipoContenido", FILTER_SANITIZE_NUMBER_INT);
                    $autor = filter_input(INPUT_POST, "txtAutor", FILTER_SANITIZE_SPECIAL_CHARS);
                    $docente = filter_input(INPUT_POST, "txtDocente", FILTER_SANITIZE_SPECIAL_CHARS);
                    $activo = filter_input(INPUT_POST, "chkActivo", FILTER_SANITIZE_STRING);
                    
                    if(!empty($_FILES))
                    {
                        $uploadDir = null;
                        
                        if(isset($_FILES['fileToUpload']))
                        {
                            switch($_FILES['fileToUpload']['error'])
                            {
                                case UPLOAD_ERR_OK:
                                    $modelCarrera = new models\CarreraModel();
                                    $modelCarrera->_idCarrera = $carrera;
                                    $this->resultCarrera = $this->_model["Carreras"]->Select($modelCarrera);
                                    //var_dump($this->resultCarrera);
                                    $cod_carrera = count($this->resultCarrera) == 1 ? $this->resultCarrera[0]["Codigo"] : null;
                                    unset($modelCarrera);

                                    $modelNivel = new models\NivelModel();
                                    $modelNivel->_idNivel = $nivel;
                                    $this->resultNivel = $this->_model["Niveles"]->Select($modelNivel);
                                    //var_dump($this->resultNivel);
                                    $cod_nivel = count($this->resultNivel) == 1 ? $this->resultNivel[0]["Ano"] : null;
                                    unset($modelNivel);

                                    $modelMateria = new models\MateriaModel();
                                    $modelMateria->_idMateria = $materia;
                                    $this->resultMateria = $this->_model["Materias"]->Select($modelMateria);
                                    //var_dump($this->resultMateria);
                                    $cod_materia = count($this->resultMateria) == 1 ? $this->resultMateria[0]["CodMateria"] : null;
                                    unset($modelMateria);

                                    $uploaddir = BASE_DIR . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "texts" . DIRECTORY_SEPARATOR . $cod_carrera . DIRECTORY_SEPARATOR . $cod_nivel . DIRECTORY_SEPARATOR . $cod_materia . DIRECTORY_SEPARATOR . $contenido . DIRECTORY_SEPARATOR;
                                    $uploadfile = $uploaddir . $cod_texto . ".pdf";
                                    
                                    // Verifico de que existan los directorios.
                                    if(!is_dir($uploaddir))
                                    {
                                        mkdir($uploaddir, "0777", true);
                                    }

                                    // Muevo el archivo.
                                    if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $uploadfile))
                                    {
                                        // Agrego un nuevo texto a la base de datos.
                                        $modelTexto = new models\TextoModel();
                                        $modelTexto->_creadoPor = $_SESSION['IdUsuario'];
                                        $modelTexto->_creadoDia = date("Y-m-d H:i:s");
                                        $modelTexto->_modificadoPor = null;
                                        $modelTexto->_modificadoDia = null;
                                        $modelTexto->_codInterno = $cod_texto;
                                        $modelTexto->_idMateria = $materia;
                                        $modelTexto->_idTipoTexto = 2;
                                        $modelTexto->_idTipoContenido = $contenido;
                                        $modelTexto->_nombre = $nombre;
                                        $modelTexto->_autor = $autor;
                                        $modelTexto->_docente = $docente;
                                        $modelTexto->_cantPaginas = $this->getPDFPages($uploadfile); //shell_exec("/usr/bin/pdfinfo " . $uploadfile . " | grep Pages: | awk '{print $2}'") or die("Error en la lectura de cantidad de paginas del PDF.");
                                        $modelTexto->_activo = $activo == "on" ? true : false;
                                        $this->_lastIdTexto = $this->_model['Textos']->Insert(array($modelTexto));
                                        unset($modelTexto);
                                        echo "CARGA CORRECTA DEL TEXTO\n";
                                    }
                                    else
                                    {
                                        echo "¡Posible ataque de carga de archivos!\n";
                                    }                    
                                    break;

                                case UPLOAD_ERR_INI_SIZE:
                                    trigger_error("El archivo subido excede la directiva upload_max_filesize en php.ini.", E_USER_ERROR);
                                    break;

                                case UPLOAD_ERR_FORM_SIZE:
                                    trigger_error("El archivo subido excede la directiva MAX_FILE_SIZE que fue especificada en el formulario HTML.", E_USER_ERROR);
                                    break;

                                case UPLOAD_ERR_PARTIAL:
                                    trigger_error("El archivo subido fue sólo parcialmente cargado.", E_USER_ERROR);    
                                    break;

                                case UPLOAD_ERR_NO_FILE:
                                    trigger_error("Ningún archivo fue subido.", E_USER_WARNING);
                                    break;

                                case UPLOAD_ERR_NO_TMP_DIR:
                                    trigger_error("Falta la carpeta temporal.", E_USER_ERROR);    
                                    break;

                                case UPLOAD_ERR_CANT_WRITE:
                                    trigger_error("No se pudo escribir el archivo en el disco.", E_USER_ERROR);    
                                    break;

                                case UPLOAD_ERR_EXTENSION:
                                    trigger_error("Una extensión de PHP detuvo la carga de archivos.", E_USER_ERROR);    
                                    break;

                                default:
                                    // Por que entro aca?
                                    break;
                            } 
                        }
                        
                        if(isset($_FILES["filePreview"]))
                        {
                            switch($_FILES['filePreview']['error'])
                            {
                                case UPLOAD_ERR_OK:
                                    // Muevo el archivo al directorio donde van a estar todos los PDFs
                                    $uploaddir = BASE_DIR . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "texts" . DIRECTORY_SEPARATOR . $cod_carrera . DIRECTORY_SEPARATOR . $cod_nivel . DIRECTORY_SEPARATOR . $cod_materia . DIRECTORY_SEPARATOR . $contenido . DIRECTORY_SEPARATOR;
                                    $uploadfile = $uploaddir . $cod_texto . ".jpg"; //. explode("/", $_FILES["filePreview"]["type"])[1];

                                    // Verifico de que existan los directorios.
                                    if(!is_dir($uploaddir))
                                    {
                                        mkdir($uploaddir, "0777", true);
                                    }

                                    // Muevo el archivo.
                                    if(move_uploaded_file($_FILES['filePreview']['tmp_name'], $uploadfile))
                                    {
                                        //echo "El archivo es válido y fue cargado exitosamente.\n";
                                    }
                                    else
                                    {
                                        echo "¡Posible ataque de carga de archivos!\n";
                                    }                    
                                    break;

                                case UPLOAD_ERR_INI_SIZE:
                                    trigger_error("El archivo subido excede la directiva upload_max_filesize en php.ini.", E_USER_ERROR);
                                    break;

                                case UPLOAD_ERR_FORM_SIZE:
                                    trigger_error("El archivo subido excede la directiva MAX_FILE_SIZE que fue especificada en el formulario HTML.", E_USER_ERROR);
                                    break;

                                case UPLOAD_ERR_PARTIAL:
                                    trigger_error("El archivo subido fue sólo parcialmente cargado.", E_USER_ERROR);    
                                    break;

                                case UPLOAD_ERR_NO_FILE:
                                    //trigger_error("Ningún archivo fue subido.", E_USER_WARNING);
                                    break;

                                case UPLOAD_ERR_NO_TMP_DIR:
                                    trigger_error("Falta la carpeta temporal.", E_USER_ERROR);    
                                    break;

                                case UPLOAD_ERR_CANT_WRITE:
                                    trigger_error("No se pudo escribir el archivo en el disco.", E_USER_ERROR);    
                                    break;

                                case UPLOAD_ERR_EXTENSION:
                                    trigger_error("Una extensión de PHP detuvo la carga de archivos.", E_USER_ERROR);    
                                    break;

                                default:
                                    // Por que entro aca?    
                                    break;
                            }
                        }
                    }
                }
            }
            
            $this->result = $this->_model['Carreras']->Select();
            //var_dump($this->result);
            if(count($this->result) > 0)
            {
                foreach($this->result as $row)
                {
                    $filename = BASE_DIR . "/mvc/templates/textos/combo_carrera.html";
                    $this->combo_carreras .= file_get_contents($filename);
                    
                    if(is_array($row))
                    {
                        foreach($row as $key => $value)
                        {
                            $this->combo_carreras = str_replace('{' . $key . '}', $value, $this->combo_carreras);
                            $this->combo_carreras = str_replace('{Seleccionado}', "", $this->combo_carreras);
                        }
                    }
                }
            }
            unset($this->result);
            
            $this->result = $this->_model["Contenidos"]->Select();
            //var_dump($this->result);
            if(count($this->result) > 0)
            {
                foreach($this->result as $row)
                {
                    $filename = BASE_DIR . "/mvc/templates/textos/combo_contenido.html";
                    $this->combo_contenidos .= file_get_contents($filename);
                    
                    if(is_array($row))
                    {
                        foreach($row as $key => $value)
                        {
                            $this->combo_contenidos = str_replace('{' . $key . '}', $value, $this->combo_contenidos);
                            $this->combo_contenidos = str_replace('{Seleccionado}', "", $this->combo_contenidos);
                        }
                    }
                }
            }
            unset($this->result);
        }

        /*
         * @deprecated
         */
        public function ajax_upload_progress()
        {
            if(!empty($_POST))
            {
                var_dump($_POST, $_SESSION, $_FILES);
                
                $progressKey = ini_get("session.upload_progress.prefix") . "frmCrearTexto";
                $current = $_SESSION[$progressKey]["bytes_processed"];
                $total = $_SESSION[$progressKey]["content_length"];

                echo $current < $total ? ceil($current / $total * 100) : 100;
            }
        }
        
        public function delete($id)
        {
            $this->_template = BASE_DIR . "/mvc/templates/textos/{$this->_action}.html";
            
            if(!empty($_POST))
            {
                //var_dump($_POST);
                
                if(isset($_POST["btnBorrar"]))
                {
                    // Borro el archivo localmente.
                    $modelTexto = new models\TextoModel();
                    $modelTexto->_idTexto = $id;
                    $this->resultTexto = $this->_model["Textos"]->Select($modelTexto);
                    unset($modelTexto);
                    //var_dump($this->resultTexto);
                    if(count($this->resultTexto) > 0)
                    {
                        $filename = BASE_DIR . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "texts" . DIRECTORY_SEPARATOR . $this->resultTexto[0]["RutaArchivo"];
                        
                        if(file_exists($filename . ".pdf"))
                        {
                            unlink($filename . ".pdf");
                        }
                        
                        if(file_exists($filename . ".jpg"))
                        {
                            unlink($filename . ".jpg");
                        }
                    }
                    else
                    {
                        trigger_error("No se encontró el texto.", E_USER_ERROR);
                    }
                    unset($this->resultTexto);
                    
                    // Borro el texto de la base de datos
                    $modelTexto = new models\TextoModel();
                    $modelTexto->_idTexto = $id;
                    $this->_model["Textos"]->Delete(array($modelTexto));
                    unset($modelTexto);
                    
                    header("Location: index.php?do=/textos/index");
                }
            }
            
            $modelTexto = new models\TextoModel();
            $modelTexto->_idTexto = $id;
            $this->result = $this->_model["Textos"]->Select($modelTexto);
            unset($modelTexto);
            //var_dump($this->result);
            
            if(count($this->result) == 1)
            {
                foreach($this->result[0] as $key => $value)
                {
                    $this->{$key} = $value;
                }
            }
            unset($this->result);
        }

        public function detail($id)
        {
            // seteo el template
            $this->_template = BASE_DIR . "/mvc/templates/textos/{$this->_action}.html";
            
            $modelTexto = new models\TextoModel();
            $modelTexto->_idTexto = $id;
            $this->result = $this->_model["Textos"]->Select($modelTexto);
            //var_dump($this->result);
            if(count($this->result) == 1)
            {
                foreach($this->result[0] as $key => $value)
                {
                    switch($key)
                    {
                        case "Activo":
                            $this->{$key} = $value == 1 ? "checked=\"checked\"" : "";
                            break;

                        default:
                            $this->{$key} = $value;
                            break;
                    }
                }
            }
            else
            {
                // Por que no encontro el registro?
            }
            
            $modelContenido = new models\TipoContenidoModel();
            $modelContenido->_idContenido = $this->result[0]["IdTipoContenido"];
            $this->result2 = $this->_model["Contenidos"]->Select($modelContenido);
            //var_dump($this->result2);
            if(count($this->result2) > 0)
            {
                $this->Contenido = $this->result2[0]["Descripcion"];
            }
            unset($this->result2);
            unset($this->result);
        }

        public function index()
        {
            $this->_template = BASE_DIR . "/mvc/templates/textos/{$this->_action}.html";
            
            if(!empty($_POST))
            {
                //var_dump($_POST);
                
                if(isset($_POST["btnFiltrar"]))
                {
                    $this->paginator_total_pages = 0;
                    
                    $materia = filter_input(INPUT_POST, "ddlMateria", FILTER_SANITIZE_NUMBER_INT);
                    $textos = filter_input(INPUT_POST, "txtTexto", FILTER_SANITIZE_SPECIAL_CHARS);

                    $modelTexto = new models\TextoModel();
                    $modelTexto->_idMateria = $materia;

                    if(empty($textos))
                    {
                        $modelTexto->_descripcion = $textos;

                        $this->resultTextos = $this->_model['Textos']->SelectByIdMateriaAndDescripcion($modelTexto);
                    }
                    else
                    {
                        $this->resultTextos = $this->_model['Textos']->SelectByIdMateria($modelTexto);
                    }
                }
                else
                {
                    $this->resultTextos = $this->_model['Textos']->Select();
                }
            }
            else
            {
                $this->resultTextos = $this->_model['Textos']->Select();
                
                $this->resultTotalTextos = $this->_model['Textos']->SelectCountTotal();
                $this->paginator_total_pages = $this->resultTotalTextos[0]["Total"];
            }
            
            if(count($this->resultTextos) > 0)
            {
                foreach($this->resultTextos as $row)
                {
                    if(is_array($row))
                    {
                        $filename = BASE_DIR . "/mvc/templates/textos/{$this->_action}_table_row.html";
                        $this->table_content .= file_get_contents($filename);

                        foreach($row as $key => $value)
                        {
                            if(!is_array($value))
                            {
                                switch($key)
                                {
                                    case "Activo":
                                        $this->table_content = str_replace('{' . $key . '}', $value == 1 ? "checked=\"checked\"" : "", $this->table_content);
                                        break;
                                    default:
                                        $this->table_content = str_replace('{' . $key . '}', $value, $this->table_content);
                                        
                                        if(in_array($_SESSION['Roles']['Nombre'], array('Administrador')))
                                        {
                                            $file_button_delete = BASE_DIR . "/mvc/templates/textos/{$this->_action}_table_row_button_delete.html";
                                            $button = file_get_contents($file_button_delete);
                                            $button = str_replace("{IdTexto}", $row["IdTexto"], $button);

                                            $this->table_content = str_replace("{button_delete}", $button, $this->table_content);
                                        }
                                        else
                                        {
                                            $this->table_content = str_replace("{button_delete}", "", $this->table_content);
                                        }
                                        break;
                                }
                            }
                        }
                    }
                }
            }
            else
            {
                $this->table_content = "";
            }
            unset($this->resultTextos);
            
            $this->result = $this->_model['Carreras']->Select();
            if(count($this->result) > 0)
            {
                foreach($this->result as $row)
                {
                    $filename = BASE_DIR . "/mvc/templates/textos/combo_carrera.html";
                    $this->combo_carreras .= file_get_contents($filename);
                    
                    if(is_array($row))
                    {
                        foreach($row as $key => $value)
                        {
                            $this->combo_carreras = str_replace('{' . $key . '}', $value, $this->combo_carreras);
                        }
                    }
                }
            }
            unset($this->result);
        }

        public function update($id)
        {
            $this->_template = BASE_DIR . "/mvc/templates/textos/{$this->_action}.html";
            
            if(!empty($_POST))
            {
                //var_dump($_POST);
                
                if(isset($_POST["btnGuardar"]))
                {
                    $nombre = filter_input(INPUT_POST, "txtNombre", FILTER_SANITIZE_SPECIAL_CHARS);
                    $cod_texto = filter_input(INPUT_POST, "txtCodigo", FILTER_SANITIZE_NUMBER_INT);
                    $carrera = filter_input(INPUT_POST, "ddlCarrera", FILTER_SANITIZE_NUMBER_INT);
                    $nivel = filter_input(INPUT_POST, "ddlNivel", FILTER_SANITIZE_NUMBER_INT);
                    $materia = filter_input(INPUT_POST, "ddlMateria", FILTER_SANITIZE_NUMBER_INT);
                    $contenido = filter_input(INPUT_POST, "ddlTipoContenido", FILTER_SANITIZE_NUMBER_INT);
                    $autor = filter_input(INPUT_POST, "txtAutor", FILTER_SANITIZE_SPECIAL_CHARS);
                    $docente = filter_input(INPUT_POST, "txtDocente", FILTER_SANITIZE_SPECIAL_CHARS);
                    $activo = filter_input(INPUT_POST, "chkActivo", FILTER_SANITIZE_STRING);
                    
                    $modelTexto = new models\TextoModel();
                    $modelTexto->_idTexto = $id;
                    $this->result = $this->_model["Textos"]->Select($modelTexto);
                    
                    $modelTexto->_creadoPor = $this->result[0]["CreadoPor"];
                    $modelTexto->_creadoDia = $this->result[0]["Creado"];
                    $modelTexto->_modificadoPor = $_SESSION['IdUsuario'];
                    $modelTexto->_modificadoDia = date("Y-m-d H:i:s");
                    $modelTexto->_codInterno = $cod_texto;
                    $modelTexto->_idMateria = $materia;
                    $modelTexto->_idTipoTexto = 2;
                    $modelTexto->_idTipoContenido = $contenido;
                    $modelTexto->_nombre = $nombre;
                    $modelTexto->_autor = $autor;
                    $modelTexto->_docente = $docente;
                    $modelTexto->_cantPaginas = $this->result[0]["Paginas"]; //shell_exec("/usr/bin/pdfinfo " . $uploadfile . " | grep Pages: | awk '{print $2}'") or die("Error en la lectura de cantidad de paginas del PDF.");
                    $modelTexto->_activo = $activo == "on" ? true : false;
                    
                    if(!empty($_FILES))
                    {
                        //var_dump($_FILES);
                        
                        $uploadDir = null;
                        
                        if(isset($_FILES['fileToUpload']))
                        {
                            switch($_FILES['fileToUpload']['error'])
                            {
                                case UPLOAD_ERR_OK:
                                    $modelCarrera = new models\CarreraModel();
                                    $modelCarrera->_idCarrera = $carrera;
                                    $this->resultCarrera = $this->_model["Carreras"]->Select($modelCarrera);
                                    //var_dump($this->resultCarrera);
                                    $cod_carrera = count($this->resultCarrera) == 1 ? $this->resultCarrera[0]["Codigo"] : null;
                                    unset($modelCarrera);

                                    $modelNivel = new models\NivelModel();
                                    $modelNivel->_idNivel = $nivel;
                                    $this->resultNivel = $this->_model["Niveles"]->Select($modelNivel);
                                    //var_dump($this->resultNivel);
                                    $cod_nivel = count($this->resultNivel) == 1 ? $this->resultNivel[0]["Ano"] : null;
                                    unset($modelNivel);

                                    $modelMateria = new models\MateriaModel();
                                    $modelMateria->_idMateria = $materia;
                                    $this->resultMateria = $this->_model["Materias"]->Select($modelMateria);
                                    //var_dump($this->resultMateria);
                                    $cod_materia = count($this->resultMateria) == 1 ? $this->resultMateria[0]["CodMateria"] : null;
                                    unset($modelMateria);

                                    $uploaddir = BASE_DIR . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "texts" . DIRECTORY_SEPARATOR . $cod_carrera . DIRECTORY_SEPARATOR . $cod_nivel . DIRECTORY_SEPARATOR . $cod_materia . DIRECTORY_SEPARATOR . $contenido . DIRECTORY_SEPARATOR;
                                    $uploadfile = $uploaddir . $cod_texto . ".pdf";
                                    
                                    // Verifico de que existan los directorios.
                                    if(!is_dir($uploaddir))
                                    {
                                        mkdir($uploaddir, "0777", true);
                                    }

                                    // Muevo el archivo.
                                    if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $uploadfile))
                                    {
                                        $modelTexto->_cantPaginas = $this->getPDFPages($uploadfile); //shell_exec("/usr/bin/pdfinfo " . $uploadfile . " | grep Pages: | awk '{print $2}'") or die("Error en la lectura de cantidad de paginas del PDF.");
                                    }
                                    else
                                    {
                                        echo "¡Posible ataque de carga de archivos!\n";
                                    }                    
                                    break;

                                case UPLOAD_ERR_INI_SIZE:
                                    trigger_error("El archivo subido excede la directiva upload_max_filesize en php.ini.", E_USER_ERROR);
                                    break;

                                case UPLOAD_ERR_FORM_SIZE:
                                    trigger_error("El archivo subido excede la directiva MAX_FILE_SIZE que fue especificada en el formulario HTML.", E_USER_ERROR);
                                    break;

                                case UPLOAD_ERR_PARTIAL:
                                    trigger_error("El archivo subido fue sólo parcialmente cargado.", E_USER_ERROR);    
                                    break;

                                case UPLOAD_ERR_NO_FILE:
                                    //trigger_error("Ningún archivo fue subido.", E_USER_WARNING);
                                    break;

                                case UPLOAD_ERR_NO_TMP_DIR:
                                    trigger_error("Falta la carpeta temporal.", E_USER_ERROR);    
                                    break;

                                case UPLOAD_ERR_CANT_WRITE:
                                    trigger_error("No se pudo escribir el archivo en el disco.", E_USER_ERROR);    
                                    break;

                                case UPLOAD_ERR_EXTENSION:
                                    trigger_error("Una extensión de PHP detuvo la carga de archivos.", E_USER_ERROR);    
                                    break;

                                default:
                                    // Por que entro aca?
                                    break;
                            } 
                        }
                        
                        if(isset($_FILES["filePreview"]))
                        {
                            switch($_FILES['filePreview']['error'])
                            {
                                case UPLOAD_ERR_OK:
                                    // Muevo el archivo al directorio donde van a estar todos los PDFs
                                    $uploaddir = BASE_DIR . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "texts" . DIRECTORY_SEPARATOR . $cod_carrera . DIRECTORY_SEPARATOR . $cod_nivel . DIRECTORY_SEPARATOR . $cod_materia . DIRECTORY_SEPARATOR . $contenido . DIRECTORY_SEPARATOR;
                                    $uploadfile = $uploaddir . $cod_texto . ".jpg"; //explode("/", $_FILES["filePreview"]["type"])[1];

                                    // Verifico de que existan los directorios.
                                    if(!is_dir($uploaddir))
                                    {
                                        mkdir($uploaddir, "0777", true);
                                    }

                                    // Muevo el archivo.
                                    if(move_uploaded_file($_FILES['filePreview']['tmp_name'], $uploadfile))
                                    {
                                        //echo "El archivo es válido y fue cargado exitosamente.\n";
                                    }
                                    else
                                    {
                                        echo "¡Posible ataque de carga de archivos!\n";
                                    }                    
                                    break;

                                case UPLOAD_ERR_INI_SIZE:
                                    trigger_error("El archivo subido excede la directiva upload_max_filesize en php.ini.", E_USER_ERROR);
                                    break;

                                case UPLOAD_ERR_FORM_SIZE:
                                    trigger_error("El archivo subido excede la directiva MAX_FILE_SIZE que fue especificada en el formulario HTML.", E_USER_ERROR);
                                    break;

                                case UPLOAD_ERR_PARTIAL:
                                    trigger_error("El archivo subido fue sólo parcialmente cargado.", E_USER_ERROR);    
                                    break;

                                case UPLOAD_ERR_NO_FILE:
                                    //trigger_error("Ningún archivo fue subido.", E_USER_WARNING);
                                    break;

                                case UPLOAD_ERR_NO_TMP_DIR:
                                    trigger_error("Falta la carpeta temporal.", E_USER_ERROR);    
                                    break;

                                case UPLOAD_ERR_CANT_WRITE:
                                    trigger_error("No se pudo escribir el archivo en el disco.", E_USER_ERROR);    
                                    break;

                                case UPLOAD_ERR_EXTENSION:
                                    trigger_error("Una extensión de PHP detuvo la carga de archivos.", E_USER_ERROR);    
                                    break;

                                default:
                                    // Por que entro aca?    
                                    break;
                            }
                        }
                    }
                    
                    $this->_model["Textos"]->Update(array($modelTexto));
                    unset($modelTexto);
                }
            }
            
            $modelTexto = new models\TextoModel();
            $modelTexto->_idTexto = $id;
            $this->result = $this->_model["Textos"]->Select($modelTexto);
            //var_dump($this->result);
            unset($modelTexto);
            if(count($this->result) == 1)
            {
                foreach($this->result[0] as $key => $value)
                {
                    switch($key)
                    {
                        case "Activo":
                            $this->{$key} = $value ? "checked=\"checked\"" : "";
                            break;

                        default:
                            $this->{$key} = $value;
                            break;
                    }
                }
            }
            
            $modelCarrera = new models\CarreraModel();
            $modelCarrera->_idCarrera = $this->result[0]["IdCarrera"];
            $this->result2 = $this->_model["Carreras"]->SelectWithMark($modelCarrera);
            //var_dump($this->result2);
            unset($modelCarrera);
            if(count($this->result2) > 0)
            {
                foreach($this->result2 as $row)
                {
                    $filename = BASE_DIR . "/mvc/templates/textos/combo_carrera.html";
                    $this->combo_carreras .= file_get_contents($filename);
                    
                    if(is_array($row))
                    {
                        foreach($row as $key => $value)
                        {
                            $this->combo_carreras = str_replace('{' . $key . '}', $value, $this->combo_carreras);
                        }
                    }
                }
            }
            unset($this->result2);
            
            $modelNivel = new models\NivelModel();
            $modelNivel->_idCarrera = $this->result[0]["IdCarrera"];
            $modelNivel->_idNivel = $this->result[0]["IdNivel"];
            $this->result2 = $this->_model["Niveles"]->SelectWithMark($modelNivel);
            //var_dump($this->result2);
            unset($modelNivel);
            if(count($this->result2) > 0)
            {
                foreach($this->result2 as $row)
                {
                    $filename = BASE_DIR . "/mvc/templates/textos/combo_nivel.html";
                    $this->combo_niveles .= file_get_contents($filename);
                    
                    if(is_array($row))
                    {
                        foreach($row as $key => $value)
                        {
                            $this->combo_niveles = str_replace('{' . $key . '}', $value, $this->combo_niveles);
                        }
                    }
                }
            }
            unset($this->result2);
            
            $modelMateria = new models\MateriaModel();
            $modelMateria->_idNivel = $this->result[0]["IdNivel"];
            $modelMateria->_idMateria = $this->result[0]["IdMateria"];
            $this->result2 = $this->_model["Materias"]->SelectWithMark($modelMateria);
            //var_dump($this->result2);
            unset($modelMateria);
            if(count($this->result2) > 0)
            {
                foreach($this->result2 as $row)
                {
                    $filename = BASE_DIR . "/mvc/templates/textos/combo_materia.html";
                    $this->combo_materias .= file_get_contents($filename);
                    
                    if(is_array($row))
                    {
                        foreach($row as $key => $value)
                        {
                            $this->combo_materias = str_replace('{' . $key . '}', $value, $this->combo_materias);
                        }
                    }
                }
            }
            unset($this->result2);
            
            $modelContenido = new models\TipoContenidoModel();
            $modelContenido->_idContenido = $this->result[0]["IdTipoContenido"];
            $this->result2 = $this->_model["Contenidos"]->SelectWithMark($modelContenido);
            //var_dump($this->result2);
            unset($modelContenido);
            if(count($this->result2) > 0)
            {
                foreach($this->result2 as $row)
                {
                    $filename = BASE_DIR . "/mvc/templates/textos/combo_contenido.html";
                    $this->combo_contenidos .= file_get_contents($filename);
                    
                    if(is_array($row))
                    {
                        foreach($row as $key => $value)
                        {
                            $this->combo_contenidos = str_replace('{' . $key . '}', $value, $this->combo_contenidos);
                        }
                    }
                }
            }
            unset($this->result2);
        }
        
        public function ajax_get_textos_table_content()
        {
            $this->_renderRaw = true;
            
            if(!empty($_POST))
            {
                //var_dump($_POST);
                
                $return = "";
                
                $modelTexto = new models\TextoModel();
                $modelTexto->_page = filter_input(INPUT_POST, "page", FILTER_SANITIZE_NUMBER_INT);
                $modelTexto->_quantity = filter_input(INPUT_POST, "quantity", FILTER_SANITIZE_NUMBER_INT);
                $this->resultTextos = $this->_model['Textos']->SelectPagination($modelTexto);
                //var_dump($this->resultTextos);
                unset($modelTexto);
                
                foreach($this->resultTextos as $row)
                {
                    if(is_array($row))
                    {
                        $filename = BASE_DIR . "/mvc/templates/textos/index_table_row.html";
                        $return .= file_get_contents($filename);

                        foreach($row as $key => $value)
                        {
                            if(!is_array($value))
                            {
                                switch($key)
                                {
                                    case "Activo":
                                        $return = str_replace('{' . $key . '}', $value == 1 ? "checked=\"checked\"" : "", $return);
                                        break;
                                    default:
                                        $return = str_replace('{' . $key . '}', $value, $return);
                                        
                                        if(in_array($_SESSION['Roles']['Nombre'], array('Administrador')))
                                        {
                                            $file_button_delete = BASE_DIR . "/mvc/templates/textos/index_table_row_button_delete.html";
                                            $button = file_get_contents($file_button_delete);
                                            $button = str_replace("{IdTexto}", $row["IdTexto"], $button);

                                            $return = str_replace("{button_delete}", $button, $return);
                                        }
                                        else
                                        {
                                            $return = str_replace("{button_delete}", "", $return);
                                        }
                                        break;
                                }
                            }
                        }
                    }
                }
                unset($this->resultTextos);
                
                $this->result = $return;
            }
        }
        
        public function ajax_get_niveles()
        {
            if(!empty($_POST))
            {
                $this->_ajaxRequest = true;
                
                $nivelModel = new models\NivelModel();
                $nivelModel->_idCarrera = filter_input(INPUT_POST, 'idCarrera', FILTER_SANITIZE_NUMBER_INT);
                $this->result = $this->_model['Niveles']->SelectByIdCarrera($nivelModel);
            }
        }
        
        public function ajax_get_materias()
        {
            if(!empty($_POST))
            {
                $this->_ajaxRequest = true;
                
                $materiaModel = new models\MateriaModel();
                $materiaModel->_idNivel = filter_input(INPUT_POST, 'idNivel', FILTER_SANITIZE_NUMBER_INT);
                $this->result = $this->_model['Materias']->SelectByIdNivel($materiaModel);
            }
        }
        
        public function count_pages($pdfname)
        {
            
            $pdftext = file_get_contents($pdfname);
            $num = preg_match_all("/\/Page\W/", $pdftext, $dummy);
            
            return $num;
        }
        
        private function getPDFPages($document)
        {
            $output = null;
            $matches = null;
            
            //$cmd = "/var/pdfinfo";          // Linux
            //$cmd = "C:\\pdfinfo.exe";           // Windows

            $cmd = "E:\\pdfinfo.exe";
            
            // Parse entire output
            exec("$cmd $document", $output);

            // Iterate through lines
            $pagecount = 0;
            foreach($output as $op)
            {
                // Extract the number
                if(preg_match("/Pages:\s*(\d+)/i", $op, $matches) === 1)
                {
                    $pagecount = intval($matches[1]);
                    break;
                }
            }

            return $pagecount;
        }
    }
}

?>