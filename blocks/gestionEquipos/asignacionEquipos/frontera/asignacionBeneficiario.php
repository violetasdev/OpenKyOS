<?php
namespace gestionEquipos\asignacionEquipos\frontera;

/**
 * IMPORTANTE: Este formulario está utilizando jquery.
 * Por tanto en el archivo ready.php se declaran algunas funciones js
 * que lo complementan.
 */
class Registrador {
    public $miConfigurador;
    public $lenguaje;
    public $miFormulario;
    public function __construct($lenguaje, $formulario) {
        $this->miConfigurador = \Configurador::singleton();

        $this->miConfigurador->fabricaConexiones->setRecursoDB('principal');

        $this->lenguaje = $lenguaje;

        $this->miFormulario = $formulario;
    }
    public function seleccionarForm() {

        // Rescatar los datos de este bloque
        $esteBloque = $this->miConfigurador->getVariableConfiguracion("esteBloque");

        // ---------------- SECCION: Parámetros Globales del Formulario ----------------------------------

        $atributosGlobales['campoSeguro'] = 'true';

        $_REQUEST['tiempo'] = time();

        // -------------------------------------------------------------------------------------------------

        // ---------------- SECCION: Parámetros Generales del Formulario ----------------------------------
        $esteCampo = $esteBloque['nombre'];
        $atributos['id'] = $esteCampo;
        $atributos['nombre'] = $esteCampo;
        // Si no se coloca, entonces toma el valor predeterminado 'application/x-www-form-urlencoded'
        $atributos['tipoFormulario'] = '';
        // Si no se coloca, entonces toma el valor predeterminado 'POST'
        $atributos['metodo'] = 'POST';
        // Si no se coloca, entonces toma el valor predeterminado 'index.php' (Recomendado)
        $atributos['action'] = 'index.php';
        $atributos['titulo'] = $this->lenguaje->getCadena($esteCampo);
        // Si no se coloca, entonces toma el valor predeterminado.
        $atributos['estilo'] = '';
        $atributos['marco'] = true;
        $tab = 1;
        // ---------------- FIN SECCION: de Parámetros Generales del Formulario ----------------------------

        // ----------------INICIAR EL FORMULARIO ------------------------------------------------------------
        $atributos['tipoEtiqueta'] = 'inicio';
        echo $this->miFormulario->formulario($atributos);
        {

            /**
             * Código Formulario
             */

            $esteCampo = 'agrupacion';
            $atributos['id'] = $esteCampo;
            $atributos['leyenda'] = "Asignación de Equipos Beneficiarios";
            echo $this->miFormulario->agrupacion('inicio', $atributos);
            unset($atributos);
            {

                $esteCampo = 'beneficiario';
                $atributos['nombre'] = $esteCampo;
                $atributos['tipo'] = "text";
                $atributos['id'] = $esteCampo;
                $atributos['etiqueta'] = $this->lenguaje->getCadena($esteCampo);
                $atributos["etiquetaObligatorio"] = true;
                $atributos['tab'] = $tab++;
                $atributos['anchoEtiqueta'] = 1; // de 0 a 12
                $atributos['evento'] = '';
                if (isset($_REQUEST[$esteCampo])) {
                    $atributos['valor'] = $_REQUEST[$esteCampo];
                } else {
                    $atributos['valor'] = '';
                }
                $atributos['estilo'] = "bootstrap";
                $atributos['deshabilitado'] = false;
                $atributos['columnas'] = '';
                $atributos['tamanno'] = '';
                $atributos['placeholder'] = "Ingrese Identificación o  Nombre del Beneficiario"; // Texto de fondo de los campos.
                $atributos['minimo'] = "1"; // Para input tipo number se establece un mínimo
                $atributos['ajax_function'] = "";
                $atributos['ajax_control'] = $esteCampo;
                $atributos['limitar'] = false;
                $atributos['anchoCaja'] = 10; // de 0 a 12 teniendo en cuenta el anchoEtiqueta
                $atributos['miEvento'] = '';
                $atributos['validar'] = 'required'; // Valida campos obligatorios
                // Aplica atributos globales al control
                $atributos = array_merge($atributos, $atributosGlobales);
                echo $this->miFormulario->campoCuadroTextoBootstrap($atributos);
                unset($atributos);

                $esteCampo = 'id_beneficiario';
                $atributos["id"] = $esteCampo; // No cambiar este nombre
                $atributos["tipo"] = "hidden";
                $atributos['estilo'] = '';
                $atributos["obligatorio"] = false;
                $atributos['marco'] = true;
                $atributos["etiqueta"] = "";
                $atributos['validar'] = 'required';
                if (isset($_REQUEST[$esteCampo])) {
                    $atributos['valor'] = $_REQUEST[$esteCampo];
                } else {
                    $atributos['valor'] = '';
                }
                $atributos = array_merge($atributos, $atributosGlobales);
                echo $this->miFormulario->campoCuadroTexto($atributos);
                unset($atributos);

                // ------------------Division para los botones-------------------------
                $atributos["id"] = "botones";
                $atributos["estilo"] = "marcoBotones";
                echo $this->miFormulario->division("inicio", $atributos);
                {

                    // -----------------CONTROL: Botón ---------------
                    $esteCampo = 'botonAceptar';
                    $atributos["id"] = $esteCampo;
                    $atributos["tabIndex"] = $tab;
                    $atributos["tipo"] = 'boton';
                    // submit: no se coloca si se desea un tipo button genérico
                    $atributos['submit'] = true;
                    $atributos["estiloMarco"] = '';
                    $atributos["estiloBoton"] = 'btn btn-primary';
                    $atributos["block"] = false;
                    // verificar: true para verificar el formulario antes de pasarlo al servidor.
                    $atributos["verificar"] = '';
                    $atributos["tipoSubmit"] = 'jquery'; // Dejar vacio para un submit normal, en este caso se ejecuta la función submit declarada en ready.js
                    $atributos["valor"] = $this->lenguaje->getCadena($esteCampo);
                    $atributos['nombreFormulario'] = $esteBloque['nombre'];
                    $tab++;

                    // Aplica atributos globales al control
                    $atributos = array_merge($atributos, $atributosGlobales);
                    echo $this->miFormulario->campoBoton($atributos);
                    unset($atributos);

                }
                // ------------------Fin Division para los botones-------------------------
                echo $this->miFormulario->division("fin");
                unset($atributos);
            }
            echo $this->miFormulario->agrupacion('fin');
            unset($atributos);

            $valorCodificado = "actionBloque=" . $esteBloque["nombre"];
            $valorCodificado .= "&pagina=" . $this->miConfigurador->getVariableConfiguracion('pagina');
            $valorCodificado .= "&bloque=" . $esteBloque['nombre'];
            $valorCodificado .= "&bloqueGrupo=" . $esteBloque["grupo"];
            $valorCodificado .= "&opcion=asignarEquipo";

            /**
             * SARA permite que los nombres de los campos sean dinámicos.
             * Para ello utiliza la hora en que es creado el formulario para
             * codificar el nombre de cada campo. Si se utiliza esta técnica es necesario pasar dicho tiempo como una variable:
             * (a) invocando a la variable $_REQUEST ['tiempo'] que se ha declarado en ready.php o
             * (b) asociando el tiempo en que se está creando el formulario
             */
            $valorCodificado .= "&campoSeguro=" . $_REQUEST['tiempo'];
            $valorCodificado .= "&tiempo=" . time();
            // Paso 2: codificar la cadena resultante
            $valorCodificado = $this->miConfigurador->fabricaConexiones->crypto->codificar($valorCodificado);

            $atributos["id"] = "formSaraData"; // No cambiar este nombre
            $atributos["tipo"] = "hidden";
            $atributos['estilo'] = '';
            $atributos["obligatorio"] = false;
            $atributos['marco'] = true;
            $atributos["etiqueta"] = "";
            $atributos["valor"] = $valorCodificado;
            echo $this->miFormulario->campoCuadroTexto($atributos);
            unset($atributos);
        }

        // ----------------FINALIZAR EL FORMULARIO ----------------------------------------------------------
        // Se debe declarar el mismo atributo de marco con que se inició el formulario.
        $atributos['marco'] = true;
        $atributos['tipoEtiqueta'] = 'fin';
        echo $this->miFormulario->formulario($atributos);
    }
    public function mensaje() {

        // Si existe algun tipo de error en el login aparece el siguiente mensaje
        $mensaje = $this->miConfigurador->getVariableConfiguracion('mostrarMensaje');
        $this->miConfigurador->setVariableConfiguracion('mostrarMensaje', null);

        if ($mensaje) {
            $tipoMensaje = $this->miConfigurador->getVariableConfiguracion('tipoMensaje');
            if ($tipoMensaje == 'json') {

                $atributos['mensaje'] = $mensaje;
                $atributos['json'] = true;
            } else {
                $atributos['mensaje'] = $this->lenguaje->getCadena($mensaje);
            }
            // ------------------Division para los botones-------------------------
            $atributos['id'] = 'divMensaje';
            $atributos['estilo'] = 'marcoBotones';
            echo $this->miFormulario->division("inicio", $atributos);

            // -------------Control texto-----------------------
            $esteCampo = 'mostrarMensaje';
            $atributos["tamanno"] = '';
            $atributos["estilo"] = 'information';
            $atributos["etiqueta"] = '';
            $atributos["columnas"] = ''; // El control ocupa 47% del tamaño del formulario
            echo $this->miFormulario->campoMensaje($atributos);
            unset($atributos);

            // ------------------Fin Division para los botones-------------------------
            echo $this->miFormulario->division("fin");
        }
    }
}

$miSeleccionador = new Registrador($this->lenguaje, $this->miFormulario);

$miSeleccionador->mensaje();

$miSeleccionador->seleccionarForm();

?>