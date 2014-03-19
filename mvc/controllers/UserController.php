<?php

namespace CEIT\mvc\controllers
{
    use \CEIT\core;
    use \CEIT\mvc\models;
    use \CEIT\mvc\views;
    
    final class UserController extends core\AController
    {
        public function __construct()
        {
            parent::__construct();
            
            if(empty($this->_model))
            {
                $this->_model = array(
                    'Personas'      =>  new models\PersonaModel(),
                    'Estudiantes'   =>  new models\EstudianteModel(),
                    'Docentes'      =>  new models\DocenteModel(),
                    'Usuarios'      =>  new models\UsuarioModel(),
                    'Carreras'      =>  new models\CarreraModel(),
                );
            }
            
            if(empty($this->_view))
            {
                $this->_view = new views\UserView();
            }
            
            $this->ajaxRequest = false;
        }
        
        public function __destruct()
        {
            parent::__destruct();
            
            if($this->ajaxRequest)
            {
                $this->_view->json($this->result);
            }
            else
            {
                $this->_view->render($this->_template, $this->_dataCollection);
            }
            
            unset($this->result);
        }
        
        public function profile($id)
        {
            $this->_template = BASE_DIR . "/mvc/templates/user/{$this->_action}.html";
            
            $modelCarrera = new models\CarreraModel();
            $modelCarrera->_idUsuario = $id;
            $this->result = $this->_model['Carreras']->SelectByIdUsuario($modelCarrera);
            var_dump($this->result);
            if(count($this->result) > 0)
            {
                foreach($this->result as $row)
                {
                    $filename = BASE_DIR . "/mvc/templates/user/combo_carrera.html";
                    $this->combo_carrera .= file_get_contents($filename);

                    if(is_array($row)  && $row['IdCarrera'] != 1)
                    {
                        foreach($row as $key => $value)
                        {
                            $this->combo_carrera = str_replace('{' . $key . '}', $value, $this->combo_carrera);
                        }
                    }
                }
            }
            unset($this->result);
            
            $param = new models\UsuarioModel();
            $param->_idUsuario = $id;
            $this->result = $this->_model['Usuarios']->Select($param);
            if(count($this->result, COUNT_NORMAL))
            {
                foreach($this->result[0] as $key => $value)
                {
                    $this->{$key} = $value;
                }
            }
            else
            {
                trigger_error("No se encontro un registro para el Id de usuario " . $id, E_USER_WARNING);
            }
        }
        
        public function change_password($id)
        {
            $this->_template = BASE_DIR . "/mvc/templates/user/{$this->_action}.html";
            
            if(!empty($_POST))
            {
                var_dump($_POST);
            }
        }
        
