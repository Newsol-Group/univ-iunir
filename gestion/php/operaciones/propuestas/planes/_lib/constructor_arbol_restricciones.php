<?php

class constructor_arbol_restricciones extends constructor_arbol
{	
	protected function construir_elemento($clase, $info)
	{    
		/*try {
			throw new Exception('clase: '.$clase);
		} catch(Exception $e) {
			echo '<pre>',$e->getMessage(),'</pre><hr/><pre>',$e->getTraceAsString(),'</pre>';
			exit;
		} */
		if(!is_null($clase) && $clase!='' && class_exists($clase)) {
			$e = new $clase($info['elemento'], $info['nombre'], $info);
			$this->elementos[$info['elemento']] = $e;
			return $e;
		} else {
			throw new guarani_error("El elemento '".$info['nombre']."' (".$info['elemento'].") tiene asignada la clase '".$clase."' que no existe.");
		}
	}
}
?>