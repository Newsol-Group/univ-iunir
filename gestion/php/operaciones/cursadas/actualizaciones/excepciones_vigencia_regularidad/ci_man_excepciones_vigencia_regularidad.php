<?php
class ci_man_excepciones_vigencia_regularidad extends ci_navegacion
{	
	function evt__filtro__filtrar($datos)
	{		
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_vigencia_cursada = '';
			$vigencia_cursada = '';
			if (isset($datos['vigencia_cursada'])) {
				if ($datos['vigencia_cursada']['valor'] == comunes::si) {
					$vigencia_cursada = " IS NOT NULL";
				} else {
					$vigencia_cursada = " IS NULL";
				}
				$filtro_vigencia_cursada = " AND sga_excep_regularidad.vigencia_cursada $vigencia_cursada";
				$this->dep('filtro')->columna('vigencia_cursada')->resetear_estado();
			}
			$filtro_vigencia_revalida = '';
			$vigencia_revalida = '';
			if (isset($datos['vigencia_revalida'])) {
				if ($datos['vigencia_revalida']['valor'] == comunes::si) {
					$vigencia_revalida = " IS NOT NULL";
				} else {
					$vigencia_revalida = " IS NULL";
				}
				$filtro_vigencia_revalida = " AND sga_excep_regularidad.vigencia_revalida $vigencia_revalida";
				$this->dep('filtro')->columna('vigencia_revalida')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_vigencia_cursada $filtro_vigencia_revalida";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
		
	}
	
	function js_evt_guardar() 
	{
		return "
			{$this->objeto_js}.evt__guardar = function() {				
				var filas = {$this->objeto_js}.dependencia('editor').dependencia('seleccion_planes').filas();
				var check = false;
				for (id_fila in filas) {
					if ( {$this->objeto_js}.dependencia('editor').dependencia('seleccion_planes').ef('requerido').ir_a_fila(filas[id_fila]).chequeado())
						check = true;
				}
				if (!check) {
					notificacion.agregar('Debe seleccionar al menos un plan');
					return false;
				}
				return confirmar_no_cambios();
			}
			";
	} 
}
?>