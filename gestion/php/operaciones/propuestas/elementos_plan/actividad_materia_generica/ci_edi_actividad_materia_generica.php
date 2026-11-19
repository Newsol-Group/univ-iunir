<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
 class ci_edi_actividad_materia_generica extends guarani_pers_ci
{
    protected $materias_seleccionadas;
    protected $s__filtro;
	protected $s__datos;
	protected $datos_elemento_temp;
   
	function conf()
	{
	    $this->dep('regla')->set_regla_tipo(regla::tipo_cumplimiento_modulos);
	    if (!$this->controlador()->estoy_duplicando()) {
		    if ($this->cn()->entidad()->pertenece_a_plan()) {
		    	$this->dep('elemento')->set_solo_lectura();	
		    	$this->dep('revision')->set_solo_lectura();
		    	$this->dep('regla')->set_solo_lectura();
		    	$this->dep('ua')->set_solo_lectura();
				$this->dep('ua')->desactivar_agregado_filas();	
				$this->dep('composicion')->set_solo_lectura();	
				$this->dep('composicion')->desactivar_agregado_filas();	
				$this->dep('composicion')->set_mostrar_utilerias_orden(false);
				$this->dep('materias')->eliminar_evento('seleccion');
				$this->dep('materias')->eliminar_evento('agregar');
			}
	    }
	}

	//---- funciones auxiliares para la composicion--------------------------------------
	/**
	 * Arma un filtro para hacer solo una consulta y traer todas las descripciones 
	       de las materias que forman la composicion 
	 *
	 * @param array $materias materias que se van a inculir en el filtro
	 * @return $filtro
	 */
	private function armar_filtro($materias)
	{
	    $codigos_materias = '';
	    $filtro = '';
	    $codigos_materias = implode(',', toba::db()->quote(aplanar_matriz($materias,'elemento_hijo')));
        if (! $codigos_materias == ''){
        	$filtro .= " sga_elementos_revision.elemento_revision IN ($codigos_materias)";
	    }
	    return  $filtro;
	}
	
	/**
	 * Agrega las descripciones de actividades guardadas en la BD 
	 * al $materias_en_comp que solo trae la clave
	 * 
	 * @param array $materias_en_comp "arreglo de dos dimensiones con formato recordset". 
	 * @return array $materias_en_comp , con las descripciones agregadas
	 */
    private function agregar_descripciones($materias_en_comp)
	{
	    /* se arma un filtro para hacer solo una consulta y traer todas las descripciones 
	       de las materias que forman la composicion     */ 
	    $filtro = $this->armar_filtro($materias_en_comp);	
	    // Se arma un array descripciones, con indice el id del elemento para mas rapido acceso
	    // y no tener que hacer un foreach dentro de otro foreach
	    $descripciones = array();
    	$consulta = toba::consulta_php('co_elementos')->get_actividades($filtro);
	    foreach ($consulta as $key => $value){
	        $descripciones[$value['elemento_revision']]['codigo']= $value['codigo'];
	        $descripciones[$value['elemento_revision']]['nombre']= $value['nombre'];
	        $descripciones[$value['elemento_revision']]['estado_nombre']= $value['estado_nombre'];
	    }	
	    foreach ($materias_en_comp as $key => $value){
	        $materias_en_comp[$key]['codigo']= $descripciones[$value['elemento_hijo']]['codigo'];
	        $materias_en_comp[$key]['nombre']= $descripciones[$value['elemento_hijo']]['nombre']; 
	        $materias_en_comp[$key]['estado']= $descripciones[$value['elemento_hijo']]['estado_nombre'];
	    }
	    return $materias_en_comp;
	}
		
	/**
	 * Busca en $materias_en_comp la clave 'x_dbr_clave' del elemento correspondiente a $id,
	 *
	 * @param array $materias_en_comp "arreglo de dos dimensiones con formato recordset". 
	 * @param integer $id del elemento que se va a buscar en $materias_en_comp 
	 * @return integer 
	 */
	private function buscar_seleccion($materias_en_comp,$id)
	{
	    foreach ($materias_en_comp as $indice => $materia){
	        if ($materia['elemento_hijo'] == $id) {
	            return $materia['x_dbr_clave'];
	        }
	    }
        return '0';
    }
	
	//---- composicion ------------------------------------------------------------------
    
	function evt__composicion__modificacion($datos)
	{
	    $this->cn()->entidad()->set_datos_comp($datos);
	}

	//El formato debe ser una matriz array('id_fila' => array('id_ef' => valor, ...), ...)
	function conf__composicion(form_ml_composicion $form_ml)
	{
		$form_ml->ef('puntaje')->set_expreg(comunes::er_numero_dos_decimales);
		/**
		 * Si el modo de cumplimiento del módulo no es Por Puntaje (regla = 100 o 114), ocultar la columa correspondiente 
		 * y eliminar su obligatoriedad.
		 */
		$datos_regla = $this->cn()->entidad()->get_datos_materia();
		if (isset($datos_regla['regla']) && $datos_regla['regla'] != regla::por_puntaje_aprobadas && $datos_regla['regla'] != regla::por_puntaje_cursadas) {
			$form_ml->desactivar_efs(array('puntaje'));
		}
		$form_ml->set_ocultar_agregar();
	    $materias_en_comp = $this->cn()->entidad()->get_datos_comp();
        $materias_en_comp = $this->agregar_descripciones($materias_en_comp);
		$form_ml->set_datos($materias_en_comp);
	}

