<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage EscalaDeNotas
 */
class cn_ent_escalas_notas extends cn_entidad
{
	function cargar($id)
	{	
		//Si es continua desactivar la tabla de notas
		if (isset($id['tipo']) && $id['tipo'] == escalas_notas::tipo_continua) {
			$this->dep('datos')->desactivar_tablas('notas');
		}
		parent::cargar($id);
	}
	
	function verificar_editabilidad() 
	{
		if ($this->esta_en_uso()) {
			$this->bloquear_eliminacion();
		} else {
			$this->habilitar_eliminacion();
		}
	}

	function es_escala_continua()
	{
		return ($this->tabla('escalas_notas')->get_columna('tipo') == escalas_notas::tipo_continua);
	}

	function es_escala_discreta()
	{
		return ($this->tabla('escalas_notas')->get_columna('tipo') == escalas_notas::tipo_discreta);
	}

	function es_escala_numerica()
	{
		return ($this->tabla('escalas_notas')->get_columna('es_numerica') == comunes::si);
	}

	function get_dato($dato)
	{
		$datos = $this->get_datos();
		return $datos[$dato];
	}

	function get_id()
	{
		return $this->get_dato('escala_nota');
	}

	function get_nota_inicial()
	{
		return $this->get_dato('nota_inicial');
	}

	function get_nota_final()
	{
		return $this->get_dato('nota_final');
	}

	function esta_en_uso()
	{
		$escala = $this->tabla('escalas_notas')->get_columna('escala_nota');
		return $this->co()->esta_en_uso($escala);
	}

	function set_datos_escalas_notas($datos)
	{
		if ($this->es_escala_discreta()) {
			$datos['cantidad_decimales'] = 0;
			$datos['separador_decimal'] = '.';
			$datos['nota_inicial'] = null;
			$datos['nota_final'] = null;
		}
		
		$this->validar_datos_basicos($datos, true);

		if (!empty($datos['nota_inicial'])) {
			$datos['nota_inicial'] = str_replace(',', '.', $datos['nota_inicial']);
		}
		if (!empty($datos['nota_final'])) {
			$datos['nota_final'] = str_replace(',', '.', $datos['nota_final']);
		}
		
		if ((!is_null($datos['nota_inicial'])) && (!is_null($datos['nota_final']))) {
			$this->validar_notas_inicial_final($datos['nota_inicial'], $datos['nota_final']);
		}

		$instancias = $datos['instancias'];
		unset($datos['instancias']);
		
		$this->tabla('escalas_notas')->set($datos);
		$this->set_instancias($instancias);

	}
	
	function get_datos_escalas_notas()
	{
		$datos = $this->tabla('escalas_notas')->get();
		
		if (!empty($datos['separador_decimal'])) {
			$datos['nota_inicial'] = str_replace('.', $datos['separador_decimal'], $datos['nota_inicial']);
			$datos['nota_final'] = str_replace('.', $datos['separador_decimal'], $datos['nota_final']);	
		}
		
		if (isset($datos['cantidad_decimales']) && !is_null($datos['cantidad_decimales'])) {
			$cant_decimales = $datos['cantidad_decimales'];
			
			if (($cant_decimales > 0)) {
				if (!empty($datos['nota_inicial'])) {
					$datos['nota_inicial'] = substr($datos['nota_inicial'], 0, strrpos($datos['nota_inicial'], $datos['separador_decimal']) + $cant_decimales + 1);
				}
				if (!empty($datos['nota_final'])) {
					$datos['nota_final'] = substr($datos['nota_final'], 0, strrpos($datos['nota_final'], $datos['separador_decimal']) + $cant_decimales + 1);
				}
			}
		}

		$datos['instancias'] = aplanar_matriz($this->get_instancias(), 'instancia');		
		return $datos;
	}	

	function set_datos_notas($datos)
	{
		$this->validar_notas($datos, true);
		$this->tabla('notas')->procesar_filas($datos);
	}
	
