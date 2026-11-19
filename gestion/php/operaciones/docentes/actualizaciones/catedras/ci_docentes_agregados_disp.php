<?php
class ci_docentes_agregados_disp extends guarani_pers_ci
{
	protected $s__filtro_asoc;
	protected $s__datos_asoc;
	protected $datos_asoc_temp;
	
	protected $s__filtro_disp;
	protected $s__datos_disp;
	
	protected $docentes_seleccionados;

	//-----------------------------------------------------------------------------------
	//---- form_ml_asociados ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_asociados(toba_ei_formulario $form)
	{
		if (isset($this->datos_asoc_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_asoc_temp);		
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_docentes_asoc());
		}
		
		$form->set_solo_lectura(array('legajo_docente', 'nombre_docente'));
	}
	
	function conf_fila__form_ml_asociados($fila)
	{
		// Se localiza el registro correspondiente
		$datos_docentes = $this->cn()->entidad()->get_datos_docentes_asoc();
		foreach ($datos_docentes as $key => $dato) {
			if ($dato['x_dbr_clave'] == $fila) {
				$registro = $dato;
				break;
			}
		}
		
		if ($registro['estado_docente'] == comunes::estado_baja_desc) {
			$this->dep('form_ml_asociados')->set_solo_lectura('estado');
		}
	}
	
	function evt__form_ml_asociados__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_docentes($datos);
		} catch ( guarani_error $e ) {
			$this->datos_asoc_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_fila_docente_asociado($seleccion);
	}
	
	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro_disp)) {
			$msg_eof = guarani_mensajes::get('800_eof_cuadro_sinfiltro');
			$cuadro->set_eof_mensaje($msg_eof);
		} else {		
			$docentes = $this->cn()->entidad()->get_datos_docentes_asoc();
			$filtro = 'true';
				
			if (!empty($docentes)) {
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($docentes, 'sga_docentes', 'docente', 'docente', true);
			}
					
			if (!((is_null($this->s__filtro_disp)) || ($this->s__filtro_disp == ''))) {
				$filtro .= " AND $this->s__filtro_disp";
			}
			
			$filtro .= " AND sga_docentes.estado <> '".comunes::estado_baja."'";
				
			$datos = $this->cn()->get_listado_docentes($filtro);
			$cuadro->set_datos($datos);
		}
	}

	function evt__disponibles__agregar()
	{
		if (!empty($this->docentes_seleccionados)) {
			foreach ($this->docentes_seleccionados as $docente) {
				$this->cn()->entidad()->agregar_fila_docente_asociado($docente);
			}			
		}

	}
	
	function evt__disponibles__seleccion($seleccion)
	{
		$this->docentes_seleccionados = $seleccion;
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro_disp ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------


	function evt__filtro_disp__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_disp = $datos;
			$this->s__filtro_disp = $this->dep('filtro_disp')->get_sql_where();
		} else {
			unset($this->s__datos_disp);
			$this->s__filtro_disp = '';
		}
	}


	function evt__filtro_disp__cancelar()
	{
		unset($this->s__datos_disp);
		$this->s__filtro_disp = '';
	}


	function conf__filtro_disp(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_disp)) {
			$filtro->set_datos($this->s__datos_disp);
		}
	}

	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		$msj_confirm_consulta = guarani_mensajes::get('800_conf_filtrovacio', array('Docentes'));
        $id_js_filtro = $this->dependencia('filtro_disp')->get_id_objeto_js();
        //La confirmación de la consulta se realiza solo si se está en la pantalla de selección (ya que ni el filtro ni su evento
        //Filtrar existen en la pantalla de edición).
        if ($this->get_id_pantalla() == 'pant_inicial') {
        	
			echo "
				//Chequea si ninguno de los campos (columnas) del filtro tienen estado, es decir el filtro es vacío, el usuario
				//debe confirmar la ejecución de la consulta.
				{$id_js_filtro}.evt__filtrar = function() {
					//Obtiene las filas y los campos de la variable interna que se mantiene en el filtro de columnas y valores (ei_filtro.js).
					var filas = this._filas;
					var campos = this._efs;
					var confirmar_cons = true;
					for (id_fila in filas) {
						var id_ef = this._filas[id_fila];
						if (campos[id_ef].tiene_estado()) {
							confirmar_cons = false;
							break;
						}
					}
					if (confirmar_cons) {
						return confirmar_consulta();
					}
				}
				
				function confirmar_consulta(proyecto, operacion, url, es_popup) {
					if (! es_popup || typeof(es_popup) == 'undefined') {
						resp = confirm('$msj_confirm_consulta');
						if (resp) {
							return true;
						} else {
							return false;
						}
					}
					return true;
				}
				
			";
        }
	}
}
?>