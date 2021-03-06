<?php

namespace facturacion\rolFactura\entidad;

class procesarAjax {
	public $miConfigurador;
	public $sql;
	public function __construct($sql) {
		$this->miConfigurador = \Configurador::singleton ();
		
		$this->ruta = $this->miConfigurador->getVariableConfiguracion ( "rutaBloque" );
		
		$this->sql = $sql;
		// Conexion a Base de Datos
		$conexion = "interoperacion";
		$esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB ( $conexion );
		
		switch ($_REQUEST ['funcion']) {
			case 'consultarRoles' :
				
				$cadenaSql = $this->sql->getCadenaSql ( 'consultarRoles' );
				$procesos = $esteRecursoDB->ejecutarAcceso ( $cadenaSql, "busqueda" );
				
				if ($procesos != false) {
					foreach ( $procesos as $key => $valor ) {
						
						$resultadoFinal [] = array (
								'id_rol' => "<center>" . $valor ['id_rol'] . "</center>",
								'n_rol' => "<center>" . $valor ['descripcion'] . "</center>",
						);
					}
					
					$total = count ( $resultadoFinal );
					$resultado = json_encode ( $resultadoFinal );
					$resultado = '{
                                "recordsTotal":' . $total . ',
                                "recordsFiltered":' . $total . ',
                                "data":' . $resultado . '}';
				} else {
					$resultado = '{
                                "recordsTotal":0 ,
                                "recordsFiltered":0 ,
                                "data": 0 }';
				}
				echo $resultado;
				
				break;
			
			case 'inhabilitarRol' :
				$conexion = "interoperacion";
				$esteRecursoDB = $this->miConfigurador->fabricaConexiones->getRecursoDB ( $conexion );
				
				$cadenaSql = $this->sql->getCadenaSql ( 'inhabilitarRol', $_REQUEST ['valor'] );
				$resultado = $esteRecursoDB->ejecutarAcceso ( $cadenaSql, "actualizar" );
				
				echo $resultado;
				break;
			
			case 'redireccionar' :
				include_once ("core/builder/FormularioHtml.class.php");
				
				$miFormulario = new \FormularioHtml ();
				
				if (! isset ( $_REQUEST ['tiempo'] )) {
					$_REQUEST ['tiempo'] = time ();
				}
				// Estas funciones se llaman desde ajax.php y estas a la vez realizan las consultas de Sql.class.php
				
				$_REQUEST ['ready'] = true;
				
				$valorCodificado = $this->miConfigurador->fabricaConexiones->crypto->codificar ( $_REQUEST ['valor'] . $_REQUEST ['id'] );
				
				$enlace = $_REQUEST ['directorio'] . '=' . $valorCodificado;
				
				echo json_encode ( $enlace );
				break;
		}
	}
}

$miProcesarAjax = new procesarAjax ( $this->sql );
exit ();
?>