	function get_datos_notas()
	{
		if (! $this->es_nueva() && $this->es_escala_continua()) {
			return $this->co()->get_detalle_escala_notas($this->get_id());
		}
		return rs_ordenar_por_columna($this->tabla('notas')->get_filas(), 'orden');
	}

	function set_datos_resultados($datos)
	{
		foreach ($datos as $key => $rango) {
			if ($rango['apex_ei_analisis_fila'] != 'B') {
				$datos[$key]['nota_inicial'] = str_replace(',', '.', $rango['nota_inicial']);
				$datos[$key]['nota_final'] = str_replace(',', '.', $rango['nota_final']);	
			}
		}
		$this->validar_resultados($datos, true);
		$this->tabla('resultados')->procesar_filas($datos);
	}

	function get_datos_resultados()
	{
		return $this->tabla('resultados')->get_filas();
	}

	function set_datos_conceptos($datos)
	{
		foreach ($datos as $key => $rango) {
			if ($rango['apex_ei_analisis_fila'] != 'B') {
				$datos[$key]['nota_inicial'] = str_replace(',', '.', $rango['nota_inicial']);
				$datos[$key]['nota_final'] = str_replace(',', '.', $rango['nota_final']);	
			}
		}
		$this->validar_conceptos($datos, true);
		$this->tabla('conceptos')->procesar_filas($datos);
	}

	function get_datos_conceptos()
	{
		return $this->tabla('conceptos')->get_filas();
	}
	
	/**
	 *  Elimina los Datos cargados correspondientas a una escala de notas de tipo continua, 
	 *  Eesto se usa en el caso que el usuario cambie el tipo a discreta una vez que ya completo datos de tipo continua.
	 * 
	 */
	function eliminar_datos_tipo_continua()
	{
		$this->tabla('resultados')->eliminar_filas();
		$this->tabla('conceptos')->eliminar_filas();
	}
	
	//--------------------------------------------------------------------------
	//---- Manejo de tabla sga_escalas_notas_instancias
	//--------------------------------------------------------------------------

	/**
	 * Retorna filas de la tabla sga_escala_notas_instancias (DT 'instancias')
	 **/
	function get_instancias()
	{
		return $this->tabla('instancias')->get_filas();
	}

	/**
	 * Carga el DT 'instancias' con los datos recibidos
	 */	
	function set_instancias($datos)
	{
		$instancias = $this->tabla('instancias')->get_filas();
		$instancias_aplanadas = aplanar_matriz($instancias, 'instancia');
		$instancias_a_borrar = array_diff($instancias_aplanadas, $datos);
		$instancias_a_insertar = array_diff($datos, $instancias_aplanadas);
		$instancias_asociativas = rs_convertir_asociativo($instancias, array('instancia'), apex_datos_clave_fila);

		foreach ($instancias_a_borrar as $instancia) {
			$this->tabla('instancias')->eliminar_fila($instancias_asociativas[$instancia]);
		}

		foreach ($instancias_a_insertar as $instancia) {
			$this->tabla('instancias')->nueva_fila(array('instancia' => $instancia));
		}
	}
	
	//--------------------------------------------------------------------------
	//---- Validacion general de los datos -------------------------------------
	//--------------------------------------------------------------------------

	function validar_datos_basicos($datos=null, $notificar=false)
	{
		if (!$datos) {
			return;
		}
		
		$this->validar_nombre_escala_notas($datos);
		
		if ($datos['tipo'] == escalas_notas::tipo_continua) {
			// Si es escala contínua...
			$this->validar_notas_extremos($datos);
			$this->validar_rango($datos);
		}

		if($notificar && $this->hay_errores()){
			throw guarani::error($this->errores);
		}
	}
	
	function validar_notas_inicial_final($nota_inicial, $nota_final)
	{
		if ($nota_final < $nota_inicial) {
			throw guarani::error('La nota final no puede ser mayor a la nota inicial.');
		}
	}
	
	function validar_nombre_escala_notas($datos)
	{
		$nombre = $datos['nombre'];
		$escala_nota = $this->tabla('escalas_notas')->get_columna('escala_nota');
		
		if (! toba::consulta_php('co_escalas_notas')->validar_nombre_escala_notas($nombre, $escala_nota)) {
			$this->set_error('dato_duplicado', array('nombre', 'Escala de Notas'));
		}
	}

