<?php

namespace facturacion\impresionFactura\entidad;

if (!isset($GLOBALS["autorizado"])) {
    include "../index.php";
    exit();
}

$ruta = $this->miConfigurador->getVariableConfiguracion("raizDocumento");
$host = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site") . "/plugin/html2pfd/";

require $ruta . "/plugin/html2pdf/html2pdf.class.php";
require $ruta . '/plugin/PHPMailer/PHPMailerAutoload.php';

$this->sql = $this->miSql;
include_once "blocks/facturacion/masivoCalFactura/entidad/sincronizarErp.php";
use facturacion\masivoCalFactura\entidad\sincronizarErp;

class GenerarDocumento
{
    public $miConfigurador;
    public $miSql;
    public $conexion;
    public $contenidoPagina;
    public $rutaURL;
    public $esteRecursoDB;
    public $beneficiarios;
    public $rutaAbsoluta;
    public $rutaXML;
    public $estrutura;
    public $contenido;
    public $FacturaMora = null;
    public function __construct($sql, $beneficiarios, $ruta_archivos)
    {

        date_default_timezone_set('America/Bogota');

        $this->miConfigurador = \Configurador::singleton();
        $this->miConfigurador->fabricaConexiones->setRecursoDB('principal');
        $this->miSql = $sql;
        $this->rutaURL = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site");

        // Conexion a Base de Datos
        $conexion = "interoperacion";
        $this->esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB($conexion);

        // Datos Para envio de Correo
        $cadenaSql = $this->miSql->getCadenaSql('consultarInformacionApi', 'gmail');
        $this->datosConexion = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

        // Datos Rutas Directorios
        $this->ruta = $this->miConfigurador->getVariableConfiguracion("raizDocumento");
        $this->rutaURL = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site");
        $this->rutaAbsoluta = $this->miConfigurador->getVariableConfiguracion("raizDocumento");
        $this->rutaProceso = $this->rutaAbsoluta . "/archivos/procesoFactura/";

        $bloque = $this->miConfigurador->getVariableConfiguracion('esteBloque');

        if (!isset($bloque["grupo"]) || $bloque["grupo"] == "") {
            $this->rutaURL .= "/blocks/" . $bloque["nombre"] . "/";
            $this->rutaAbsoluta .= "/blocks/" . $bloque["nombre"] . "/";
        } else {
            $this->rutaURL .= "/blocks/" . $bloque["grupo"] . "/" . $bloque["nombre"] . "/";
            $this->rutaAbsoluta .= "/blocks/" . $bloque["grupo"] . "/" . $bloque["nombre"] . "/";
        }

        // Ruta XML para Creación PDF
        $this->rutaXML = $this->rutaAbsoluta . 'entidad/PlantillaXML/Facturacion25012017.xml';

        //Objeto de Sincronización Erp
        $this->ERP = new sincronizarErp('', $this->miSql);

        // Procedimiento

        $opciones = explode("&", $beneficiarios);

        if (end($opciones) == 'correo') {
            $_REQUEST['correo'] = true;
        }

        foreach ($opciones as $key => $value) {

            $pos = strpos($value, 'oportuna_pago');

            if ($pos != false) {
                $arreglo = explode("=", $value);

                $_REQUEST['fecha_oportuna_pago'] = end($arreglo);

            }

        }

        $this->beneficiarios = explode(";", $opciones[0]);

        $this->ruta_archivos = $ruta_archivos;

        /**
         * Creación Log Sincronización Erp
         */

        $this->creacion_log();

        /**
         * Procedimiento crear Facturas
         */

        foreach ($this->beneficiarios as $key => $this->identificador_beneficiario) {

            if ($this->validarBeneficiario()) {

                /**
                 * Asignación Fecha Oportuna de Pago
                 */

                if (isset($this->InformacionFacturacion['estado_factura']) && $this->InformacionFacturacion['estado_factura'] == 'Borrador') {

                    /**
                     * Crear Factura ERP NEXT
                     */

                    $sincronizacion = $this->crearFacturaErp();

                    $this->InformacionFacturacion['fecha_pago_oportuno'] = $sincronizacion['fechaOportunaPago'];
                }

                /**
                 * Númeracion Facturación
                 */

                $this->parametrizacionNumeracionFacturacion();

                /**
                 * Cargar Estructura XML
                 */

                $this->cargarEstructuraXML();

                /**
                 * Parametrizacioón Posición
                 */

                $this->parametrizacionPosicion();

                /**
                 * Parametrizacioón Posición
                 */

                $this->estruturaDocumento();

                /**
                 * Creacion Factura
                 */

                /**
                 * Creación Desprendible
                 */
                //$this->crearPDFDesprendible();

                if (!isset($_REQUEST['documento_intantaneo'])) {
                    $this->archivo_adjunto = $this->ruta_archivos . "/Factura_" . $this->InformacionBeneficiario['numero_identificacion'] . "_" . str_replace(' ', '_', $this->InformacionBeneficiario['nombre_beneficiario']) . ".pdf";

                    $this->crearPDFFactura();

                    /**
                     * Unir Documento
                     */

                    if ($sincronizacion['mensajeCreacion']['estado'] == 0) {

                        //$this->unirDocumento();

                    }

                } else {

                    /**
                     * Unir Documento
                     */
                    //$this->unirDocumento();

                    /**
                     * Descargar PDF
                     */

                    $this->archivo_adjunto = $this->rutaProceso . 'FacturaBeneficiario.pdf';
                    //exit;

                    $this->crearPDFFactura();

                    $this->descargarDocumento($this->archivo_adjunto);

                }

                if (!isset($_REQUEST['documento_intantaneo']) && $this->InformacionFacturacion['estado_factura'] == 'Borrador') {

                    /**
                     * Actualizar Factura Beneficiario
                     */

                    if ($sincronizacion['mensajeCreacion']['estado'] == 0) {

                        $arreglo = array(
                            'id_beneficiario' => $this->identificador_beneficiario,
                            'fecha_oportuna_pago' => $sincronizacion['fechaOportunaPago'],
                            'indice_facturacion' => $this->InformacionFacturacion['indice_facturacion'],
                            'numeracion_facturacion' => $this->InformacionFacturacion['numeracion_facturacion'],
                            'codigo_barras' => $this->InformacionFacturacion['codigo_barras'],
                            'id_factura' => $this->InformacionFacturacion['id_factura'],
                            'factura_erp' => $sincronizacion['mensajeCreacion']['recibo'],

                        );

                        $cadenaSql = $this->miSql->getCadenaSql('actualizarFacturaBeneficiario', $arreglo);
                        $actualizacionEstadoFactura = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

                        if (isset($_REQUEST['correo'])) {

                            $this->enviarNotificacion();
                        }

                        $mensaje_log = $this->InformacionBeneficiario['numero_identificacion'] . " " . $this->InformacionBeneficiario['nombre_beneficiario'] . " =>" . $sincronizacion['mensajeCreacion']['mensaje'] . " Recibo : " . $sincronizacion['mensajeCreacion']['recibo'];

                    } else {

                        $mensaje_log = $this->InformacionBeneficiario['numero_identificacion'] . " " . $this->InformacionBeneficiario['nombre_beneficiario'] . " =>" . $sincronizacion['mensajeCreacion']['mensaje'];

                    }

                    $this->escribir_log($mensaje_log);

                }

            }
        }

        if (!isset($_REQUEST['documento_intantaneo'])) {
            $this->cerrar_log();
        }

    }

