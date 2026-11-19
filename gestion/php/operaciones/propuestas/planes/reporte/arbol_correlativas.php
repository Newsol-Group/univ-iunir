<?php 
class arbol_correlativas extends guarani_pers_ei_arbol
{
	function extender_objeto_js()
	{
		$vinculo = toba::vinculador()->crear_autovinculo();
		echo "
		{$this->objeto_js}.seleccionar_actividad = function(nodo) {
			var id_actividad = nodo.getAttribute('id_nodo');
			var vinculo = '{$vinculo}&actividad=' + id_actividad ;
			document.location.href = vinculo;
		};
		";
	}
}

?>