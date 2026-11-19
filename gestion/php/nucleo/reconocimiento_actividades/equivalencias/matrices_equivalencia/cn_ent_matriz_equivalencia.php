<?php
/**
 * Representa una Matriz de Equivalencia definida para un Plan Versión destino.
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class cn_ent_matriz_equivalencia extends cn_entidad
{
	//-------------------------------------------------------------------
	
	function get_datos_generales()
	{
		return $this->tabla('matriz_equivalencia')->get();
	}

	/**
	 * Se utiliza desde el cn man cuando se agrega desde el árbol una nueva matriz, sólo para agregar el id de 
	 * plan_version_destino asociado, por lo tanto no debe invocarse al validar datos.
	 * 
	 */
	function set_datos_iniciales($datos)
	{
		$this->tabla('matriz_equivalencia')->set($datos);	
	}

	function set_datos_generales($datos)
	{
		$this->validar_datos_generales($datos);
		// Si se seleccionó todos los planes o todas las versiones, los campos plan o versión origen van en nulo.
		$datos = $this->modificar_campos_alcance_todos($datos['alcance'], $datos);
		$this->tabla('matriz_equivalencia')->set($datos);
	}	
	
	function modificar_campos_alcance_todos($alcance, $datos)
	{
		if ($alcance == equivalencia::matriz_alcance_entre_planes_misma_prop && $datos['plan_origen'] == 'T') {
			$datos['plan_origen'] = null;
		} elseif ($alcance == equivalencia::matriz_alcance_entre_versiones && $datos['plan_version_origen'] == 'T') {
			$datos['plan_version_origen'] = null;
		} 
		return $datos;
	}
	
	function get_id_matriz()
	{
		return $this->tabla('matriz_equivalencia')->get_columna('matriz');
	}
	
	function cargar_grupos()
	{
		$this->dep('sga_equiv_grupos')->cargar(array('matriz' => $this->get_id_matriz()));		
	}
			
	function get_filas_grupos()
	{
		return $this->dep('sga_equiv_grupos')->get_filas();
	}
	
	function resetear_grupos()
	{
		$this->dep('sga_equiv_grupos')->resetear();
	}
	
	function sincronizar_grupos()
	{
		$this->dep('sga_equiv_grupos')->sincronizar();
	}
	
	function procesar_grupos($datos)
	{
		$this->dep('sga_equiv_grupos')->procesar_filas($datos);
	}
	
	
	//---------------------------------------------------------------------
	
	function validar_datos_generales($datos)
	{
		// Se verifican que se hayan completado los campos correspondientes a matriz activa.
		$this->validar_campos_matriz_activa($datos['activo'], $datos);
		// Se verifica que se hayan completado los campos correspondientes al alcance definido para la matriz.
		$this->validar_campos_alcance($datos['alcance'], $datos);
		// Para el caso de matriz automática se verifica que ya no exista otra equivalente en cuanto a origen y destino.
		$this->validar_origen_destino($datos['alcance'], $datos);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
 	  * Determina si tienen valor los campos correspondientes según el valor del campo Activo de la Matriz.
      *	@param char 	activo	determina si la matriz está o no activa.
      * @param array	datos	datos generales de la matriz. 
     */
	function validar_campos_matriz_activa($activo, $datos) 
	{
		if ($activo == comunes::no) {
			if (!isset($datos['nro_resolucion_baja'])) {
				$this->set_error('800_dato_dep_obligatorio', array('Nro de Resolución de Baja', 'Activa'));
			}
			if (!isset($datos['fecha_baja'])) {
				$this->set_error('800_dato_dep_obligatorio', array('Fecha de Baja', 'Activa'));
			}
		} else {
			//No pueden editarse los campos fecha baja y nro resolucion baja.	
			$this->limpiar_campos_no_editables($datos, array('nro_resolucion_baja', 'fecha_baja')); 
		}
	}
	
	/**
 	  * Valida si se completaron los campos correspondientes al Alcance seleccionado para la Matriz.
      *	@param str 		alcance		alcance definido para la matriz.
      * @param array	datos		datos generales de la matriz. 
    */
	function validar_campos_alcance($alcance, $datos) 
	{
		if ($alcance == equivalencia::matriz_alcance_general) {
			$this->limpiar_campos_no_editables($datos, array('propuesta_origen', 'plan_origen', 'plan_version_origen'));
		} elseif ($alcance == equivalencia::matriz_alcance_entre_propuestas) {
			if (!isset($datos['propuesta_origen'])) {
				$this->set_error('800_dato_dep_obligatorio', array('Propuesta Origen', 'Alcance'));
			}
			$this->limpiar_campos_no_editables($datos, array('plan_origen', 'plan_version_origen'));
		} elseif ($alcance == equivalencia::matriz_alcance_entre_planes_otra_prop || $alcance == equivalencia::matriz_alcance_entre_planes_misma_prop) {
			if (!isset($datos['propuesta_origen'])) {
				$this->set_error('800_dato_dep_obligatorio', array('Propuesta Origen', 'Alcance'));
			}
			if (!isset($datos['plan_origen'])) {
				$this->set_error('800_dato_dep_obligatorio', array('Plan Origen', 'Alcance'));
			}
			$this->limpiar_campos_no_editables($datos, array('plan_version_origen'));
		} else {
			//Alcance entre Versiones.
			if (!isset($datos['plan_version_origen'])) {
				$this->set_error('800_dato_dep_obligatorio', array('Versión Origen', 'Alcance'));
			}	
		}
	}
	
	/**
 	  * Determina si ya existe otra Matriz de tipo automática, con origen/destino que se superpongan a la que se está 
	  * intentando crear.
	  * Se verifica sólo cuando el alcance de la matriz entre Planes o Versiones, y debe considerar si existe otra con
	  * igual origen y destino, considerando el caso especial de cuando la matriz es para 'Todos los planes' o 
	  * 'Todas las versiones'.
	  * 
      *	@param str 		alcance		alcance definido para la matriz.
      * @param array	datos		datos generales de la matriz. 
    */
	function validar_origen_destino($alcance, $datos) 
	{
		if ($datos['activo'] == comunes::si && $datos['aplicacion_automatica'] == comunes::si && $alcance <> equivalencia::matriz_alcance_general) {
			$datos_iniciales = $this->get_datos_generales();
			$plan_version_destino = $datos_iniciales['plan_version_destino'];
			$prop_origen = $datos['propuesta_origen'];
			$plan_origen = $datos['plan_origen'];
			$where = "	sga_equiv_matrices.aplicacion_automatica = '".comunes::si."'
						AND sga_equiv_matrices.activo = '".comunes::si."'
						AND sga_equiv_matrices.alcance = '$alcance'
						AND sga_equiv_matrices.plan_version_destino = $plan_version_destino
						AND sga_equiv_matrices.propuesta_origen = $prop_origen";
			// Para el caso de Edición, debe excluirse de las matrices con mismo origen-destino a la que se está actualmente editando.
			$id_matriz = $this->get_id_matriz();
			if (!empty($id_matriz)) {
				$where .= " AND sga_equiv_matrices.matriz <> $id_matriz";
			}
			if ($alcance == equivalencia::matriz_alcance_entre_propuestas) {
				$matrices = toba::consulta_php('co_matrices_de_equivalencia')->get_listado_con_prop_plan_version_origen_y_destino($where);
				if (!empty($matrices)) {
					$matriz = current($matrices);
					$param_msj = $matriz['propuesta_origen_nombre'];
				}
			} elseif ($alcance == equivalencia::matriz_alcance_entre_planes_otra_prop) {
				$where .= " AND sga_equiv_matrices.plan_origen = $plan_origen";
				$matrices = toba::consulta_php('co_matrices_de_equivalencia')->get_listado_con_prop_plan_version_origen_y_destino($where);
				if (!empty($matrices)) {
					$matriz = current($matrices);
					$param_msj = $matriz['propuesta_origen_nombre'] . ' - ' . $matriz['plan_origen_nombre'];
				}
			} elseif ($alcance == equivalencia::matriz_alcance_entre_planes_misma_prop) {
				$where .= ($plan_origen == 'T') ? " AND sga_equiv_matrices.plan_origen IS NULL" : " AND sga_equiv_matrices.plan_origen = $plan_origen";
				/**
				 * Se intenta crear una matriz para todos los Planes de la Propuesta origen ó
				 * se intenta crear una matriz para un Plan específico de la Propuesta origen.
				 * En el primer caso verifica si existe otra matriz para todos los Planes de la Propuesta origen.
				 * En el segundo caso verifica si existe otra matriz para el mismo Plan de la Propuesta origen.
				 * Siempre se consideran matrices de tipo automático y activas para el mismo alcance que se intenta definir.
				 */
				$matrices = toba::consulta_php('co_matrices_de_equivalencia')->get_listado_con_prop_plan_version_origen_y_destino($where);
				if (!empty($matrices)) {
					// Ya existe otra matriz automática con el mismo alcance para esa propuesta origen y plan versión destino.
					$matriz = current($matrices);
					if (isset($matriz['plan_origen'])) {
						// La matriz existente es para un plan específico.
						$param_msj = $matriz['propuesta_origen_nombre'] . ' - ' . $matriz['plan_origen_nombre'];
					} else {
						$param_msj =  $matriz['propuesta_origen_nombre'] . ' - Todos los Planes';
					}
				}
			} elseif ($alcance == equivalencia::matriz_alcance_entre_versiones) {
				$version_origen = $datos['plan_version_origen'];
				$where .= " AND sga_equiv_matrices.plan_origen = $plan_origen";
				$where .= ($version_origen == 'T') ? " AND sga_equiv_matrices.plan_version_origen IS NULL" : " AND sga_equiv_matrices.plan_version_origen = $version_origen";
				/**
				 * Análogo al caso de Planes de la misma Propuesta.
				 * En el primer caso verifica si existe otra matriz para todas las Versiones de la Propuesta-Plan origen.
				 * En el segundo caso filtra por propuesta, plan y versión origen igual a la seleccionada por el usuario.
				 */
				$matrices = toba::consulta_php('co_matrices_de_equivalencia')->get_listado_con_prop_plan_version_origen_y_destino($where);
				if (!empty($matrices)) {
					$matriz = current($matrices);
					if (isset($matriz['plan_version_origen'])) {
						// La matriz existente es para una versión específica.
						$param_msj = $matriz['propuesta_origen_nombre'] . ' - ' . $matriz['propuesta_origen_nombre'] . ' - ' . $matriz['plan_version_origen_nombre'];
					} else {
						// La matriz existente es para Todas las versiones del plan y propuesta orígen.
						$param_msj =  $matriz['propuesta_origen_nombre'] . ' - ' . $matriz['plan_origen_nombre'] . ' - Todas las Versiones';
					}
				}
			}
			if (!empty($matrices)) {
				$this->set_error('800EQU_solo_una_matriz_automatica', array($param_msj));
			}
		}
	}
}
?>