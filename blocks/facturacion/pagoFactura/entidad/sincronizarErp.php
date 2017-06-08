<?php

namespace facturacion\pagoFactura\entidad;

if (! isset ( $GLOBALS ["autorizado"] )) {
	include "../index.php";
	exit ();
}

include_once 'Redireccionador.php';
include_once 'RestClient.class.php';
class sincronizarErp {
	public $miConfigurador;
	public $lenguaje;
	public $miFormulario;
	public $miSql;
	public $conexion;
	public $esteRecursoDB;
	public function __construct($lenguaje, $sql) {
		date_default_timezone_set ( 'America/Bogota' );
		
		$this->miConfigurador = \Configurador::singleton ();
		$this->miConfigurador->fabricaConexiones->setRecursoDB ( 'principal' );
		$this->lenguaje = $lenguaje;
		$this->miSql = $sql;
		
		$this->rutaURL = $this->miConfigurador->getVariableConfiguracion ( "host" ) . $this->miConfigurador->getVariableConfiguracion ( "site" );
		
		$this->rutaAbsoluta = $this->miConfigurador->getVariableConfiguracion ( "raizDocumento" );
		
		if (! isset ( $_REQUEST ["bloqueGrupo"] ) || $_REQUEST ["bloqueGrupo"] == "") {
			$this->rutaURL .= "/blocks/" . $_REQUEST ["bloque"] . "/";
			$this->rutaAbsoluta .= "/blocks/" . $_REQUEST ["bloque"] . "/";
		} else {
			$this->rutaURL .= "/blocks/" . $_REQUEST ["bloqueGrupo"] . "/" . $_REQUEST ["bloque"] . "/";
			$this->rutaAbsoluta .= "/blocks/" . $_REQUEST ["bloqueGrupo"] . "/" . $_REQUEST ["bloque"] . "/";
		}
		// Conexion a Base de Datos
		$conexion = "interoperacion";
		$this->esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB ( $conexion );
		
		$_REQUEST ['tiempo'] = time ();
	}
	
	
	/**
	 * inahbialitar Factura en ERPNext
	 */
	public function crearUrlFactura($parametros = '') {
		
		$base = array (
				"docname" => $parametros,
				"docstatus" => 2 
		);
		
		// URL base
		$url = $this->miConfigurador->getVariableConfiguracion ( "host" );
		$url .= $this->miConfigurador->getVariableConfiguracion ( "site" );
		$url .= "/index.php?";
		// Variables
		$variable = "pagina=openKyosApi";
		$variable .= "&procesarAjax=true";
		$variable .= "&action=index.php";
		$variable .= "&bloqueNombre=" . "llamarApi";
		$variable .= "&bloqueGrupo=" . "";
		$variable .= "&tiempo=" . $_REQUEST ['tiempo'];
		$variable .= "&metodo=inactivarFactura";
		$variable .= "&variables=" . json_encode ( $base );
		// Codificar las variables
		$enlace = $this->miConfigurador->getVariableConfiguracion ( "enlace" );
		$cadena = $this->miConfigurador->fabricaConexiones->crypto->codificar_url ( $variable, $enlace );
		// URL definitiva
		$material = $url . $cadena;
		
		return $material;
	}
	
	/**
	 * Estado Pagada Factura en ERPNext
	 */
	public function crearUrlFactura($parametros = '') {
	
		$base = array (
				"docname" => $parametros,
				"docstatus" => 2
		);
	
		// URL base
		$url = $this->miConfigurador->getVariableConfiguracion ( "host" );
		$url .= $this->miConfigurador->getVariableConfiguracion ( "site" );
		$url .= "/index.php?";
		// Variables
		$variable = "pagina=openKyosApi";
		$variable .= "&procesarAjax=true";
		$variable .= "&action=index.php";
		$variable .= "&bloqueNombre=" . "llamarApi";
		$variable .= "&bloqueGrupo=" . "";
		$variable .= "&tiempo=" . $_REQUEST ['tiempo'];
		$variable .= "&metodo=pagarFactura";
		$variable .= "&variables=" . json_encode ( $base );
		// Codificar las variables
		$enlace = $this->miConfigurador->getVariableConfiguracion ( "enlace" );
		$cadena = $this->miConfigurador->fabricaConexiones->crypto->codificar_url ( $variable, $enlace );
		// URL definitiva
		$material = $url . $cadena;
	
		return $material;
	}
	
	
	public function cancelarFactura($url) {
		
		$variable = array (
				'estado' => 1,
				'mensaje' => "Error cancelando Factura en ERPNext" 
		);
		
		$operar = file_get_contents ( $url );

		$validacion = strpos ( $operar, 'modified_by' );
		
		if (is_numeric ( $validacion )) {
			
			$res = ( array ) json_decode ( $operar );
			$res2 = ( array ) ($res ['items'] [0]);
			
			$variable = array (
					'estado' => 0,
					'mensaje' => "Factura Cancelada con Éxito",
					'recibo' => $res2 ['parent'] 
			);
		}
		
		return $variable;
	}
	
}

$miProcesador = new sincronizarErp ( $this->lenguaje, $this->sql );
?>