	function validar_notas_extremos($datos)
	{
		$cant_decimales = $datos['cantidad_decimales'];
		
		$exp_reg = "/^[0-9]{1,5}";
		
		if ($cant_decimales > 0) {
			$separador = $datos['separador_decimal'];
			if ($separador == escalas_notas::separador_punto) {
				$exp_reg .= "\\";
			}
			$exp_reg .= $separador;
			$exp_reg .= '[0-9]{'.$cant_decimales.'}';
		}
		$exp_reg .= "$/";
		
		if (!preg_match($exp_reg, $datos['nota_inicial'])) {
			$this->set_error('800_dato_invalido', array('<strong>Nota Inicial</strong>', 'debido al separador decimal, cantidad de decimales seleccionados o por tener más de cinco dígitos en la parte entera'));
		}
		
		if (!preg_match($exp_reg, $datos['nota_final'])) {
			$this->set_error('800_dato_invalido', array('<strong>Nota Final</strong>', 'debido al separador decimal, cantidad de decimales seleccionados o por tener más de cinco dígitos en la parte entera'));
		}
	}
	
	function validar_rango($datos)
	{
		if ((!empty($datos['nota_inicial'])) && (!empty($datos['nota_final']))) {
			if ($datos['nota_inicial'] >= $datos['nota_final']) {
				$this->set_error('La nota final debe ser mayor a la nota inicial');
			}
		}
	}

	function validar_notas($datos=null, $notificar=false)
	{
		if (!$datos) {
			$datos = $this->get_datos_notas();
		}
		
		$cant_notas = guarani_arreglos::count($datos);
		if ($this->es_escala_discreta() && $cant_notas == 0) {
			$this->set_error('dato_faltante',array('Cargar <strong>Notas</strong>'));
		}
		
		// Si la escala indica que es discreta y numérica, se debe validar que cada nota ingresada sea un número.
		if ($cant_notas > 0 && $this->es_escala_discreta() && $this->es_escala_numerica($datos)) {
			foreach ($datos as $nota) {
				if (! empty($nota['nota']) && ! is_numeric($nota['nota'])) {
					$this->set_error('800_error_valor_numerico', array($nota['nota']));
				}
			}
		}

		if ($notificar && $this->hay_errores()){
			throw guarani::error($this->errores);
		}
	}

	function validar_datos_escala_notas()
	{
		$this->validar_datos_basicos();
		
		if ($this->es_escala_continua()) {
			$this->validar_carga_resultados();
		}
		
		if ($this->es_escala_discreta()) {
			$this->validar_notas();
		} else {//Continua
			//Si no es nueva no se valida porque no pueden modificarse
			if (($this->es_nueva()) || (!$this->esta_en_uso())) {
				$this->validar_resultados();
				$this->validar_conceptos();
			}
		}

		if($this->hay_errores()){
			throw guarani::error($this->errores);
		}
	}
	
	function validar_carga_resultados()
	{
		$rangos = $this->get_datos_resultados();
		$cant_rangos = guarani_arreglos::count($rangos);
		if ($cant_rangos == 0) {
			$this->set_error('dato_faltante',array('Cargar <strong>Resultados</strong>'));
		}
	}

