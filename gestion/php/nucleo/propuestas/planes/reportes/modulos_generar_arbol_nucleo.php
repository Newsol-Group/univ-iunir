<?php
class modulos_generar_arbol_nucleo {
	protected $elems;
	
	function generar_arbol($elementos, $pantalla) {
		foreach ($elementos as $elemento) {
			//- Construyo el elemento
			if(isset($elemento['modulo_revision'])) {
				$e = new modulos_nodo_arbol($elemento['elemento'], $elemento['modulo_revision'], $elemento['nombre'], $elemento['nombre_abreviado'], $elemento['entidad_subtipo'], $pantalla);
			} else {
				$e = new actividad_nodo_arbol($elemento['elemento'], $elemento['nombre'],$pantalla);
				if($pantalla == 'correlativas') {
					$r = new restriccion_nodo_arbol($elemento['elemento']."_A", 'Aprobada', $pantalla, 'A');
					$e->agregar_hijo($r);
					$r = new restriccion_nodo_arbol($elemento['elemento']."_C", 'Cursada', $pantalla, 'C');
					$e->agregar_hijo($r);
				}
			}
			if (!isset($elemento['elemento_padre'])) {
				$nodo_raiz = $e;
			} else {
				//- Asocio el elemento a su padre
				$padre = $elems[$elemento['elemento_padre']];
				$padre->agregar_hijo($e);
			}
			$elems[$elemento['elemento']] = $e;
			
		}
		return $nodo_raiz;
	}
}
?>