    public function escribir_log($mensaje)
    {

        fwrite($this->log, $mensaje . PHP_EOL);

    }

    public function cerrar_log()
    {

        fclose($this->log);

    }

    public function creacion_log()
    {

        if (!isset($_REQUEST['documento_intantaneo'])) {

            $prefijo = substr(md5(uniqid(time())), 0, 6);

            $this->ruta_absoluta_log = $this->ruta_archivos . "/" . $prefijo . "_creacionFacturasErp.log";

            $this->log = fopen($this->ruta_absoluta_log, "w");
        }

    }

    public function crearFacturaErp()
    {
        // Variable necesaria para crear factura
        $_REQUEST['id_beneficiario'] = $this->identificador_beneficiario;

        $arreglo = array(
            'id_ciclo' => $this->InformacionFacturacion['id_ciclo'],
            'total_factura' => $this->InformacionFacturacion['total_factura'],
            'id_factura' => $this->InformacionFacturacion['id_factura'],
        );

        $arregloErp = $this->ERP->crearUrlFactura($arreglo);

        $mensaje = $this->ERP->crearFactura($arregloErp['url']);

        unset($_REQUEST['id_beneficiario']);

        return array(
            'fechaOportunaPago' => $arregloErp['fechaPagoOportuno'],
            'mensajeCreacion' => $mensaje);

    }

    public function descargarDocumento($documento = '')
    {

        ob_start();

        header("Content-type: application/pdf");
        header('Content-Disposition: attachment; filename=FacturaBeneficiario' . date('Ymd') . '.pdf');
        header('Pragma: no-cache');
        readfile($documento);

        echo "<script languaje='javascript' type='text/javascript'>window.close();</script>";
        exit();

    }

    public function unirDocumento()
    {

        if (isset($this->archivo_adjunto)) {

            $sentencia_linux = 'pdftk ' . $this->paginaFactura . ' ' . $this->paginaDesprendible . '  cat output ' . $this->archivo_adjunto;

        } else {

            $sentencia_linux = 'pdftk ' . $this->paginaFactura . ' ' . $this->paginaDesprendible . '  cat output ' . $this->rutaProceso . 'FacturaBeneficiario.pdf';

        }

        shell_exec($sentencia_linux);
    }

    public function validarBeneficiario()
    {

        unset($this->FacturaMora);

        $cadenaSql = $this->miSql->getCadenaSql('consultaInformacionFacturacionAnterior', $this->identificador_beneficiario);
        $this->InformacionFacturacionAnterior = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

        $cadenaSql = $this->miSql->getCadenaSql('consultaInformacionFacturacion', $this->identificador_beneficiario);
        $this->InformacionFacturacion = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

        $cadenaSql = $this->miSql->getCadenaSql('consultaValorPagado', $this->identificador_beneficiario);
        $this->ValorPagado = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0]['valor_pagado'];

        $cadenaSql = $this->miSql->getCadenaSql('consultaUltimoValorPagado', $this->identificador_beneficiario);
        $this->UltimoValorPagadoFactura = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

        $cadenaSql = $this->miSql->getCadenaSql('consultaValoresConceptos', $this->InformacionFacturacion);
        $this->Conceptos = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");

        $this->CuotaPeriodo = 0;

        // Factura en Mora

        $validar = false;
        if ($this->Conceptos != false) {

            foreach ($this->Conceptos as $key => $value) {

                if ($value['factura_mora'] != '') {
                    $id_factura = $value['factura_mora'];
                }

                if ($value['factura_mora'] == '') {
                    $this->CuotaPeriodo = $this->CuotaPeriodo + $value['valor_concepto'];
                }

                if (isset($id_factura) && $id_factura != '') {
                    $cadenaSql = $this->miSql->getCadenaSql('consultarFacturaMora', $id_factura);
                    $FacturaMora = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

                    $this->Conceptos[$key]['concepto'] = $this->Conceptos[$key]['concepto'] . " (" . $FacturaMora['indice_facturacion'] . sprintf("%'.06d", $FacturaMora['numeracion_facturacion']) . ") ";

                    if ($validar == false) {

                        $this->FacturaMora = $FacturaMora;

                        $validar = true;

                    }

                }

            }

        }

