<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class cn_ent_actividad extends cn_ent_elemento
{
	protected $tipo = elemento::tipo_actividad;
	protected $subtipo = null;
	
	//---------- paquete de INFO default de MATERIA -------------

	function set_datos_elemento($datos, $estoy_duplicando) 
	{
		$datos['entidad_tipo'] = elemento::tipo_actividad;
		$datos['compartible'] = 'S';
		$this->set_subtipo($datos['entidad_subtipo']);
		parent::set_datos_elemento($datos, $estoy_duplicando);
	}
	
	function set_datos_revision($datos)
	{
		//El valor de origen_creditos se setea por defecto a tipo A.
		$datos['origen_creditos'] = elemento::creditos_componentes;
		parent::set_datos_revision($datos);
	}
	
	function set_datos_materia($datos)
	{
		$this->validar_datos_materia($datos);
		$this->tabla('atributos')->set($datos);
	}

	function set_datos_extracurricular($datos)
	{
		$this->validar_datos_extracurricular($datos);
		$this->tabla('atributos')->set($datos);
	}

	function set_datos_extra($datos)
	{
		$this->validar_datos_extra($datos);
		$this->tabla('atributos')->set($datos);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('atributos')->get();
	}
	
	function set_subtipo($subtipo)
	{
		$this->subtipo = $subtipo;
	}
	
	function get_subtipo()
	{
		return $this->subtipo;
	}
	
	function get_g3entidad_subtipo()
	{
		return $this->get_subtipo();
	}
			
	function get_datos_ra()
	{
        return $this->tabla('resp_academicas')->get_filas();
	}

	function set_datos_ra($datos)
	{
		$this->tabla('resp_academicas')->procesar_filas($datos);
	}
	
	function get_datos_categorias()
	{
		return $this->tabla('elementos_categoria')->get_filas();
	}
	
	function set_datos_categorias($datos)
	{
		$this->validar_datos_categorias($datos);
		$this->tabla('elementos_categoria')->procesar_filas($datos);
	}
	
	//-----------------------------------------------------------------------------------
	
	function limpiar_campos_elemento()
	{
		$this->tabla('elemento')->set(array('codigo' => '', 'elemento' => NULL));
	}
	
	function duplicar()
	{
		$this->dep('datos')->forzar_insercion();
	}
	
	//-----------------------------------------------------------------------------------
	
	function validar_datos_materia($datos)
	{
		if (isset($datos['cantidad_de_aplazos'])) {
			$cantidad_aplazos = $datos['cantidad_de_aplazos'];
			$this->validar_cantidad_aplazos($cantidad_aplazos);
		}
		if (isset($datos['horas_totales'])) {
			$horas_totales = $datos['horas_totales'];
			$this->validar_horas_totales($horas_totales);
		}
		if (isset($datos['horas_semanales'])){
			$horas_semanales = $datos['horas_semanales'];
			$this->validar_horas_semanales($horas_semanales);
		}
		if (isset($horas_semanales) && isset($horas_totales)) {
			$this->validar_horas_totales_semanales($horas_totales, $horas_semanales);
		}
		if (isset($datos['vigencia'])) {
			$this->validar_vigencia($datos['vigencia']);
		}
		$this->validar_datos_creditos($datos);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_datos_extracurricular($datos)
	{
		$this->validar_datos_creditos($datos);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_datos_extra($datos)
	{
		if (isset($datos['horas_inicio_examen']) && isset($datos['horas_fin_examen'])) {
			$hora_inicio = $datos['horas_inicio_examen'];
			if (! guarani_validar::es_hora_minutos($hora_inicio)) {
				$this->set_error('800_dato_invalido', array('horas_inicio'));
			}
			$hora_fin = $datos['horas_fin_examen'];
			if (! guarani_validar::es_hora_minutos($hora_fin)) {
				$this->set_error('800_dato_invalido', array('horas_fin'));
			}

			if (! guarani_hora::es_mayor($hora_fin, $hora_inicio)) {
				$this->set_error('La Hora de Finalización debe ser posterior a la de Inicio.');
			}
		}
		
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_datos_creditos($datos)
	{
		if (isset($datos['creditos']) && $datos['creditos'] < 0) {
			$this->set_error('800_dato_invalido', array('campo Créditos Mínimos', 'debe ser un número mayor o igual a cero'));
		}

		if (isset($datos['creditos_min']) && !isset($datos['creditos_max']) || !isset($datos['creditos_min']) && isset($datos['creditos_max'])) {
			if (isset($datos['creditos_min'])) {
				$this->set_error('dato_faltante', array('Créditos Máximos'));
			} else {
				$this->set_error('dato_faltante', array('Créditos Mínimos'));
			}
		} else {
			if (isset($datos['creditos_min'])) {
				if ($datos['creditos_min'] >= $datos['creditos_max']) {
					$this->set_error('800_dato_invalido', array('campo Créditos Mínimos/Máximos', 'los Créditos Máximos deben ser mayores que los Créditos Mínimos'));
				} else {
					if (isset($datos['creditos'])) {
						if (! guarani_validar::esta_entre($datos['creditos'],$datos['creditos_min'],$datos['creditos_max'])) {
							$this->set_error('800_dato_invalido', array('campo Créditos Totales', 'los Créditos Totales deben estar entre el Mínimo y el Máximo'));
						}
					}
					if ($datos['creditos_min'] < 0 ) {
						$this->set_error('800_dato_invalido', array('campo Créditos Mínimos','debe ser un número mayor o igual a cero'));
					}
				}
			}
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_cantidad_aplazos($cantidad_aplazos)
	{
		$es_numero_entero = $cantidad_aplazos == round($cantidad_aplazos);
		$esta_en_rango = ($cantidad_aplazos >= 0);
		
		if (!$es_numero_entero || !$esta_en_rango) {
			$this->set_error('800_dato_invalido', array('Cantidad de Aplazos','debe ser un número entero mayor a cero'));
		}
	}
	
	function validar_horas_totales($horas_totales)
	{
		$esta_en_rango = ($horas_totales >= 0);
		
		if (!$esta_en_rango) {
			$this->set_error('800_dato_invalido', array('campo Horas totales','debe ser un número mayor a cero'));
		}
	}
	
	function validar_horas_semanales($horas_semanales)
	{
		$esta_en_rango = ($horas_semanales >= 0);
		
		if (!$esta_en_rango) {
			$this->set_error('800_dato_invalido', array('campo Horas semanales','debe ser un número mayor a cero'));
		}
	}
	
	function validar_horas_totales_semanales($horas_totales, $horas_semanales)
	{
		$horas_seteadas = isset($horas_totales) && isset($horas_semanales);
		
		if (($horas_seteadas) && ($horas_semanales >= $horas_totales)) {
				$this->set_error('800_dato_invalido', array('campo Horas Totales/Semanales', 'las Horas Totales deben ser mayores que las Horas Semanales'));
		}
	}
	
	function validar_vigencia($vigencia)
	{
		$es_numero_entero = $vigencia == round($vigencia);
		$esta_en_rango = ($vigencia >= 0);
		
		if (!$es_numero_entero || !$esta_en_rango) {
			$this->set_error('800_dato_invalido', array('Vigencia','debe ser un número entero mayor a cero'));
		}
	}

	/**
	 * Valida que no se ingrese una categoría que ya se encuentra relacionada a la actividad.
	 *
	 */
	function validar_datos_categorias($datos)
	{	
		if (isset($datos)) {
			$cuenta = array();
			foreach ($datos as $dato) {
				if (isset($dato['categoria'])) {
					if (isset($cuenta[$dato['categoria']])) {
						$this->set_error('dato_duplicado', array('Categoría', 'Actividad'));
						break;
					} else {
						$cuenta[$dato['categoria']] = 1;
					}
				}
			}
		}
	}
}

?>