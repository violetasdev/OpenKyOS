<?php
namespace reportes\adicionalActaPortatil\frontera;

if (!isset($GLOBALS["autorizado"])) {
    include "../index.php";
    exit();
}

/**
 * IMPORTANTE: Este formulario está utilizando jquery.
 * Por tanto en el archivo ready.php se declaran algunas funciones js
 * que lo complementan.
 */
class Registrador
{
    public $miConfigurador;
    public $lenguaje;
    public $miFormulario;
    public function __construct($lenguaje, $formulario, $sql)
    {
        $this->miConfigurador = \Configurador::singleton();

        $this->miConfigurador->fabricaConexiones->setRecursoDB('principal');

        $this->lenguaje = $lenguaje;

        $this->miFormulario = $formulario;

        $this->miSql = $sql;

        $conexion = "interoperacion";
        //$conexion = "produccion";
        $this->esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB($conexion);
    }
    public function seleccionarForm()
    {

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

            {

                $esteCampo = 'AgrupacionBeneficiario';
                $atributos['id'] = $esteCampo;
                $atributos['leyenda'] = "Procedimiento de Adición Hoja de Soporte a Acta de Entrega de Portatil";
                echo $this->miFormulario->agrupacion('inicio', $atributos);
                unset($atributos);

                {

                    // -------------Control texto-----------------------
                    $esteCampo = 'mostrarMensaje';
                    $atributos["tamanno"] = '';
                    $atributos["etiqueta"] = '';
                    $mensaje = '<b>ATENCIÓN se tendra en cuenta lo siguiente</b>:<br>
                                                1. <b>Se modificaran todas las actas de entrega  de portatil registradas en el sistema que no fueron procesadas anteriormente</b>.<br>
                                                2. Debe existir la acta de entrega de portatil ya registrada.<br>
                                                3. Debe existir el archivo en el servidor.<br>
                                                4. Se tendran en cuenta los formatos PDF, PNG,JPG, JPGE.<br>';

                    $atributos["mensaje"] = $mensaje;
                    $atributos["estilo"] = 'information'; // information,warning,error,validation
                    $atributos["columnas"] = ''; // El control ocupa 47% del tamaño del formulario
                    echo $this->miFormulario->campoMensaje($atributos);
                    unset($atributos);
                }

                echo $this->miFormulario->agrupacion('fin');
                unset($atributos);

                $esteCampo = 'municipio';
                $atributos['nombre'] = $esteCampo;
                $atributos['id'] = $esteCampo;
                $atributos['etiqueta'] = $this->lenguaje->getCadena($esteCampo);
                $atributos["etiquetaObligatorio"] = true;
                $atributos['tab'] = $tab++;
                $atributos['anchoEtiqueta'] = 2;
                $atributos['evento'] = '';

                if (isset($_REQUEST[$esteCampo])) {
                    $atributos['seleccion'] = $_REQUEST[$esteCampo];
                } else {
                    $atributos['seleccion'] = '-1';
                }
                $atributos['deshabilitado'] = false;
                $atributos['columnas'] = 1;
                $atributos['tamanno'] = 1;
                $atributos['ajax_function'] = "";
                $atributos['ajax_control'] = $esteCampo;
                $atributos['estilo'] = "bootstrap";
                $atributos['limitar'] = false;
                $atributos['anchoCaja'] = 10;
                $atributos['miEvento'] = '';
                $atributos['validar'] = 'required';
                $atributos['cadena_sql'] = ' ';
                $cadenaSql = $this->miSql->getCadenaSql('consultarMunicipio');
                $resultado = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");
                $matrizItems = $resultado;
                $atributos['matrizItems'] = $matrizItems;
                // Aplica atributos globales al control
                $atributos = array_merge($atributos, $atributosGlobales);
                echo $this->miFormulario->campoCuadroListaBootstrap($atributos);
                unset($atributos);

                // ------------------Division para los botones-------------------------
                $atributos["id"] = "botones";
                $atributos["estilo"] = "marcoBotones";
                $atributos["estiloEnLinea"] = "display:block;";
                echo $this->miFormulario->division("inicio", $atributos);
                unset($atributos);
                {
                    // -----------------CONTROL: Botón ----------------------------------------------------------------
                    $esteCampo = 'botonGenerar';
                    $atributos["id"] = $esteCampo;
                    $atributos["tabIndex"] = $tab;
                    $atributos["tipo"] = 'boton';
                    // submit: no se coloca si se desea un tipo button genérico
                    $atributos['submit'] = true;
                    $atributos["simple"] = true;
                    $atributos["estiloMarco"] = '';
                    $atributos["estiloBoton"] = 'default';
                    $atributos["block"] = false;
                    // verificar: true para verificar el formulario antes de pasarlo al servidor.
                    $atributos["verificar"] = '';
                    $atributos["tipoSubmit"] = 'jquery'; // Dejar vacio para un submit normal, en este caso se ejecuta la función submit declarada en ready.js
                    $atributos["valor"] = $this->lenguaje->getCadena($esteCampo);
                    $atributos['nombreFormulario'] = $esteBloque['nombre'];
                    $tab++;

                    // Aplica atributos globales al control
                    $atributos = array_merge($atributos, $atributosGlobales);
                    echo $this->miFormulario->campoBotonBootstrapHtml($atributos);
                    unset($atributos);
                    // -----------------FIN CONTROL: Botón -----------------------------------------------------------
                }
                // ------------------Fin Division para los botones-------------------------
                echo $this->miFormulario->division("fin");
                unset($atributos);
            }

            {
                /**
                 * En algunas ocasiones es útil pasar variables entre las diferentes páginas.
                 * SARA permite realizar esto a través de tres
                 * mecanismos:
                 * (a). Registrando las variables como variables de sesión. Estarán disponibles durante toda la sesión de usuario. Requiere acceso a
                 * la base de datos.
                 * (b). Incluirlas de manera codificada como campos de los formularios. Para ello se utiliza un campo especial denominado
                 * formsara, cuyo valor será una cadena codificada que contiene las variables.
                 * (c) a través de campos ocultos en los formularios. (deprecated)
                 */

                // En este formulario se utiliza el mecanismo (b) para pasar las siguientes variables:

                // Paso 1: crear el listado de variables
                $valorCodificado = "action=" . $esteBloque["nombre"];
                $valorCodificado .= "&pagina=" . $this->miConfigurador->getVariableConfiguracion('pagina');
                $valorCodificado .= "&bloque=" . $esteBloque['nombre'];
                $valorCodificado .= "&bloqueGrupo=" . $esteBloque["grupo"];
                $valorCodificado .= "&opcion=generar";

                /**
                 * SARA permite que los nombres de los campos sean dinámicos.
                 * Para ello utiliza la hora en que es creado el formulario para
                 * codificar el nombre de cada campo.
                 */
                $valorCodificado .= "&campoSeguro=" . $_REQUEST['tiempo'];
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
        }

        // ----------------FINALIZAR EL FORMULARIO ----------------------------------------------------------
        // Se debe declarar el mismo atributo de marco con que se inició el formulario.
        $atributos['marco'] = true;
        $atributos['tipoEtiqueta'] = 'fin';
        echo $this->miFormulario->formulario($atributos);

        if (isset($_REQUEST['mensaje'])) {

            $this->mensajeModal();

        }

    }