	function validar_resultados($datos=null, $notificar=false)
	{
		if (!$datos) {
			$datos = $this->get_datos_resultados();
		}

		$rangos = array();
		foreach ($datos as $key => $rango) {
			if ((!empty($rango['apex_ei_analisis_fila']))) {
				if ($rango['apex_ei_analisis_fila'] != 'B') {
					$rango['nota_inicial'] = str_replace(',', '.', $rango['nota_inicial']);
					$rango['nota_final'] = str_replace(',', '.', $rango['nota_final']);
					$rangos[] = $rango;
				}
			} else {
				$rangos[] = $rango;
			}
		}
		
		$cant_rangos = guarani_arreglos::count($rangos);
		
		if ($cant_rangos > 0) {
		
			//Ordenar Asc por nota_inicial
			$rangos = rs_ordenar_por_columna($rangos , 'nota_inicial');
	
			//Validar no congruencia o solapamiento
			$datos_obligatorios = array('nota_inicial', 'nota_final', 'resultado');
	
			foreach ($rangos as $indx1 => $rango) {
				//Rango válido
				foreach ($datos_obligatorios as $dato_obligatorio => $etiqueta) {
					if (!isset($rango[$etiqueta])) {
						$this->set_error('dato_faltante', array($etiqueta));
					}
				}
				
				// Ajuste para permitir comparación
				$rango['nota_inicial'] = str_replace(',', '.', $rango['nota_inicial']);
				$rango['nota_final'] = str_replace(',', '.', $rango['nota_final']);
	
				if ($rango['nota_inicial'] > $rango['nota_final']) {
					$this->set_error('Resultados: La nota final debe ser mayor a la nota inicial');
				}
				if ($rango['nota_inicial'] < $this->get_nota_inicial()) {
					$this->set_error('Resultados: La nota inicial debe ser igual no puede ser anterior a la nota inicial de la escala.');
				}
				
				$nota_final = str_replace(',', '.', $this->get_nota_final());
				if ($rango['nota_final'] > $nota_final) {
					$this->set_error('Resultados: La nota final no puede ser superior a la nota final de la escala.');
				}
	
				//Solapamiento
				foreach ($rangos as $indx2 => $otro_rango) {
					if ($indx1 == $indx2) {
						continue;
					}
					if ($otro_rango['resultado'] == $rango['resultado']) {
						$this->set_error('Resultados: Hay duplicidad de resultados.');
						break;
					}
					if (($otro_rango['nota_inicial'] >= $rango['nota_inicial'] && $otro_rango['nota_inicial'] <= $rango['nota_final']) || ($otro_rango['nota_final'] >= $rango['nota_inicial'] && $otro_rango['nota_final'] <= $rango['nota_final'])) {
						$this->set_error('Resultados: Hay solapamiento entre rangos.');
						break;
					}
				}
			}

			if($notificar && $this->hay_errores()){
				throw guarani::error($this->errores);
			}
			
			//Validar valores sin definición
			$valores_sin_definir = array();
			$nota_final_anterior = $this->get_nota_inicial();
			foreach ($rangos as $indx2 => $rango) {	
				//Que el inicio sea igual al final del anterior + 1/10^decimales
				if (($indx2 == 0 && $rango['nota_inicial'] > $nota_final_anterior) ||
							($indx2 > 0 && $rango['nota_inicial'] > ($nota_final_anterior +  1/pow(10, $this->get_dato('cantidad_decimales'))))) {
						$valores_sin_definir[] = array('desde'=> $nota_final_anterior, 'hasta' => $rango['nota_inicial']);
				}
	
				//Que el último índice termine en la nota final de la cabecera
				if (($indx2 + 1)  == $cant_rangos) {
					if ($rango['nota_final'] < $this->get_nota_final()) {
						$valores_sin_definir[] = array('desde'=> $rango['nota_final'], 'hasta' => $this->get_nota_final());
					}
				}
				$nota_final_anterior = $rango['nota_final'];
			}
			
			if (guarani_arreglos::count($valores_sin_definir) > 0) {
				$msj_error = 'Resultados: Hay valores de la escala sin definir en los rangos (';
	
				foreach ($valores_sin_definir as $indx => $valores) {
					$msj_error .= "de {$valores['desde']} a {$valores['hasta']}";
					if ($indx + 1 < guarani_arreglos::count($valores_sin_definir)) {
						$msj_error .= '; ';
					}
				}
				$msj_error .= ')';
				$this->set_error($msj_error);
			}
	
			if($notificar && $this->hay_errores()){
				throw guarani::error($this->errores);
			}
		
		}
		
	}

