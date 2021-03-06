<?php
namespace facturacion\impresionFactura\entidad;

if (!isset($GLOBALS["autorizado"])) {
    include "index.php";
    exit();
}
class Redireccionador
{
    public static function redireccionar($opcion, $valor = "")
    {

        $miConfigurador = \Configurador::singleton();

        switch ($opcion) {

            case "ErrorRegistroProceso":
                $variable = 'pagina=impresionFactura';
                $variable .= '&mensaje=errorRegistroProceso';
                break;

            case "ExitoRegistroProceso":
                $variable = 'pagina=impresionFactura';
                $variable .= '&mensaje=exitoRegistroProceso';
                $variable .= '&proceso=' . $valor;
                break;

            case "SinResultado":
                $variable = 'pagina=impresionFactura';
                $variable .= '&mensaje=SinResultado';
                break;

            case "ErrorEliminarProceso":
                $variable = 'pagina=impresionFactura';
                $variable .= '&mensaje=errorEliminarProceso';
                break;

            case "ExitoEliminarProceso":
                $variable = 'pagina=impresionFactura';
                $variable .= '&mensaje=exitoEliminarProceso';
                break;

            case "ErrorNumeracionFacturacion":
                $variable = 'pagina=impresionFactura';
                $variable .= '&mensaje=errorNumeracionFacturacion';
                $variable .= '&numero_factura=' . $valor;
                break;

            case "ErrorNumeroBeneficiariosFacturar":
                $variable = 'pagina=impresionFactura';
                $variable .= '&mensaje=errorNumeroBeneficiariosFacturar';
                break;

        }

        foreach ($_REQUEST as $clave => $valor) {
            unset($_REQUEST[$clave]);
        }

        $url = $miConfigurador->configuracion["host"] . $miConfigurador->configuracion["site"] . "/index.php?";
        $enlace = $miConfigurador->configuracion['enlace'];
        $variable = $miConfigurador->fabricaConexiones->crypto->codificar($variable);
        $_REQUEST[$enlace] = $enlace . '=' . $variable;
        $redireccion = $url . $_REQUEST[$enlace];

        echo "<script>location.replace('" . $redireccion . "')</script>";

        exit();
    }
}
