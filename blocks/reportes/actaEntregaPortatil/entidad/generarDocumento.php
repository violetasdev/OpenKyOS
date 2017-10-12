<?php

namespace reportes\actaEntregaPortatil\entidad;

if (!isset($GLOBALS["autorizado"])) {
    include "../index.php";
    exit();
}

$ruta = $this->miConfigurador->getVariableConfiguracion("raizDocumento");
$host = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site") . "/plugin/html2pfd/";

include $ruta . "/plugin/html2pdf/html2pdf.class.php";
class GenerarDocumento
{
    public $miConfigurador;
    public $elementos;
    public $miSql;
    public $conexion;
    public $contenidoPagina;
    public $rutaURL;
    public $esteRecursoDB;
    public $clausulas;
    public $beneficiario;

    public $rutaAbsoluta;
    public function __construct($sql)
    {
        $this->miConfigurador = \Configurador::singleton();
        $this->miConfigurador->fabricaConexiones->setRecursoDB('principal');
        $this->miSql   = $sql;
        $this->rutaURL = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site");

        // Conexion a Base de Datos
        $conexion = "interoperacion";

        $this->esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB($conexion);

        $this->rutaURL      = $this->miConfigurador->getVariableConfiguracion("host") . $this->miConfigurador->getVariableConfiguracion("site");
        $this->rutaAbsoluta = $this->miConfigurador->getVariableConfiguracion("raizDocumento");

        if (!isset($_REQUEST["bloqueGrupo"]) || $_REQUEST["bloqueGrupo"] == "") {
            $this->rutaURL .= "/blocks/" . $_REQUEST["bloque"] . "/";
            $this->rutaAbsoluta .= "/blocks/" . $_REQUEST["bloque"] . "/";
        } else {
            $this->rutaURL .= "/blocks/" . $_REQUEST["bloqueGrupo"] . "/" . $_REQUEST["bloque"] . "/";
            $this->rutaAbsoluta .= "/blocks/" . $_REQUEST["bloqueGrupo"] . "/" . $_REQUEST["bloque"] . "/";
        }

        /**
         * 1.
         * Estruturar Documento
         */

        $this->estruturaDocumento();

        /**
         * 2.
         * Crear PDF
         */
        $this->crearPDF();
    }
    public function crearPDF()
    {
        ob_start();
        $html2pdf = new \HTML2PDF('P', 'LETTER', 'es', true, 'UTF-8', array(
            1,
            1,
            1,
            1,
        ));

        $html2pdf->pdf->SetDisplayMode('fullpage');
        $html2pdf->WriteHTML($this->contenidoPagina);
        $html2pdf->Output('Acta_Entrega_servicio_CC_' . $this->infoCertificado['identificacion'] . '_' . date('Y-m-d') . '.pdf', 'D');
    }
    public function estruturaDocumento()
    {

        $cadenaSql       = $this->miSql->getCadenaSql('consultaInformacionCertificado');
        $infoCertificado = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

        $this->infoCertificado = $infoCertificado;

        $_REQUEST = array_merge($_REQUEST, $infoCertificado);

        if (!is_null($_REQUEST['serial']) && $_REQUEST['serial'] != '') {

            $cadenaSql          = $this->miSql->getCadenaSql('consultarInformacionEquipoSerial', $_REQUEST['serial']);
            $this->infoPortatil = $this->esteRecursoDB->ejecutarAcceso($cadenaSql, "busqueda")[0];

            foreach ($this->infoPortatil as $key => $value) {
                $this->infoPortatil[$key] = trim($value);
            }

        } else {
            $this->informacionEstandarPortatil();
        }

        if (!is_null($this->infoCertificado['fecha_entrega']) && $this->infoCertificado['fecha_entrega'] != '') {

            $fecha       = explode("-", $_REQUEST['fecha_entrega']);
            $dia         = $fecha[2];
            $mes         = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
            $mes         = $mes[$fecha[1] + 0];
            $anno        = $fecha[0];
            $fecha_letra = $dia . " del mes de " . $mes . " del Año " . $anno;

            $_REQUEST['fecha_entrega'] = $_REQUEST['fecha_entrega'];

        } else {

            $fecha_letra = "_________ del mes de _________ del Año _________";

            $_REQUEST['fecha_entrega'] = '';

        }

        {

            $tipo_vip           = ($_REQUEST['tipo_beneficiario_contrato'] == "1") ? "<b>X</b>" : "";
            $tipo_residencial_1 = ($_REQUEST['tipo_beneficiario_contrato'] == "2") ? (($_REQUEST['estrato_socioeconomico_contrato'] == "1") ? "<b>X</b>" : "") : "";
            $tipo_residencial_2 = ($_REQUEST['tipo_beneficiario_contrato'] == "2") ? (($_REQUEST['estrato_socioeconomico_contrato'] == "2") ? "<b>X</b>" : "") : "";
        }

        setlocale(LC_ALL, "es_CO.UTF-8");

        {

            $anexo_dir = '';

            if ($this->infoCertificado['manzana'] != '0' && $this->infoCertificado['manzana'] != '') {
                $anexo_dir .= " Manzana  #" . $this->infoCertificado['manzana'] . " - ";
            }

            if ($this->infoCertificado['bloque'] != '0' && $this->infoCertificado['bloque'] != '') {
                $anexo_dir .= " Bloque #" . $this->infoCertificado['bloque'] . " - ";
            }

            if ($this->infoCertificado['torre'] != '0' && $this->infoCertificado['torre'] != '') {
                $anexo_dir .= " Torre #" . $this->infoCertificado['torre'] . " - ";
            }

            if ($this->infoCertificado['casa_apartamento'] != '0' && $this->infoCertificado['casa_apartamento'] != '') {
                $anexo_dir .= " Casa/Apartamento #" . $this->infoCertificado['casa_apartamento'];
            }

            if ($this->infoCertificado['interior'] != '0' && $this->infoCertificado['interior'] != '') {
                $anexo_dir .= " Interior #" . $this->infoCertificado['interior'];
            }

            if ($this->infoCertificado['lote'] != '0' && $this->infoCertificado['lote'] != '') {
                $anexo_dir .= " Lote #" . $this->infoCertificado['lote'];
            }

            if ($this->infoCertificado['piso'] != '0' && $this->infoCertificado['piso'] != '') {
                $anexo_dir .= " Piso #" . $this->infoCertificado['piso'];

            }

            if (!is_null($this->infoCertificado['barrio']) && $this->infoCertificado['barrio'] != '') {
                $anexo_dir .= " Barrio " . $this->infoCertificado['barrio'];
            }

            $direccion_beneficiario = strtoupper(trim($_REQUEST['direccion'] . " " . $anexo_dir));

        }

        // Caraterizacioón Codigo Departamento

        if ($_REQUEST['codigo_departamento'] == '23') {

            $departamento_cordoba = 'X';
            $departamento_sucre   = '';

        } elseif ($_REQUEST['codigo_departamento'] == '70') {

            $departamento_cordoba = ' ';
            $departamento_sucre   = 'X';

        } else {
            $departamento_cordoba = ' ';
            $departamento_sucre   = ' ';

        }

        {
            // Nombre Beneficiario

            $nombre_beneficiario = $_REQUEST['nombre_contrato'] . " " . $_REQUEST['primer_apellido_contrato'] . " " . $_REQUEST['segundo_apellido_contrato'];

            $nombre_beneficiario = strtoupper(trim($nombre_beneficiario));

        }

        //var_dump($infoCertificado);exit;
        $contenidoPagina = "
                            <style type=\"text/css\">
                                table {

                                    font-family:Helvetica, Arial, sans-serif; /* Nicer font */

                                    border-collapse:collapse; border-spacing: 3px;
                                }
                                td, th {
                                    border: 1px solid #000000;
                                    height: 13px;
                                } /* Make cells a bit taller */

                                th {

                                    font-weight: bold; /* Make sure they're bold */
                                    text-align: center;
                                    font-size:10px;
                                }
                                td {

                                    text-align: left;

                                }
                                page{
                                    font-size:13px;

                                }
                            </style>";

        $contenidoPagina .= "<page backtop='25mm' backbottom='10mm' backleft='20mm' backright='20mm' footer='page'>
                            <page_header>
                                <table  style='width:100%;' >
                                        <tr>
                                                <td align='center' style='width:100%;border=none;' >
                                                <img src='" . $this->rutaURL . "frontera/css/imagen/logos_contrato.png'  width='500' height='45'>
                                                </td>
                                                <tr>
                                                <td> </td>
                                                </tr>
                                                <tr>
                                                <td style='width:100%;border:none;text-align:center;'><br><br><b>008 - ACTA DE ENTREGA DE COMPUTADOR PORTÁTIL</b></td>
                                                </tr>

                                        </tr>
                                </table>
                            </page_header>";

        $informacion_beneficiario = "<p>El suscrito beneficiario del Proyecto Conexiones Digitales II, cuyos datos se presentan a continuación:</p>

                            <table width:100%;>
                                <tr>
                                    <td style='width:21%;background-color:#efefef;'>Contrato de Servicios</td>
                                    <td colspan='3' align='center' style='width:80%;'><b>" . $_REQUEST['numero_contrato'] . "</b></td>
                                </tr>
                                <tr>
                                    <td style='width:21%;background-color:#efefef;'>Beneficiario</td>
                                    <td colspan='3' style='width:80%;'><b>" . $nombre_beneficiario . "</b></td>
                                </tr>
                                <tr>
                                    <td style='width:21%;background-color:#efefef;'>No. de Identificación</td>
                                    <td colspan='3' style='width:80%;'><b>" . $_REQUEST['numero_identificacion_contrato'] . "</b></td>
                                </tr>
                                <tr>
                                    <td colspan='4' style='width:100%;'><b>Datos de Vivienda</b></td>
                                </tr>
                                <tr>
                                    <td align='center' style='width:20%;'>Tipo</td>
                                    <td align='center' style='width:26.6%;'>Estrato 2 (<b>" . $tipo_residencial_2 . "</b> )</td>
                                    <td align='center' style='width:26.6%;'>Estrato 1 (<b>" . $tipo_residencial_1 . "</b>)</td>
                                    <td align='center' style='width:26.6%;'>VIP (<b>" . $tipo_vip . "</b>)</td>
                                </tr>
                                <tr>
                                    <td style='width:21%;background-color:#efefef;'>Dirección</td>
                                    <td colspan='3' style='width:80%;'>" . $direccion_beneficiario . "</td>
                                </tr>
                                <tr>
                                    <td style='width:21%;background-color:#efefef;'>Departamento</td>
                                    <td align='center' style='width:26.6%;'>" . $_REQUEST['nombre_departamento'] . "</td>
                                    <td align='center' style='width:26.6%;background-color:#efefef;'>Municipio</td>
                                    <td align='center' style='width:26.6%;'> </td>
                                </tr>
                                <tr>
                                    <td style='width:21%;background-color:#efefef;'>Urbanización</td>
                                    <td colspan='3' style='width:80%;'>" . $this->limpiar_caracteres_especiales($_REQUEST['nombre_urbanizacion']) . "</td>
                                </tr>
                            </table>";

        $contenidoPagina .= $informacion_beneficiario;
        $contenidoPagina .= "<br>
                            <br>
                            <div align='center'><b>MANIFIESTO QUE:</b></div>
                            <br>
                            <p style='text-align:justify'>
                               1. El contratista entregó un computador portátil marca HP 245 G4 Notebook PC nuevo, a titulo de uso y goce hasta la terminación del contrato de aporte suscrito entre el Fondo TIC y la Corporación Politécnica. En consecuencia, el computador no puede ser vendido, arrendado,transferido, dado en prenda, servir de garantía, so pena de perder el beneficio.<br><br>
                               2. Respecto al computador descrito en la hoja 2 de este documento, dejo constancia que no se me cobro ningún tipo de cargo como usuario beneficiado del Proyecto de Conexiones Digitales y que el equipo fue entregado embalado, garantizando la integridad del mismo.<br><br>
                               3. Además certifico que se realizaron las siguientes pruebas de funcionalidad:
                            </p>
                             <br>
                             <div align='center'>
                                <table align='center' style='width:50%'>
                                    <tr>
                                        <td style='width:80%;background-color:#efefef;'>Correcto encendido/apagado</td>
                                        <td align='center' style='width:20%;'>SI</td>
                                    </tr>
                                    <tr>
                                        <td style='width:80%;background-color:#efefef;'>Equipo funcionando y navegando</td>
                                        <td align='center' style='width:20%;'>SI</td>
                                    </tr>
                                    <tr>
                                        <td style='width:80%;background-color:#efefef;'>Funciona el teclado, parlante y touchpad</td>
                                        <td align='center' style='width:20%;'>SI</td>
                                    </tr>
                                </table>
                             </div>
                             <br>
                            <p style='text-align:justify'>
                            4. La garantía del equipo es un año a partir de la fecha de entrega en la que se firma este documento.<br><br>
                            5. El Contacto de Garantía es la Corporación Politécnica, y me puedo comunicar con la línea gratuita las 24 horas del día de los 7 días de la semana. ((018000 961016)).<br><br>
                            6. Que con el fin de no perder la garantía del fabricante en la eventualidad de presentarse fallas, el beneficiario ni un tercero no autorizado por el fabricante, pueden manipular el equipo tratando de resolver el problema presentado.<br><br>
                            7. En caso de daño, hurto, el usuario debe hacer el reporte a la mesa de ayuda con numero 018000961016, lo cual debe quedar consignado en un ticket para la gestión y seguimiento del mismo.<br><br>
                            8. En caso de pérdida o hurto no habrá reposición del equipo.<br><br>
                            9. Que manifiesto mi entera conformidad y satisfacción del bien que recibo en la fecha y me obligo a realizar su correcto uso, custodia y conservación, autorizando al prestador del servicio (Corporación Politécnica) para que ejerza seguimiento y control sobre el mismo.<br><br>
                            10. Que a la terminación del plazo de ejecución de este contrato de comodato, tendré la opción de adquirir el bien antes descrito.
                            </p>";

        $contenidoPagina .= "</page>";

        $contenidoPagina .= "<page backtop='25mm' backbottom='10mm' backleft='20mm' backright='20mm' footer='page'>
                            <page_header>
                                <table  style='width:100%;' >
                                        <tr>
                                                <td align='center' style='width:100%;border=none;' >
                                                <img src='" . $this->rutaURL . "frontera/css/imagen/logos_contrato.png'  width='500' height='45'>
                                                </td>
                                                <tr>
                                                <td> </td>
                                                </tr>
                                                <tr>
                                                <td style='width:100%;border:none;text-align:center;'><br><br><b>008 - ACTA DE ENTREGA DE COMPUTADOR PORTÁTIL</b></td>
                                                </tr>

                                        </tr>
                                </table>
                            </page_header>
                            ";

        $contenidoPagina .= $informacion_beneficiario;
        $contenidoPagina .= "<br>
                            <br>
                            <div align='center'><b>CERTIFICA BAJO GRAVEDAD DE JURAMENTO:</b></div>
                            <br>
                            <p style='text-align:justify'>
                              1. Que recibe un computador portátil NUEVO, sin uso, original de fábrica y en perfecto estado de funcionamiento, con las siguientes características:
                            </p>
                            <br>
                            <table>
                                <tr>
                                    <td style='width:20%;background-color:#efefef;'><b>Modelo</b></td>
                                    <td style='width:30%'>" . $this->infoPortatil['modelo'] . "</td>
                                    <td style='width:18%;background-color:#efefef;'><b>Marca</b></td>
                                    <td style='width:32%'>" . $this->infoPortatil['marca'] . "</td>
                                </tr>
                                <tr>
                                    <td style='width:20%;background-color:#efefef;'><b>Procesador</b></td>
                                    <td style='width:30%'>" . $this->infoPortatil['modelo'] . "</td>
                                    <td style='width:18%;background-color:#efefef;'><b>Serial</b></td>
                                    <td style='width:32%'>" . $this->infoPortatil['serial'] . "</td>
                                </tr>
                                <tr>
                                    <td style='width:20%;background-color:#efefef;'><b>Disco Duro</b></td>
                                    <td style='width:30%'>" . $this->infoPortatil['disco_duro'] . "</td>
                                    <td style='width:18%;background-color:#efefef;'><b>Memoria RAM</b></td>
                                    <td style='width:32%'>" . $this->infoPortatil['memoria_ram'] . "</td>
                                </tr>
                                <tr>
                                    <td style='width:20%;background-color:#efefef;'><b>Cámara</b></td>
                                    <td style='width:30%'>" . $this->infoPortatil['camara'] . "</td>
                                    <td style='width:18%;background-color:#efefef;'><b>Sistema Operativo</b></td>
                                    <td style='width:32%'>" . $this->infoPortatil['sistema_operativo'] . "</td>
                                </tr>
                                <tr>
                                    <td style='width:20%;background-color:#efefef;'><b>Batería</b></td>
                                    <td style='width:30%'>" . $this->infoPortatil['bateria'] . "</td>
                                    <td style='width:18%;background-color:#efefef;'><b>Audio</b></td>
                                    <td style='width:32%'>" . $this->infoPortatil['audio'] . "</td>
                                </tr>
                                <tr>
                                    <td style='width:20%;background-color:#efefef;'><b>Tarjeta de Red<br>(Inalámbrica)</b></td>
                                    <td style='width:30%'>" . $this->infoPortatil['targeta_red_inalambrica'] . "</td>
                                    <td style='width:18%;background-color:#efefef;'><b>Tarjeta de Red<br>(Alámbrica)</b></td>
                                    <td style='width:32%'>" . $this->infoPortatil['targeta_red_alambrica'] . "</td>
                                </tr>
                                <tr>
                                    <td style='width:20%;background-color:#efefef;'><b>Pantalla</b></td>
                                    <td style='width:30%'>" . $this->infoPortatil['pantalla'] . "</td>
                                    <td style='width:18%;background-color:#efefef;'><b>Cargador</b></td>
                                    <td style='width:32%'>" . $this->infoPortatil['cargador'] . "</td>
                                </tr>
                                <tr>
                                    <td style='width:20%;background-color:#efefef;'><b>Sitio Web de Soporte</b></td>
                                    <td colspan='3' style='width:80%'></td>
                                </tr>
                                <tr>
                                    <td style='width:20%;background-color:#efefef;'><b>Teléfono de Soporte</b></td>
                                    <td colspan='3' style='width:80%'> </td>
                                </tr>
                            </table>
                            <br>
                            <p style='text-align:justify'>
                            2. Que el computador recibido no presenta rayones, roturas, hendiduras o elementos sueltos.<br><br>
                            3. Que entiende que el computador recibido no tiene costo adicional y se encuentra incorporado al contrato de servicio suscrito con la Corporación Politécnica Nacional de Colombia.<br><br>
                            4. Que se compromete a velar por la seguridad del equipo y a cuidarlo para mantener su capacidad de uso y goce en el marco del contrato de servicio suscrito con la Corporación Politécnica Nacional de Colombia.<br><br>
                            5. Que se compromete a participar en por lo menos 20 horas de capacitación sobre el manejo del equipo y/o aplicativos de uso productivo de esta herramienta como parte del proceso de
                            apropiación social contemplado en el Anexo Técnico del proyecto Conexiones Digitales II.<br><br>
                            </p>";
        $contenidoPagina .= "</page>";

        $this->contenidoPagina = $contenidoPagina;
    }