	function validar_conceptos($datos=null, $notificar=false)
	{
		
		if (!$datos) {
			$datos = $this->get_datos_conceptos();
		}
		
		$rangos = array();
		foreach ($datos as $key => $rango) {
			if ((!empty($rango['apex_ei_analisis_fila'])) && ($rango['apex_ei_analisis_fila'] != 'B')) {
				$rangos[] = $rango;
			}
		}
		
		if (guarani_arreglos::count($rangos) > 0) {

			//Ordenar Asc por nota_inicial
			$rangos = rs_ordenar_por_columna($rangos , 'nota_inicial');
	
			//Validar no congruencia o solapamiento
			$datos_obligatorios = array('nota_inicial', 'nota_final', 'concepto');
			foreach ($rangos as $indx1 => $rango) {
				//Rango válido
				foreach ($datos_obligatorios as $dato_obligatorio => $etiqueta) {
					if (!isset($rango[$etiqueta])) {
						$this->set_error('dato_faltante', array($etiqueta));
					}
				}
				
				// Ajuste para permitir comparación
				$rango['nota_inicial'] = str_replace(',', '.', $rango['nota_inicial']);
				$rango['nota_final'] = str_replace(',', '.', $rango['nota_final']);
	
				if ($rango['nota_inicial'] > $rango['nota_final']) {
					$this->set_error('Conceptos: La nota final debe ser mayor a la nota inicial');
				}
				if ($rango['nota_inicial'] < $this->get_nota_inicial()) {
					$this->set_error('Conceptos: La nota inicial no puede ser anterior a la nota inicial de la escala.');
				}
				if ($rango['nota_final'] > $this->get_nota_final()) {
					$this->set_error('Conceptos: La nota final no puede ser superior a la nota final de la escala.');
				}
	
				foreach ($rangos as $indx2 => $otro_rango) {
					if ($indx1 == $indx2) {
						continue;
					}
					if ($otro_rango['concepto'] == $rango['concepto']) {
						$this->set_error('Conceptos: Hay duplicidad de conceptos.');
						break;
					}
					if (($otro_rango['nota_inicial'] >= $rango['nota_inicial'] && $otro_rango['nota_inicial'] <= $rango['nota_final']) || ($otro_rango['nota_final'] >= $rango['nota_inicial'] && $otro_rango['nota_final'] <= $rango['nota_final'])) {
						$this->set_error('Conceptos: Hay solapamiento entre rangos.');
						break;
					}
				}
			}
			if($notificar && $this->hay_errores()){
				throw guarani::error($this->errores);
			}
			
		}
	}

	function pre_sincronizar()
	{
		$this->validar_datos_escala_notas();
	}

	function sincronizar()
	{
		if (! $this->dep('datos')->hay_cambios()) {
			return;
		}
		$this->validar_editabilidad();
		$this->pre_sincronizar();

		try {
			toba::db('guarani')->abrir_transaccion();
			$this->dep('datos')->sincronizar();
			$escala = $this->recuperar_clave_db();
			if ($this->es_escala_continua()) {
				if ($this->es_nueva()) {
					guarani::act('act_escalas_notas')->generar_notas($escala['escala_nota']);
				} else {
					if (!$this->esta_en_uso()) {
						guarani::act('act_escalas_notas')->eliminar_notas($escala['escala_nota']);
						guarani::act('act_escalas_notas')->generar_notas($escala['escala_nota']);
					}
				}
			}
			toba::db('guarani')->cerrar_transaccion();
			return $escala;
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	function eliminar()
	{
		$this->validar_editabilidad();
		try {
			toba::db('guarani')->abrir_transaccion();
			if ($this->es_escala_continua()) {
				//Se eliminan las notas manualmente porque no se cargan en el DR
				//para no sobrecargar la memoria con datos que no se pueden editar.
				guarani::act('act_escalas_notas')->eliminar_notas($this->get_id());
			}
			$this->dep('datos')->eliminar_todo();
			toba::db('guarani')->cerrar_transaccion();
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}
			
	function get_datos_ra()
	{
        return $this->tabla('ras')->get_filas();
	}

	function set_datos_ra($datos)
	{
		$this->tabla('ras')->procesar_filas($datos);
	}
	
}