        public function fill_estudiante($id)
        {
            $this->_template = BASE_DIR . "/mvc/templates/user/{$this->_action}.html";
            
            if(!empty($_POST))
            {
                var_dump($_POST);
                
                if(isset($_POST['btnGuardar']))
                {
                    $persona = filter_input(INPUT_POST, 'hidIdPersona', FILTER_SANITIZE_NUMBER_INT);
                    $legajo = filter_input(INPUT_POST, 'hidLegajo', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $dni = filter_input(INPUT_POST, 'hidDNI', FILTER_SANITIZE_NUMBER_INT);
                    $carrera = filter_input(INPUT_POST, 'ddlCarrera', FILTER_SANITIZE_NUMBER_INT);
                    $usuario = filter_input(INPUT_POST, 'txtUsuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $contrasena1 = filter_input(INPUT_POST, 'txtContrasena1', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    //$contrasena2 = filter_input(INPUT_POST, 'txtContrasena2', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $nombre = filter_input(INPUT_POST, 'txtNombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $apellido = filter_input(INPUT_POST, 'txtApellido', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $email = filter_input(INPUT_POST, 'txtEmail', FILTER_SANITIZE_EMAIL);
                    $telefono = filter_input(INPUT_POST, 'txtTelefono', FILTER_SANITIZE_NUMBER_INT);
                    $celular = filter_input(INPUT_POST, 'txtCelular', FILTER_SANITIZE_NUMBER_INT);
                    
                    $modelPersona = new models\PersonaModel();
                    $modelPersona->_idPersona = $persona;
                    $modelPersona->_nombre = $nombre;
                    $modelPersona->_apellido = $apellido;
                    $modelPersona->_dni = $dni;
                    $modelPersona->_telefono = $telefono;
                    $modelPersona->_celular = $celular;
                    $modelPersona->_email = $email;
                    $this->result = $this->_model['Personas']->Update(array($modelPersona));
                    
                    $modelEstudiante = new models\EstudianteModel();
                    $modelEstudiante->_idPersona = $persona;
                    $modelEstudiante->_legajo = $legajo;
                    $modelEstudiante->_idCarrera = $carrera;
                    //$this->result = $this->_model['Estudiantes']->UpdateByIdPersona($modelEstudiante);
                    
                    $modelUsuario = new models\UsuarioModel();
                    $modelUsuario->_idUsuario = $_SESSION['IdUsuario'];
                    $modelUsuario->_idPersona = $persona;
                    $modelUsuario->_usuario = $usuario;
                    $modelUsuario->_contrasena = $contrasena1; //crypt($contrasena1);
                    $modelUsuario->_emailValidado = false;
                    $this->result = $this->_model['Usuarios']->Update(array($modelUsuario));
                    
                    //header("Location: index.php?do=/dashboard/index");
                }
            }
            
            // Cargo los pocos datos del usuario.
            $modelEstudiante = new models\EstudianteModel();
            $modelEstudiante->_idUsuario = (int)$id;
            $this->result = $this->_model['Estudiantes']->SelectByIdUsuario($modelEstudiante);
            //var_dump($this->result);
            if(count($this->result) == 1)
            {
                foreach($this->result as $row)
                {
                    if(is_array($row))
                    {
                        foreach($row as $key => $value)
                        {
                            $this->{$key} = $value;
                        }
                    }
                }
            }
            unset($this->result);
            
            // Cargo combo carreras
            $modelCarrera = new models\CarreraModel();
            $modelCarrera->_idUsuario = (int)$id;
            $this->result = $this->_model['Carreras']->SelectByIdUsuario($modelCarrera);
            if(count($this->result) > 0)
            {
                foreach($this->result as $row)
                {
                    $filename = BASE_DIR . "/mvc/templates/user/combo_carrera.html";
                    $this->combo_carrera .= file_get_contents($filename);

                    if(is_array($row) && $row['IdCarrera'] != 1)
                    {
                        foreach($row as $key => $value)
                        {
                            $this->combo_carrera = str_replace('{' . $key . '}', $value, $this->combo_carrera);
                        }
                    }
                }
            }
            unset($this->result);
        }
        
        public function fill_docente($id)
        {
            $this->_template = BASE_DIR . "/mvc/templates/user/{$this->_action}.html";
            
            if(!empty($_POST))
            {
                var_dump($_POST);
            }
        }
        
        public function fill_operador($id)
        {
            $this->_template = BASE_DIR . "/mvc/templates/user/{$this->_action}.html";
            
            if(!empty($_POST))
            {
                var_dump($_POST);
            }
        }
        
        public function logout()
        {
            // cierro session
            if(session_status() == PHP_SESSION_ACTIVE)
            {
                session_destroy();
            }
            
            // redirijo
            header("Location: index.php?do=/login/index");
        }
        
        public function ajax_check_available_user()
        {
            $this->ajaxRequest = true;
            
            if(!empty($_POST))
            {
                //var_dump($_POST);
                
                $modelUsuario = new models\UsuarioModel();
                $modelUsuario->_username = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $this->result = $this->_model['Usuarios']->SelectByUsername($modelUsuario);
                //var_dump($this->result);
                if(count($this->result) > 0)
                {
                    $this->result = array(false);
                }
                else
                {
                    $this->result = array(true);
                }
            }
        }
        
        public function ajax_check_available_email()
        {
            $this->ajaxRequest = true;
            
            if(!empty($_POST))
            {
                //var_dump($_POST);
                
                $modelUsuario = new models\UsuarioModel();
                $modelUsuario->_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                if(filter_var($modelUsuario->_email, FILTER_VALIDATE_EMAIL))
                {
                    $this->result = $this->_model['Usuarios']->SelectByEmail($modelUsuario);
                    //var_dump($this->result);
                    if(count($this->result) > 0)
                    {
                        $this->result = array(false);
                    }
                    else
                    {
                        $this->result = array(true);
                    }
                }
                else
                {
                    $this->result = array(false);
                }
            }
        }
    }
}

?>