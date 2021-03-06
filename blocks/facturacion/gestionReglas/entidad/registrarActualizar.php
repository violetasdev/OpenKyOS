<?php
namespace facturacion\gestionReglas\entidad;

if (!isset($GLOBALS["autorizado"])) {
    include "../index.php";
    exit();
}

require_once 'Redireccionador.php';

class FormProcessor
{

    public $miConfigurador;
    public $lenguaje;
    public $miFormulario;
    public $miSql;
    public $conexion;
    public $archivos_datos;
    public $esteRecursoDB;

    public function __construct($lenguaje, $sql)
    {

        $this->miConfigurador = \Configurador::singleton();
        $this->miConfigurador->fabricaConexiones->setRecursoDB('principal');
        $this->lenguaje = $lenguaje;
        $this->miSql = $sql;

        $this->rutaURL = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site");

        $this->rutaAbsoluta = $this->miConfigurador->getVariableConfiguracion("raizDocumento");

        if (!isset($_REQUEST["bloqueGrupo"]) || $_REQUEST["bloqueGrupo"] == "") {
            $this->rutaURL .= "/blocks/" . $_REQUEST["bloque"] . "/";
            $this->rutaAbsoluta .= "/blocks/" . $_REQUEST["bloque"] . "/";
        } else {
            $this->rutaURL .= "/blocks/" . $_REQUEST["bloqueGrupo"] . "/" . $_REQUEST["bloque"] . "/";
            $this->rutaAbsoluta .= "/blocks/" . $_REQUEST["bloqueGrupo"] . "/" . $_REQUEST["bloque"] . "/";
        }
        //Conexion a Base de Datos
        $conexion = "interoperacion";
        $this->esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB($conexion);

        $_REQUEST['tiempo'] = time();

        switch ($_REQUEST['opcion']) {
            case 'registrarReglaParticular':
                $arreglo = array(
                    'descricion' => $_REQUEST['descripcion'],
                    'formula' => $_REQUEST['formula'],
                    'identificador' => $_REQUEST['identificador_formula'],
                );

                $cadenaSql = $this->miSql->getCadenaSql('registrarRegla', $arreglo);

                $this->proceso = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "acceso");

                if (isset($this->proceso) && $this->proceso != null) {
                    Redireccionador::redireccionar("ExitoRegistro", $this->proceso);
                } else {
                    Redireccionador::redireccionar("ErrorRegistro");
                }

                break;

            case 'actualizarReglaParticular':
                $arreglo = array(
                    'id_regla' => $_REQUEST['id_regla'],
                    'descricion' => $_REQUEST['descripcion'],
                    'formula' => $_REQUEST['formula'],
                    'identificador' => $_REQUEST['identificador_formula'],
                );

                $cadenaSql = $this->miSql->getCadenaSql('actualizarRegla', $arreglo);

                $this->proceso = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "acceso");

                if (isset($this->proceso) && $this->proceso != null) {
                    Redireccionador::redireccionar("ExitoActualizacion", $this->proceso);
                } else {
                    Redireccionador::redireccionar("ErrorActualizacion");
                }

                break;
        }
    }
}

$miProcesador = new FormProcessor($this->lenguaje, $this->sql);