    public function informacionEstandarPortatil()
    {

        $this->infoPortatil = array(

            'camara'                     => 'Integrada 720 px HD Grabación, Video y Fotografía',

            'mouse_tipo'                 => 'Touchpad con capacidad multi-touch',
            'sistema_operativo'          => 'Ubuntu',

            'targeta_audio_video'        => 'Incorporados',

            'disco_duro'                 => '500 GB velocidad de 5.400 rpm',

            'autonomia'                  => 'Mín. Cuatro horas – 6 celdas',

            'puerto_usb'                 => '(2)Usb 2.0 y (3) Ubs 3.0',

            'voltaje'                    => '100 v a 120 v - 50 Hz a 60 Hz',

            'targeta_memoria'            => 'multi-format digital media reader(soporta SD, SDHC, SDXC)',

            'salida_video'               => 'VGA 1 y HMDI 1',

            'cargador'                   => 'Adaptador Smart AC 100 v a 120 v',

            'bateria_tipo'               => 'Recargable Lithium Ion',

            'teclado'                    => 'Español(Internacional)',

            'marca'                      => 'Hewlett Packard',

            'modelo'                     => 'HP 245 G4 Notebook PC',

            'procesador'                 => 'AMD A8-7410 2200 MHz cores 2.2 GHz',

            'arquitectura'               => '64 Bits',

            'memoria_ram'                => 'DDR3 4096 MB',

            'compatibilidad_memoria_ram' => 'PAE, NX, y SSE 4.x',

            'tecnologia_memoria_ram'     => 'DDR3',

            'antivirus'                  => 'Clamav Antivirus',

            'disco_anti_impacto'         => 'N/A',

            'serial'                     => '',

            'audio'                      => 'Integrado Mono/Estereo',

            'bateria'                    => '41610 mWh',

            'targeta_red_alambrica'      => 'Integrada',

            'targeta_red_inalambrica'    => 'Integrada',

            'pantalla'                   => 'HD SVA anti-brillo LED14"',

        );

    }

    public function limpiar_caracteres_especiales($s)
    {
        $s = ereg_replace("[áàâãª]", "a", $s);
        $s = ereg_replace("[ÁÀÂÃ]", "A", $s);
        $s = ereg_replace("[éèê]", "e", $s);
        $s = ereg_replace("[ÉÈÊ]", "E", $s);
        $s = ereg_replace("[íìî]", "i", $s);
        $s = ereg_replace("[ÍÌÎ]", "I", $s);
        $s = ereg_replace("[óòôõº]", "o", $s);
        $s = ereg_replace("[ÓÒÔÕ]", "O", $s);
        $s = ereg_replace("[úùû]", "u", $s);
        $s = ereg_replace("[ÚÙÛ]", "U", $s);
        $s = str_replace("ñ", "n", $s);
        $s = str_replace("Ñ", "N", $s);
        //para ampliar los caracteres a reemplazar agregar lineas de este tipo:
        //$s = str_replace("caracter-que-queremos-cambiar","caracter-por-el-cual-lo-vamos-a-cambiar",$s);
        $s = str_replace("urbanizacion", "", strtolower($s));

        return trim(strtoupper($s));
    }
}
$miDocumento = new GenerarDocumento($this->sql);
