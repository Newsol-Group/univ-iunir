<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class cn_man_comisiones extends cn_manejador_entidad
{
	protected $s__indice_entidad_actual = 'comision';

	function cargar_atributos_parametros($propuestas , $planes )
	{
		$this->resetear_atributos_parametros();

		$operacion = guarani::get_id_operacion();
		
		// Se eliminan propuestas repetidas (por estar en más de un plan)
		$propuestas_plano = array();
		foreach ($propuestas as $propuesta) {
			if (!in_array($propuesta, $propuestas_plano)) {
				$propuestas_plano[] = $propuesta;
			}
		}
		$propuestas = $propuestas_plano;
		
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_plan, $planes);
		
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);

		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
		
		if (guarani::parametros()->get_valor('sq_usa_sanaviron') == comunes::no) {
			guarani::cache_parametros()->set_parametro_no_usado($operacion, 'sq_id_externo');
		}
	}
	
	/**
	 * Se extiende el ini del cn_manejador_entidad para poder crear el arreglo de nombres default de comisiones.
	 */
	function ini()
	{
		parent::ini();
		// Se setean los nombres por defecto para las comisiones (array a personalizar)
		comision::set_nombres_default_comisiones();
	}

	function get_indice_instancia()
	{
		return $this->s__indice_entidad_actual;
	}

	function usar_comisiones()
	{
		$this->s__indice_entidad_actual = 'comision';
	}

	function usar_subcomisiones()
	{
		$this->s__indice_entidad_actual = 'subcomision';
	}

	function get_clase_consulta()
	{
		return 'co_comisiones';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_comisiones')->get_comisiones_con_subcomisiones($filtro);
	}

	function cargar_comision($id_comision)
	{
		$this->usar_comisiones();
		$this->cargar(array('comision' => $id_comision));
		// Cargar atributos parámetros
		$datos_propuestas = $this->entidad()->get_datos_propuestas(false);		
		$propuestas = aplanar_matriz($datos_propuestas, 'propuesta');
		$plan = aplanar_matriz($datos_propuestas, 'plan');
		$this->cargar_atributos_parametros($propuestas, $plan);
	}

	function cargar_subcomision($id_subcom)
	{
		$this->usar_subcomisiones();
		$this->cargar(array('subcomision' => $id_subcom));
	}

	function agregar_comision_a_actividad($id_activ, $id_periodo)
	{
		// Cuando se agrega una nueva comisión, para resolver los primeros parámetros 
		// se utilizan las propuestas asociadas a la actividad		
		$filtro = "sga_planes.tipo_plan IN ('" . planes::tipo_estructurado . "', '".planes::tipo_personalizado."')";
		$datos_propuestas = toba::consulta_php('co_propuestas')->get_propuestas_por_elemento($id_activ, $filtro);
		$propuestas = aplanar_matriz($datos_propuestas, 'propuesta');
                $plan = aplanar_matriz($datos_propuestas, 'plan');
		$this->cargar_atributos_parametros($propuestas,$plan);
		
		$this->usar_comisiones();
		$this->nueva();
		$cant_comisiones = toba::consulta_php('co_comisiones')->get_cant_comisiones_de_actividad_en_per_lectivo($id_activ, $id_periodo);
		$nombre = comision::get_nombre_default_comision($cant_comisiones);

		$datos_iniciales['nombre'] = $nombre;
		$datos_iniciales['elemento'] = $id_activ;
		$this->entidad()->set_datos_generales($datos_iniciales);
	}

	function agregar_subcomision_a_comision($id_comision)
	{
		$this->usar_subcomisiones();
		$this->nueva();
		$datos_iniciales['comision'] = $id_comision;
		$this->entidad()->set_datos_iniciales($datos_iniciales);
	}

	/**
	 * Eliminación en cascada. Cuando se elimina una comisión se eliminan todas sus subcomisiones asociadas.
	 */
	function eliminar_comision($id_comision)
	{
		try {
			
			toba::db()->abrir_transaccion();
			
			// No se puede usar directamente $usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron'); porque da error.

			// Se obtienen las propuestas asociadas a la comisión
			$propuestas = array_column(toba::consulta_php('co_comisiones')->get_propuestas_comision($id_comision), 'propuesta');
			// Se obtienen las Responsables Académicas de esas propuestas
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
			// Se determina si se usa Sanavirón-Quilmes como módulo de pago para esas RAs
			$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron', $ras);

			// Si usa SQ y la comisión es cobrable la elimino en SQ (DELETE /servicios-versiones)
			if (($usa_sq == comunes::si) && $this->es_comision_cobrable($id_comision)) {
				cobro::eliminar_comision_cobrable($id_comision);
			}
			
			$this->eliminar_subcomisiones_comision($id_comision);
			$this->cargar_comision($id_comision);
			$this->eliminar();
			
			toba::db()->cerrar_transaccion();
			$this->resetear_atributos_parametros();
			
		} catch (Exception $e) {
			
			toba::db()->abortar_transaccion();
			$msj = guarani_mensajes::get('eliminar_error');
			throw new guarani_error($msj);
			
		}
	}

	/**
	 * Elimina todas las subcomisiones asociadas a la comisión dada. Para esto 
	 * primero las recupera, y luego desactiva las transacciones anidadas, para 
	 * que se eliminen o bien todas o bien ninguna.
	 */
	function eliminar_subcomisiones_comision($id_comision)
	{
		$subcomisiones = $this->co()->get_subcomisiones_comision($id_comision);
		$this->desactivar_transacciones_anidadas();
		foreach ($subcomisiones as $subcomision) {
			$this->eliminar_subcomision($subcomision['subcomision']);
		}
	}

	function eliminar_subcomision($id_subcom)
	{
		$this->usar_subcomisiones();
		$this->cargar_subcomision($id_subcom);
		$this->eliminar();
	}

	function duplicar_comision_para_actividad($id_comision, $id_activ, $id_periodo)
	{
		// Se genera el nombre default de la nueva comisión.
		$cant_comisiones = toba::consulta_php('co_comisiones')->get_cant_comisiones_de_actividad_en_per_lectivo($id_activ, $id_periodo);
		$nombre = comision::get_nombre_default_comision($cant_comisiones);

		// Se consulta el parámetro de sistema para Asistencia por hora o por clase y el parámetro de porcentaje.
		$filtro = "sga_planes.tipo_plan IN ('" . planes::tipo_estructurado . "', '".planes::tipo_personalizado."')";
		$datos_propuestas = toba::consulta_php('co_propuestas')->get_propuestas_por_elemento($id_activ, $filtro);
		$propuestas = aplanar_matriz($datos_propuestas, 'propuesta');
                $plan = aplanar_matriz($datos_propuestas, 'plan');
                $this->cargar_atributos_parametros($propuestas, $plan);
		$asistencia_hr = guarani::parametros()->get_valor(comision::asitencia_por_hs);
		$asist_cant_inasist = guarani::parametros()->get_valor(comision::asit_cant_inasist);

		try {
			toba::db()->abrir_transaccion();

			/**
			 * Se duplica la Comisión. Se verifican las condiciones de cobrable y si usa sanavirón para determinar 
			 * el estado en que debe crearse la comisión.
			 */
			$estado = null;
			if ($this->es_comision_cobrable($id_comision)) {
				$propuesta_curso = $this->get_propuesta_curso_asociada_sq($id_comision);
				// Se definió que si la comisión es cobrable, pero no se encuentra ninguna propuesta
				// de tipo Curso que use SQ asociada, no se hace nada.
				if (!is_null($propuesta_curso)) {
					$estado = comunes::estado_pendiente;
				}
			}
			
			$retorno = guarani::act('act_comisiones')->duplicar_comision($id_comision, $nombre, $asistencia_hr, $asist_cant_inasist, $estado);

			// Parámetros devueltos
			$parametros = current($retorno);
			$codigo_retorno = $parametros['codigo_retorno'];
			$id_nueva_comision = $parametros['comision'];
			$observaciones = $parametros['observaciones'];

			// Dependiendo del código retornado se realiza la acción correspondiente o se emite el mensaje de error de la duplicación.
			switch ($codigo_retorno) {
				case 0:
					// Comisión copiada ok.
					// Una vez duplicada la comisión con los datos respectivos en la base, se carga para su edición.
					$this->usar_comisiones();
					$this->cargar(array('comision' => $id_nueva_comision));
					// Se verifica si la comisión debe ser informada a Sanavirón-Quilmes.
					$this->entidad()->verificar_informar_sanaviron(array());
					break;
				case 1:
					// Comisión no copiada: registro en el reporte de Comisiones no copiadas
					throw new guarani_error($observaciones);
				case 2:
					// Una vez duplicada la comisión con los datos respectivos en la base, se carga para su edición.
					$this->usar_comisiones();
					$this->cargar(array('comision' => $id_nueva_comision));
					// Se verifica si la comisión debe ser informada a Sanavirón-Quilmes.
					$this->entidad()->verificar_informar_sanaviron(array());
					// Comisión copiada con errores: registro en el reporte de Comisiones con observaciones y la contabilizo
					toba::notificacion()->agregar($observaciones, 'info');
					break;
			}
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
		return $id_nueva_comision;
	}

	// Métodos análogos a los existentes en el CN ENT para chequear si la comisión debe informarse a SQ.
	// Se replican en el CN MAN ya que se utilizan al Duplicar una Comisión para verificar si la Comisión Origen cumple
	// estas condiciones, para crear la nueva Comisión con estado Pendiente. En este caso no hay cargada ninguna Entidad,
	// sólo se tiene el id de la comisión a duplicar asociado al nodo del árbol que disparó el evento.
	
	/**
	 * Verifica si la comisión dada está seteada con el atributo Cobrable en 'Sí'. Se utiliza al Duplicar Comisión para
	 * verificar con qué estado debe crearse la nueva Comisión (si se informa a SQ debe crearse inicialmente en Pendiente).
	 * @param int $id_comision clave de la comisión para la que se consulta si es Cobrable.
	 * @return boolean true or false dependiendo de si el atributo Cobrable de la comisión tiene el valor 'S'.
	 */
	function es_comision_cobrable($id_comision)
	{
		return toba::consulta_php('co_comisiones')->es_cobrable($id_comision);
	}
	
	/**
	 * Obtiene la propuesta asociada la comision que se utiliza para facturar la misma. Esta es aquella de tipo Curso
	 * y tal que el parametro 'sq_usa_sanaviron' = S para la misma. Si no se encuentra ninguna, retorna null. 
	 * Si hay mas de una, lanza un error.
	 * @param int $id_comision clave de la comisión para la que se busca la propuesta de tipo curso que usa SQ.
	 * @return array propuesta: Arreglo asociativo los datos de la fila de sga_comisiones_propuestas encontrada. 
	 * @throws guarani_error si hay multiples propuestas de tipo curso y que usan sanaviron para la comision cobrable.
	 */
	function get_propuesta_curso_asociada_sq($id_comision)
	{
		// Recupera las propuestas de Tipo Curso asociadas a la comisión.
		$where = "sga_comisiones_propuestas.propuesta IN (SELECT	propuesta
														  FROM		sga_propuestas
														  WHERE		sga_propuestas.propuesta_tipo = " . propuesta::tipo_curso . "
														  )";
		$propuestas_curso_comision = toba::consulta_php('co_comisiones')->get_propuestas_comision($id_comision, $where);
		$propuesta_curso = null;
		$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron');
		foreach ($propuestas_curso_comision as $propuesta) {
			// Si la propuesta es de Tipo Curso y esta definida como que usa sanaviron,
			// sera la utilizada para facturar la Comisión (no puede haber más de una).
			if ($usa_sq == comunes::si) {
				if (is_null($propuesta_curso)) {
					$propuesta_curso = $propuesta;
				} else {
					$msj = guarani_mensajes::get('800CUR_error_multiples_propuestas');
					throw new guarani_error($msj);
				}
			}
		}

		// Si no se encontró ninguna propuesta de tipo Curso y que use sanaviron asociada a la
		// comisión, se retorna null. En caso contrario, retorna la única asociada.
		return $propuesta_curso;
	}

}