	//---- elemento ---------------------------------------------------------------------

	function evt__elemento__modificacion($datos)
	{
		$this->datos_elemento_temp = $datos;
	    $this->cn()->entidad()->set_datos_elementos($datos, $this->controlador()->estoy_duplicando());
	}

	//El formato del carga debe ser array('id_ef' => $valor, ...)
	function conf__elemento(toba_ei_formulario $form)
	{
		if (isset($this->datos_elemento_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_elemento_temp);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_elementos());
		}
	}

	//---- materias ---------------------------------------------------------------------

	function evt__materias__seleccion($seleccion)
	{
		$this->materias_seleccionadas = $seleccion;
	}

	function evt__materias__agregar()
	{
		if (!is_null($this->materias_seleccionadas)) {
			foreach ($this->materias_seleccionadas as $materia) {
				$this->cn()->entidad()->add_datos_comp($materia);
			}
		}
	}

	//El formato del retorno debe ser array( array('columna' => valor, ...), ...)
	function conf__materias(toba_ei_cuadro $cuadro)
	{
		if ($this->s__filtro <> '') $this->s__filtro .= ' AND ';
		$this->s__filtro .= " sga_elementos.estado = '".comunes::estado_activo."'";
		$comp = $this->dep('composicion')->get_datos();
		if ($comp != array()) {
			$act_comp = guarani_sql::armar_condicion_in($comp, 'sga_elementos_revision', $campo='elemento_revision', $clave='elemento_hijo', true);
			$this->s__filtro .= ' AND '.$act_comp;
		}	
		$datos = toba::consulta_php('co_elementos')->get_actividades($this->s__filtro);
		$cuadro->set_datos($datos);
	}

	//---- regla ------------------------------------------------------------------------
	
/*	function conf__form_regla()
	{
		$mat = $this->datos_regla_temp;
		if (isset($mat['parametros'])) {
			$parametros = $mat['parametros'];
		} else {
			$parametros = null;
		}
		
    	$this->dep('regla')->set_regla($mat['regla'], $parametros);
	}
*/	
	function get_datos_regla()
	{
		$this->dep('regla')->resetear();
    	$mat = $this->cn()->entidad()->get_datos_materia();
		
		$parametros = null;
    	if (isset($mat['parametros'])) {
			$parametros = $mat['parametros'];
		}
		
		$regla = null;
    	if (isset($mat['regla'])) {
			$regla = $mat['regla'];
		}
		
    	return (array('regla' => $regla, 'parametros' => $parametros));
	}
	
	function evt__form_regla__modificacion($datos_regla) 
	{
		if (!isset($datos_regla['parametros'])) {
			$datos_regla['parametros'] = null;
		}
		$this->cn()->entidad()->set_datos_materia($datos_regla);
	}

	//---- revision ---------------------------------------------------------------------

	function evt__revision__modificacion($datos)
	{
		// Armo el arreglo para grabar los datos 
		$datos_rev = $datos;
		unset($datos_rev['creditos_otorgados']);
		
		$this->cn()->entidad()->set_datos_materia($datos_rev);

		$datos_atrib = array();
		
		if (!is_null($datos['creditos_otorgados'])) {
			$datos_atrib['creditos'] = $datos['creditos_otorgados'];
		}
		$this->cn()->entidad()->set_datos_atrib($datos_atrib);			
	}

	//El formato del carga debe ser array('id_ef' => $valor, ...)
	function conf__revision(toba_ei_formulario $form)
	{
    	$at = $this->cn()->entidad()->get_datos_atrib();
    	$mat = $this->cn()->entidad()->get_datos_materia();
    	
		$parametros = null;
    	if (isset($mat['parametros'])) {
			$parametros = $mat['parametros'];
		}
		
		$regla = null;
    	if (isset($mat['regla'])) {
			$regla = $mat['regla'];
		}
		
    	$this->dep('regla')->set_regla($regla, $parametros);
    	
		if ((isset($at['creditos'])) && ($mat['origen_creditos']!='N')) {
    		$mat['creditos_otorgados'] = $at['creditos'];
		}
		
    	$form->set_datos($mat);
    	
	}
	//---- ua ---------------------------------------------------------------------

	function evt__ua__modificacion($datos)
	{
	    $this->cn()->entidad()->set_datos_ua($datos);
	}

	//El formato debe ser una matriz array('id_fila' => array('id_ef' => valor, ...), ...)
	function conf__ua()
	{
	    $uas = $this->cn()->entidad()->get_datos_ua();
		return $uas;
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (!$this->controlador()->estoy_duplicando()) {
			
			//Busca todos los planes donde se encuentra la materia generica
			$datos = Array();
			$planes = toba::consulta_php('co_planes')->get_lista_planes();
			$elemento = $this->cn()->entidad()->dep('datos')->tabla('elemento')->get_filas();
			if (isset($elemento[0]['elemento'])) {
				$planes_elemento = toba::consulta_php('co_planes')->get_planes_elemento($elemento[0]['elemento']);
				foreach ($planes_elemento as $plan_elemento) {
						foreach ($planes as $plan){
							if ($plan_elemento['plan_version'] == $plan['plan_version']){
								$datos[]=$plan;
							}
						}
				}
			}
			$cuadro->set_datos($datos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_materias --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}	
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = "";
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		$this->s__filtro = "";
	}

}