    public function mensajeModal()
    {

        switch ($_REQUEST['mensaje']) {

            case 'registrosProcesados':
                $mensaje = "Exito en Procedimiento<br>Registros Procesados : <b>" . $_REQUEST['cantidad_registros'] . "</b>";
                $atributos['estiloLinea'] = 'success'; //success,error,information,warning
                break;

            case 'sinResultadosDocumentos':
                $mensaje = "Error<br>No Existen Documentos de Ata de Portatil Asociados al Municipio";
                $atributos['estiloLinea'] = 'error'; //success,error,information,warning
                break;

            case 'sinRegistrosProcesados':
                $mensaje = "Error<br>No se procesaron los documentos dado que no existen en el servidor o ya se procesaron las actas del municipio";
                $atributos['estiloLinea'] = 'error'; //success,error,information,warning
                break;

        }

        // ----------------INICIO CONTROL: Ventana Modal Beneficiario Eliminado---------------------------------

        $atributos['tipoEtiqueta'] = 'inicio';
        $atributos['titulo'] = 'Mensaje';
        $atributos['id'] = 'mensajeModal';
        echo $this->miFormulario->modal($atributos);
        unset($atributos);

        // ----------------INICIO CONTROL: Mapa--------------------------------------------------------
        echo '<div style="text-align:center;">';

        echo '<p><h5>' . $mensaje . '</h5></p>';

        echo '</div>';

        // ----------------FIN CONTROL: Mapa--------------------------------------------------------

        echo '<div style="text-align:center;">';

        echo '</div>';

        $atributos['tipoEtiqueta'] = 'fin';
        echo $this->miFormulario->modal($atributos);
        unset($atributos);

    }

}

$miSeleccionador = new Registrador($this->lenguaje, $this->miFormulario, $this->sql);

$miSeleccionador->seleccionarForm();