        $cadenaSql = $this->miSql->getCadenaSql('consultarBeneficiario', $this->identificador_beneficiario);
        $this->InformacionBeneficiario = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

        if ($this->InformacionBeneficiario && $this->Conceptos && $this->InformacionFacturacion) {
            return true;
        } else {
            return false;
        }

    }

    public function parametrizacionNumeracionFacturacion()
    {

        switch ($this->InformacionFacturacion['departamento']) {
            case '23':

                $this->InformacionFacturacion['indice_facturacion'] = 'FVM';
                $this->InformacionFacturacion['limite_facturacion'] = 'No.FVM 000001 al No. FVM 130316';
                $limite = 130316;

                $cadenaSql = $this->miSql->getCadenaSql('consultarNumeracionFactura', 'FVM');
                $numeracion = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");

                break;

            case '70':

                $this->InformacionFacturacion['indice_facturacion'] = 'FVS';
                $this->InformacionFacturacion['limite_facturacion'] = 'No.FVS 000001 al No. FVS 001445';
                $cadenaSql = $this->miSql->getCadenaSql('consultarNumeracionFactura', 'FVS');
                $numeracion = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda");
                $limite = 1445;
                break;

        }

        if (is_null($numeracion[0]['numeracion'])) {

            $numero_factura = 1;

        } else {

            $numero_factura = $numeracion[0]['numeracion'] + 1;
        }

        if (is_null($this->InformacionFacturacion['numeracion_facturacion'])) {

            $this->InformacionFacturacion['numeracion_facturacion'] = sprintf("%'.06d", $numero_factura);

        } else {

            $this->InformacionFacturacion['numeracion_facturacion'] = sprintf("%'.06d", $this->InformacionFacturacion['numeracion_facturacion']);

        }

    }
    public function cargarEstructuraXML()
    {
        ini_set('xdebug.var_display_max_depth', 5);
        ini_set('xdebug.var_display_max_children', 256);
        ini_set('xdebug.var_display_max_data', 1024);

        $this->estruturaXML = simplexml_load_file($this->rutaXML);

        // var_dump($nodo->titulo->attributes());

        $estrutura = json_encode($this->estruturaXML);

        $this->estructura = json_decode($estrutura, true);
    }
    public function parametrizacionPosicion()
    {

        /**
         * Configuracion Pagina Documento
         *
         * _____________Columna 1 Columna 2
         * Seccion 1 | | |
         * _____________________________________
         * Seccion 2 | | |
         * _____________________________________
         * Seccion 3 | | |
         * _____________________________________
         * Seccion 4 | | |
         * _____________________________________
         *
         * 1020px * 100%
         */
        $this->contenido = "<table  style='width:100%; border: 0.1px; background-color: #fff' >";

        // Determina la utilización de colspan
        $this->determinacionTipoColumna();

        $numero_secciones = count($this->estruturaXML);

        switch ($numero_secciones) {
            case 1:
                $height = '1015px';
                break;

            case 2:
                $height = '505px';
                break;

            case 3:
                $height = '145px';
                break;

            case 4:
                $height = '145px';
                break;

            default:
                echo "Error Numero Secciones";
                exit();
                break;
        }

        foreach ($this->estruturaXML as $key => $seccion) {

            $this->contenido .= "<tr>";

            $numero_columnas = count($seccion->columna);

            switch ($numero_columnas) {
                case 1:
                    $width = '100%';
                    break;

                case 2:
                    $width = '45%';
                    break;

                default:
                    echo "Error Numero columnas";
                    exit();
                    break;
            }

            foreach ($seccion as $key => $columna) {

                if (isset($this->colspan) && $width == '100%') {

                    $this->contenido .= "<td colspan='2' style='width:" . $width . ";height:" . $height . ";border:none;font-size:100%;" . $columna->attributes() . "'  >";

                    // Permite generar el Contenido a unos Tipos de Parametros
                    $this->caracterizacionContenido($columna);

                    $this->contenido .= "</td>";
                } else {

                    $this->contenido .= "<td  style='width:" . $width . ";height:" . $height . ";border:none;" . $columna->attributes() . "' >";
                    $this->caracterizacionContenido($columna);

                    $this->contenido .= "</td>";
                }

            }

            $this->contenido .= "</tr>";
        }

        $this->contenido .= "</table>";

    }
    public function caracterizacionContenido($objetoDatos)
    {
        foreach ($objetoDatos as $key => $value) {
            $this->atributos = $value->attributes();

            switch ($key) {
                case 'titulo':

                    $this->contenido .= "<div style='" . $this->atributos . "'><b>" . $this->reemplazarTextos($value) . "</b></div>";
                    break;

                case 'texto':
                    $this->contenido .= "<div style='" . $this->atributos . "'>" . $this->reemplazarTextos($value) . "</div>";
                    break;

                case 'imagen':
                    $this->contenido .= "<div style='" . $this->atributos['alineacionImagen'];
                    $this->contenido .= "'><img src='" . $value . "' " . $this->atributos['dimensionesImagen'] . "  ></div>";
                    break;

                case 'variable':
                    // Ejecuta los procesos para obtener contenido de la variable
                    $this->ejecutarContenidoVariable($value);
                    break;
            }
            $this->contenido .= "<br>";
        }
    }

    public function transformarFecha($fecha = '')
    {
        $tiempo = strtotime($fecha);

        $fecha_transformada = strftime("%d ", $tiempo) . ucfirst(strftime("%h ", $tiempo)) . strftime("%Y", $tiempo);

        return $fecha_transformada;

    }

    public function reemplazarTextos($variable)
    {
        $texto_variable = ['$numero_factura', '$fecha_factura_letras', '$limite_factura', '@!', '¡@', '%%'];

        foreach ($texto_variable as $key => $value) {

            switch ($value) {
                case '$numero_factura':
                    $variable = str_replace($value, $this->InformacionFacturacion['indice_facturacion'] . " " . $this->InformacionFacturacion['numeracion_facturacion'], $variable);
                    break;

                case '$fecha_factura_letras':

                    setlocale(LC_ALL, "es_CO.UTF-8");

                    $fecha_actual = strftime("%d de %B de %Y", time());

                    $variable = str_replace($value, $fecha_actual, $variable);

                    break;

                case '$limite_factura':
                    $variable = str_replace($value, $this->InformacionFacturacion['limite_facturacion'], $variable);
                    break;

                case '@!':
                    $variable = str_replace($value, "<b>", $variable);
                    break;

                case '¡@':
                    $variable = str_replace($value, "</b>", $variable);
                    break;

                case '%%':
                    $variable = str_replace($value, "<br>", $variable);
                    break;
            }

        }

        return $variable;

    }

    public function ejecutarContenidoVariable($variable)
    {
        switch ($variable) {
            case 'FechaActual':
                $this->contenido .= "<div style='" . $this->atributos . "'>" . date('Y-m-d') . "</div>";
                break;

            case 'InformacionPago':
                $this->contenido .= "<div style='" . $this->atributos . "'>INFORMACION DE PAGO</div>";
                break;

            case 'HistoricoConsumo':
                $this->contenido .= "<div style='" . $this->atributos . "'>Gráfico Histórico</div>";
                break;

            case 'InformacionPagoResumido':

                $this->contenido .= "<div style='" . $this->atributos . "'>";

                $this->contenido .= "<table style='vertical-align:middle;border-collapse:collapse;border:none;width:100%;'s>
                            <tr>
                                <td colspan='2' style='font-family:futura_extra_black_condensed_bt;font-size:18px;height:30px;text-align:left;border:none;background-color:#c7c7c7;color:#444444;border-bottom: #fff;'>Resumen</td>
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;font-size:12.5px;height:17px;text-align:left;border:none;width:50%;background-color:#c7c7c7;color:#444444;border-bottom: #fff;'><b>Deuda Anterior </b></td>";

                if (isset($this->FacturaMora) && !is_null($this->FacturaMora['total_factura']) && $this->FacturaMora['total_factura'] != '') {
                    $this->contenido .= "<td style='font-family:futuraltbt;font-size:12.5px;height:17px;text-align:right;border:none;width:50%;background-color:#c7c7c7;color:#444444;border-bottom: #fff;'>$ " . number_format($this->FacturaMora['total_factura'], 2) . "</td>";
                } else {
                    $this->contenido .= "<td style='font-family:futuraltbt;font-size:12.5px;height:17px;text-align:right;border:none;width:50%;background-color:#c7c7c7;color:#444444;border-bottom: #fff;'>$ 0</td>";
                }

                $this->contenido .= "</tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:17px;font-size:12.5px;text-align:left;border:none;width:50%;background-color:#c7c7c7;color:#444444;border-bottom: #fff;'><b>Cuota Mes </b></td>
                                <td style='font-family:futuraltbt;height:17px;font-size:12.5px;text-align:right;border:none;width:50%;background-color:#c7c7c7;color:#444444;border-bottom: #fff;'>$ " . number_format($this->CuotaPeriodo, 2) . " </td>
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;vertical-align:middle;font-size:13.5px;height:17px;text-align:left;border:none;width:50%;background-color:#rgb(230, 197, 96);color:#444444;'><b>TOTAL A PAGAR</b></td>
                                <td style='font-family:futuraltbt;vertical-align:middle;height:17px;font-size:13.5px;text-align:right;border:none;width:50%;background-color:#rgb(230, 197, 96);color:#444444;'><b>$ " . number_format($this->InformacionFacturacion['total_factura'], 2) . "</b></td>
                            </tr>
                        </table>
                        <br>
                        <table style='border:none;width:100%;'>
                           <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;font-size:16px;height:13px;text-align:left;border:none;width:50%;background-color:#efefef;color:#444444'><b>Cuenta o Ref. Pago </b></td>
                                <td style='font-family:futuraltbt;font-size:16px;height:13px;text-align:center;border:0.3px;border-top-left-radius:9px;border-bottom-left-radius:9px;border-top-right-radius:9px;border-bottom-right-radius:9px;;width:50%;background-color:#efefef;color:#444444;'>" . $this->InformacionBeneficiario['numero_identificacion'] . "</td>
                            </tr>
                        </table>";

                $this->contenido .= "</div>";

                break;

            case 'Conceptos':
                $this->contenido .= "<div style='" . $this->atributos . "'>";

                $table = "<table style='border-collapse:collapse;border:0.1px;width:100%;vertical-align:middle;' >
                            <tr>
                                <td colspan='4' style='font-family:futura_extra_black_condensed_bt;margin: 0 auto;font-size:16px;height:18px;text-align:left;border:0.1px;background-color:#2a91bd;color:#fff;border-top-left-radius: 4px; border-bottom-left-radius: 4px;border-top-right-radius: 4px; border-bottom-right-radius: 4px;'><b>Detalle de Cargos Facturados</b></td>
                            </tr>";

                $table .= "<tr>
                <td style='color:#5b5e60;font-family:futura_extra_black_condensed_bt;text-align:center;border:0.1px;width:4%;'><br><b>N°</b><br></td>
                <td style='color:#5b5e60;font-family:futura_extra_black_condensed_bt;text-align:center;border:0.1px;width:35%;'><br><b>Periodo Facturado</b><br></td>
                <td style='color:#5b5e60;font-family:futura_extra_black_condensed_bt;text-align:center;border:0.1px;width:41%;'><br><b>Concepto</b><br></td>
                <td style='color:#5b5e60;font-family:futura_extra_black_condensed_bt;text-align:center;border:0.1px;width:20%;'><br><b>Valor</b><br></td>
                </tr>";
                $i = 1;
                foreach ($this->Conceptos as $key => $value) {
                    $table .= "<tr>
                                  <td style='color:#5b5e60;font-family:futuraltbt;font-size:8.5px;text-align:center;border:0.1px;width:4%;'><br>" . $i . ".<br></td>
                                  <td style='color:#5b5e60;font-family:futuraltbt;font-size:8.5px;text-align:center;border:0.1px;width:35%;'><br>" . $value['inicio_periodo'] . "  /  " . $value['fin_periodo'] . "<br></td>
                                  <td style='color:#5b5e60;font-family:futuraltbt;font-size:8.5px;text-align:left;border:0.1px;width:41%;'><br>" . $value['concepto'] . "<br></td>
                                  <td align='center' style='color:#5b5e60;font-family:futuraltbt;font-size:8.5px;text-align:left;border:0.1px;width:20%;'><br>$ " . number_format($value['valor_concepto'], 2) . "<br></td>
                               </tr>";

                    $i++;
                }

                $table .= "</table>";

                $this->contenido .= $table;
                $this->contenido .= "</div>";

                break;

            case 'InformacionBeneficiario':

                $this->contenido .= "<div style='" . $this->atributos . "'>";

                if (isset($_REQUEST['fecha_oportuna_pago'])) {
                    $fechaOportuna = $_REQUEST['fecha_oportuna_pago'];
                } else {

                    $fechaOportuna = $this->InformacionFacturacion['fecha_pago_oportuno'];

                }

                $table = "<table style='vertical-align:top;border-collapse:collapse;border:none;width:100%;' nowrap >
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;font-size: 14px;height:20px;text-align:left;border:0.1px;background-color:#2a91bd;border-top-left-radius: 4px; border-bottom-left-radius: 4px; color:#fff;width:50%;vertical-align:middle;'><b>Fecha Oportuna de Pago</b></td>
                                <td style='font-family:futura_extra_black_condensed_bt;height:15px;text-align:center;border:0.1px;background-color:#d6f4f9;border-top-right-radius:4px;border-bottom-right-radius:4px;width:50%;vertical-align:middle;color:#5b5e60'><b>" . $this->transformarFecha($fechaOportuna) . "</b></td>
                            </tr>
                            </table>
                            <table style='border-collapse:collapse;border:0.1px;width:100%;color:#5b5e60;font-size:11px;' nowrap>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;border-spacing: 3px;width:30%'><b>Cliente No: </b></td>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;border-spacing: 3px;width:70%'><b>" . $this->InformacionBeneficiario['id_beneficiario'] . "</b></td>
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;border-spacing: 3px;width:30%'><b>Beneficiario: </b></td>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;border-spacing: 3px;width:70%'><b>" . trim($this->InformacionBeneficiario['nombre_beneficiario']) . "</b></td>
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;border-spacing: 3px;width:30%'><b>Identificación: </b></td>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;border-spacing: 3px;width:70%'><b>" . $this->InformacionBeneficiario['numero_identificacion'] . "</b></td>
                            </tr>
                             <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;border-spacing: 3px;width:30%'><b>Dirección: </b></td>
                                <td style='font-family:futuraltbt;height:13px;text-align:left;border:none;border-spacing: 3px;width:70%'><b>" . $this->InformacionBeneficiario['direccion_beneficiario'] . " Urbanización " . $this->InformacionBeneficiario['urbanizacion'] . "</b></td>
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;border-spacing: 3px;width:30%'><b>Departamento: </b></td>
                                <td style='font-family:futuraltbt;height:13px;text-align:left;border:none;border-spacing: 3px;width:70%'><b>" . $this->InformacionBeneficiario['departamento'] . "</b></td>
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;border-spacing: 3px;width:30%'><b>Municipio: </b></td>
                                <td style='font-family:futuraltbt;height:13px;text-align:left;border:none;border-spacing: 3px;width:70%'><b>" . $this->InformacionBeneficiario['municipio'] . "</b></td>
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;border-spacing: 3px;width:30%'><b>Factura de Venta: </b></td>
                                <td style='font-family:futuraltbt;height:13px;text-align:left;border:none;border-spacing: 3px;width:70%'><b>No. " . $this->InformacionFacturacion['indice_facturacion'] . " " . $this->InformacionFacturacion['numeracion_facturacion'] . "</b></td>
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;border-spacing: 3px;width:30%'><b>Fecha de Expedición: </b></td>
                                <td style='font-family:futuraltbt;height:13px;text-align:left;border:none;border-spacing: 3px;width:70%'><b>" . $this->transformarFecha(date('Y-m-d')) . "</b></td>
                            </tr>
                            </table>

                        ";

                $this->contenido .= $table;
                $this->contenido .= "</div>";
                break;

            case 'InformacionFacturacion':
                $this->contenido .= "<div style='" . $this->atributos . "'>";

                $this->contenido .= "<table style='border-collapse:collapse;border:1px;width:100%;' nowrap >
                            <tr>
                                <td colspan='2' style='font-family:futuramdbtb;font-size: 16px;height:20px;text-align:left;border:none;background-color:#f16c94;border-top-left-radius: 4px; border-top-right-radius:4px;border-bottom-right-radius:4px;border-bottom-left-radius: 4px;color:#fff'><b>Estado de Cuenta</b><br></td>
                            </tr>
                            <tr>
                                  <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;width:50%;font-size:12px;color:#5b5e60;'><b>Monto Total Contrato</b></td>
                                  <td style='font-family:futuraltbt;height:13px;text-align:right;border:none;width:50%;font-size:12px;color:#5b5e60;'>$ " . number_format($this->InformacionFacturacion['valor_contrato'], 2) . "</td>
                            </tr>
                            <tr>
                                  <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;width:50%;font-size:12px;color:#5b5e60;'><b>Monto Pagado del Total Contrato</b></td>";
                if (!is_null($this->ValorPagado) && $this->ValorPagado != '') {
                    $this->contenido .= "<td style='font-family:futuraltbt;height:13px;text-align:right;border:none;width:50%;font-size:12px;color:#5b5e60;'>$ " . number_format($this->ValorPagado, 2) . "</td>";
                } else {
                    $this->contenido .= "<td style='height:13px;text-align:right;border:none;width:50%;font-size:12px;color:#5b5e60;'>$ 0</td>";
                }

                $this->contenido .= "</tr>
                                    <tr>
                                  <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;width:50%;font-size:12px;color:#5b5e60;'><b>Valor Factura Anterior</b></td>";
                if (!is_null($this->InformacionFacturacionAnterior['total_factura']) && $this->InformacionFacturacionAnterior['total_factura'] != '') {
                    $this->contenido .= "<td style='font-family:futuraltbt;height:13px;text-align:right;border:none;width:50%;font-size:12px;color:#5b5e60;'>$ " . number_format($this->InformacionFacturacionAnterior['total_factura'], 2) . "</td>";
                } else {
                    $this->contenido .= "<td style='height:13px;text-align:right;border:none;width:50%;font-size:12px;color:#5b5e60;'>$ 0</td>";
                }

                $this->contenido .= "</tr>
                                    <tr>
                                  <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;width:50%;font-size:12px;color:#5b5e60;'><b>Ultimo Valor Pagado</b></td>";
                if (!is_null($this->UltimoValorPagadoFactura['ultimo_valor_pagado']) && $this->UltimoValorPagadoFactura['ultimo_valor_pagado'] != '') {
                    $this->contenido .= "<td style='font-family:futuraltbt;height:13px;text-align:right;border:none;width:50%;font-size:12px;color:#5b5e60;'>$ " . number_format($this->UltimoValorPagadoFactura['ultimo_valor_pagado'], 2) . "</td>";
                } else {
                    $this->contenido .= "<td style='height:13px;text-align:right;border:none;width:50%;font-size:12px;color:#5b5e60;'>$ 0</td>";
                }

                $this->contenido .= "
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;width:50%;font-size:12px;color:#5b5e60;'><b>En Mora </b></td>";

                if (isset($this->FacturaMora) && !is_null($this->FacturaMora['indice_facturacion']) && !is_null($this->FacturaMora['numeracion_facturacion']) && $this->FacturaMora['numeracion_facturacion'] != '') {
                    $this->contenido .= "<td style='font-family:futuraltbt;height:13px;text-align:right;border:none;width:50%;font-size:12px;color:#5b5e60;'>No. " . $this->FacturaMora['indice_facturacion'] . " " . sprintf("%'.06d", $this->FacturaMora['numeracion_facturacion']) . "</td>";
                } else {
                    $this->contenido .= "<td style='height:13px;text-align:right;border:none;width:50%;font-size:12px;color:#5b5e60;'> </td>";
                }

                $this->contenido .= "
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;width:50%;border-top-left-radius: 4px; border-bottom-left-radius: 4px;border-spacing: 3px;font-size:12px;color:#5b5e60;'><b>Saldo Vencido </b></td>";

                if (isset($this->FacturaMora) && !is_null($this->FacturaMora['total_factura']) && $this->FacturaMora['total_factura'] != '') {
                    $this->contenido .= "<td style='font-family:futuraltbt;height:13px;text-align:right;border:none;width:50%;border-top-right-radius:4px;border-bottom-right-radius:4px;font-size:12px;color:#5b5e60;'>$ " . number_format($this->FacturaMora['total_factura'], 2) . "</td>";
                } else {
                    $this->contenido .= "<td style='height:13px;text-align:right;border:none;width:50%;border-top-right-radius:4px;border-bottom-right-radius:4px;font-size:12px;color:#5b5e60;'>$ 0</td>";
                }

                $this->contenido .= "
                            </tr>
                          </table>";

                $this->contenido .= "<br><br><table style='vertical-align:middle;border-collapse:collapse;border:1px;width:100%;' nowrap >
                            <tr>
                                <td colspan='2' style='font-family:futuramdbtb;font-size: 16px;height:20px;text-align:left;border:0.1px;background-color:#2a91bd;border-top-left-radius: 4px; border-top-right-radius:4px;border-bottom-right-radius:4px;border-bottom-left-radius: 4px;color:#fff'><b>Periodo Facturado </b>" . $this->InformacionFacturacion['id_ciclo'] . "<br></td>
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;width:50%;font-size:12px;color:#5b5e60;border-bottom: #5b5e60;'><b>Factura</b></td>
                                <td style='font-family:futuraltbt;height:13px;text-align:right;border:none;width:50%;font-size:12px;color:#5b5e60;border-bottom: #5b5e60;'>No. " . $this->InformacionFacturacion['indice_facturacion'] . " " . $this->InformacionFacturacion['numeracion_facturacion'] . "</td>
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;width:50%;font-size:12px;color:#5b5e60;border-bottom: #5b5e60;'><b>Valor</b></td>
                                <td style='font-family:futuraltbt;height:13px;text-align:right;border:none;width:50%;font-size:12px;color:#5b5e60;border-bottom: #5b5e60;'>$ " . number_format($this->InformacionFacturacion['total_factura'], 2) . "</td>
                            </tr>
                            <tr>
                                <td style='font-family:futura_extra_black_condensed_bt;height:13px;text-align:left;border:none;width:50%;font-size:12px;color:#5b5e60;border-bottom: #5b5e60;'><b>IVA</b></td>
                                <td style='font-family:futuraltbt;height:13px;text-align:right;border:none;width:50%;font-size:12px;color:#5b5e60;border-bottom: #5b5e60;'>$ 0.00</td>
                            </tr>
                          </table>";

                /**
                 * $this->contenido .
                 *
                 * = "<table style='border-collapse:collapse;border:1px;width:100%;' nowrap >
                 * <tr>
                 * <td colspan='2' style='height:13px;text-align:center;border:0.1px;background-color:#97b5f4;'><b>INFORMACIÓN PAGO RESUMIDO</b><br></td>
                 * </tr>
                 * <tr>
                 * <td style='height:13px;text-align:left;border:0.1px;width:50%'><b>Fecha de Venta: </b></td>
                 * <td style='height:13px;text-align:right;border:0.1px;width:50%'>" . $this->InformacionFacturacion['fecha_venta'] . "</td>
                 * </tr>
                 * <tr>
                 * <td style='height:13px;text-align:left;border:0.1px;width:50%;'><b>Fecha Factura: </b></td>
                 * <td style='height:13px;text-align:right;border:0.1px;width:50%;'>" . $this->InformacionFacturacion['fecha_factura'] . "</td>
                 * </tr>
                 * <tr>
                 * <td style='height:13px;text-align:left;border:0.1px;width:50%;'><b>Periodo: </b></td>
                 * <td style='height:13px;text-align:right;border:0.1px;width:50%;'>" . $this->InformacionFacturacion['id_ciclo'] . "</td>
                 * </tr>
                 * <tr>
                 * <td style='height:13px;text-align:left;border:0.1px;width:50%;'><b>Contrato-Ref.Pago: </b></td>
                 * <td style='height:13px;text-align:right;border:0.1px;width:50%;'>" . $this->InformacionFacturacion['numero_contrato'] . "</td>
                 * </tr>
                 * <tr>
                 * <td style='height:13px;text-align:left;border:0.1px;width:50%;'><b>Fecha Pago Oportuno: </b></td>
                 * <td style='height:13px;text-align:right;border:0.1px;width:50%;'></td>
                 * </tr>
                 * </table>";
                 */
                $this->contenido .= "</div>";
                break;

            case 'Contacto':
                $this->contenido .= "<div style='" . $this->atributos . "'>";

                $this->contenido .= "<table style='border-collapse:collapse;border:none;width:100%;font-family: myriadpro;'>
                                        <tr>
                                            <td style='height:13px;text-align:left;border:none;width:50%;vertical-align:center'><img width='20' height='20' src='http://localhost/OpenKyOS/theme/basico/img/facebook.png'  >&nbsp;&nbsp;&nbsp;&nbsp;<b>Conexiones-Digitales-II</b></td>
                                            <td style='height:13px;text-align:left;border:none;width:50%;'><b>Línea gratuita: 01 8000 961 016</b></td>
                                        </tr>
                                        <tr>
                                            <td style='height:13px;text-align:left;border:none;width:50%;'><b>Conexiones-Digitales-II Sede Monteria:</b><br>Calle 67 # 2-112 piso 2</td>
                                            <td style='height:13px;text-align:left;border:none;width:50%;'><b>Conexiones-Digitales-II Sede Sucre:</b><br>Carrera 20 #27-87 oficina 302<br>Edificio Camara de comercio</td>
                                        </tr>
                                        <tr>
                                            <td  colspan='2' style='height:13px;text-align:left;border:none;width:100%;'><br><b>Corporación Politécnica Nacional de Colombia<br>
                                            NIT 830.115.993-4<br>
                                            http://conexionesdigitales.politecnica.edu.co/</b></td>
                                        </tr>
                                  </table>";
                $this->contenido .= "</div>";
                break;

            case 'FormaPago':

                $this->contenido .= "<div style='" . $this->atributos . "'>";

                $this->contenido .= "<table style='border-collapse:collapse;border:none;width:100%;font-family: myriadpro;'>
                                        <tr>
                                            <td style='height:13px;text-align:center;border:none;width:30%;vertical-align:center'>Forma de Pago : </td>";

                switch ($this->InformacionFacturacion['departamento']) {
                    case '23':

                        $this->contenido .= "<td style='height:13px;text-align:left;border:none;width:70%;'><img width='200' height='43' src='http://localhost/OpenKyOS/theme/basico/img/FormaPago1.png'></td>";
                        break;

                    case '70':

                        $this->contenido .= "<td style='height:13px;text-align:left;border:none;width:70%;'><img width='200' height='50' src='http://localhost/OpenKyOS/theme/basico/img/FormaPago2.1.png'></td>";
//                        $this->contenido .= "<td style='height:13px;text-align:left;border:none;width:70%;'><br>Administración del conjunto residencial<br><br>Horario: Lunes a Viernes de 8:00 a 12:00 y de 2:00 a 6:00 pm</td>";
                        break;

                    default:

                        $this->contenido .= "<td style='height:13px;text-align:left;border:none;width:70%;'><img width='200' height='45' src='http://localhost/OpenKyOS/theme/basico/img/FormaPago.png'></td>";
                        break;
                }

                $this->contenido .= "   </tr>
                                    </table>";
                $this->contenido .= "</div>";
                break;

            case 'CodigoBarras':

                $this->contenido .= "<div style='font-size: 40%;text-align:" . $this->atributos['alineacionCodigoBarras'];

                $fecha = str_replace('-', '', $this->InformacionFacturacion['id_ciclo']);

                $arreglo = array(
                    str_replace('-', '', $this->InformacionFacturacion['id_ciclo']),
                    $this->InformacionFacturacion['departamento'],
                    $this->InformacionFacturacion['municipio'],
                    $this->InformacionFacturacion['numero_identificacion'],
                    $this->InformacionFacturacion['total_factura'],
                );

                $valorCodigo = implode('000', $arreglo);

                $this->InformacionFacturacion['codigo_barras'] = $valorCodigo;

                $this->contenido .= "'><barcode type='CODABAR' value='" . $valorCodigo . "' style='" . $this->atributos['dimensionesCodigoBarras'] . "'></barcode></div>";
                break;
        }
    }
    public function determinacionTipoColumna()
    {
        foreach ($this->estruturaXML as $key => $seccion) {

            $numero_columnas = count($seccion);

            switch ($numero_columnas) {
                case 1:
                    $columna_1 = true;
                    break;

                case 2:
                    $columna_2 = true;
                    break;

                default:
                    echo "Error Numero columnas";
                    exit();
                    break;
            }
        }

        if (isset($columna_1) && isset($columna_2)) {

            $this->colspan = true;
        }
    }
    // ----------------------------------------------------------------------
    public function crearPDFFactura()
    {
        ob_start();
        $html2pdf = new \HTML2PDF('P', 'LEGAL', 'es', true, 'UTF-8', array(
            1,
            1,
            1,
            1,
        ));

        $html2pdf->pdf->SetDisplayMode('fullpage');
        $html2pdf->WriteHTML($this->contenidoPagina);

        //$this->paginaFactura = $this->rutaProceso . 'Factura_actual.pdf';

        $html2pdf->Output($this->archivo_adjunto, 'F');

    }

    // ----------------------------------------------------------------------
    public function crearPDFDesprendible()
    {
        ob_start();
        $html2pdf = new \HTML2PDF('L', array(150, 60), 'es', true, 'UTF-8', array(
            1,
            1,
            1,
            1,
        ));
        $html2pdf->pdf->SetDisplayMode('fullpage');

        $contenidoPagina = "<page backtop='0mm' backbottom='0mm' backleft='0mm' backright='0mm'>
                                <div style='width:100%'>
                                    <img width='560' height='218' src='http://localhost/OpenKyOS/theme/basico/img/desprendible.png'>
                                </div>
                            </page>";

        $html2pdf->WriteHTML($contenidoPagina);

        $this->paginaDesprendible = $this->rutaProceso . 'Desprendible_actual.pdf';

        $html2pdf->Output($this->paginaDesprendible, 'F');

    }

    public function estruturaDocumento()
    {
        $contenidoPagina = "<style type=\"text/css\">


                           table {

                                    font-family:Helvetica, Arial, sans-serif; /* Nicer font */

                                    border-collapse:collapse; border-spacing: 3px;
                                }
                                td, th {
                                    border: 1px solid #CCC;

                                } /* Make cells a bit taller */

                                th {

                                    font-weight: bold; /* Make sure they're bold */
                                    text-align: center;
                                    font-size:10px;
                                }
                                td {

                                    text-align: left;
                                }

                            </style>";

        $contenidoPagina = "<page backtop='1mm' backbottom='1mm' backleft='1mm' backright='1mm'>";

        $contenidoPagina .= $this->contenido;

        $contenidoPagina .= "</page>";

        $this->contenidoPagina = $contenidoPagina;
    }

    public function enviarNotificacion()
    {

        /**
         * This example shows settings to use when sending via Google's Gmail servers.
         */

        // Create a new PHPMailer instance
        $mail = new \PHPMailer();

        $mail->CharSet = 'UTF-8';

        // Tell PHPMailer to use SMTP
        $mail->isSMTP();

        // Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $mail->SMTPDebug = 0;

        // Ask for HTML-friendly debug output
        $mail->Debugoutput = 'html';

        // Set the hostname of the mail server
        $mail->Host = 'smtp.gmail.com';
        // use
        // $mail->Host = gethostbyname('smtp.gmail.com');
        // if your network does not support SMTP over IPv6

        // Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $mail->Port = 587;

        // Set the encryption system to use - ssl (deprecated) or tls
        $mail->SMTPSecure = 'tls';

        // Whether to use SMTP authentication
        $mail->SMTPAuth = true;

        // Username to use for SMTP authentication - use full email address for gmail
        $mail->Username = $this->datosConexion['usuario'];

        // Password to use for SMTP authentication
        $mail->Password = $this->datosConexion['password'];

        // Set who the message is to be sent from
        $mail->setFrom($this->datosConexion['usuario'], 'Conexiones Digitales - Sistema OpenKyOS');

        // Set an alternative reply-to address
        // $mail->addReplyTo ( 'replyto@example.com', 'First Last' );

        // Set who the message is to be sent to

        $this->designatariosCorreo = array(
            '0' => $this->InformacionFacturacion['correo'],
            '1' => $this->InformacionFacturacion['correo_institucional'],
        );

        if (is_array($this->designatariosCorreo) == true) {

            foreach ($this->designatariosCorreo as $key => $value) {

                if (!is_null($value) && $value != '') {
                    $mail->addAddress($value);
                }
            }
        }
        // Set the subject line
        $mail->Subject = 'Factura - Conexiones Digitales II';
        $body = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                        <html>
                        <head>
                          <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
                          <title>Factura - Conexiones Digitales II</title>
                        </head>
                        <body>
                        <div style="width: 640px; font-family: Arial, Helvetica, sans-serif; font-size: 11px;">
                          <h1>Factura - Conexiones Digitales II</h1>
                          <p>Factura de pago para el periodo ' . $this->InformacionFacturacion['id_ciclo'] . '.<br><br>Conexiones Digitales II<br>Notificación de Sistema OpenKyOS</p>
                          <div align="center">
                          </div>
                        </div>
                        </body>
                        </html>
        ';

        // Archivo Adjunto
        $mail->addAttachment($this->archivo_adjunto, 'Factura.pdf');

        // Read an HTML message body from an external file, convert referenced images to embedded,
        // convert HTML into a basic plain-text alternative body
        $mail->msgHTML($body, dirname(__FILE__));

        // Replace the plain text body with one created manually
        // $mail->AltBody = 'Hemos recibido una solicitud de restauración de contraseña, si usted realizo la solicitud de clic sobre el siguiente link . Si usted no realizo dicha solicitud por favor omita este mensaje';

        // Attach an image file
        // $mail->addAttachment ( $this->ruta . '/plugin/PHPMailer/examples/images/phpmailer_mini.png' );

        // send the message, check for errors
        $mail->send();
    }
}
$miDocumento = new GenerarDocumento($this->miSql, $this->proceso['datos_adicionales'], $this->rutaAbsoluta_archivos);
