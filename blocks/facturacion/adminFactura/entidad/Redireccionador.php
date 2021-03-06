<?php

namespace facturacion\adminFactura\entidad;

if (! isset ( $GLOBALS ["autorizado"] )) {
	include "index.php";
	exit ();
}
class Redireccionador {
	public static function redireccionar($opcion, $valor = "") {
		$miConfigurador = \Configurador::singleton ();
		
		switch ($opcion) {
			
			case "InsertoInformacion" :
				$variable = 'pagina=adminFactura';
				$variable .= '&mensajeModal=exitoInformacion';
				break;
			
			case "UpdateInformacion" :
				$variable = 'pagina=adminFactura';
				$variable .= '&mensajeModal=exitoActualizacion';
				break;
			
			case "NoUpdateInformacion" :
				$variable = 'pagina=adminFactura';
				$variable .= '&mensajeModal=errorActualizacion';
				break;
			
			case "ErrorConsulta" :
				$variable = 'pagina=adminFactura';
				$variable .= '&mensajeModal=errorConsulta';
				break;
			
			case "NoInsertoInformacion" :
				$variable = 'pagina=adminFactura';
				$variable .= '&mensajeModal=errorCreacion';
				break;
		}
		foreach ( $_REQUEST as $clave => $valor ) {
			unset ( $_REQUEST [$clave] );
		}
		
		$url = $miConfigurador->configuracion ["host"] . $miConfigurador->configuracion ["site"] . "/index.php?";
		$enlace = $miConfigurador->configuracion ['enlace'];
		$variable = $miConfigurador->fabricaConexiones->crypto->codificar ( $variable );
		$_REQUEST [$enlace] = $enlace . '=' . $variable;
		$redireccion = $url . $_REQUEST [$enlace];
		
		echo "<script>location.replace('" . $redireccion . "')</script>";
		
		exit ();
	}
}
?>
