<?php
namespace siu\operaciones\ficha_alumno;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_home extends pagelet
{
	function get_nombre()
	{
		return 'home';
	}
	
	public function get_vista_form()
	{
		return $this->controlador->get_vista_form();
	}
	
	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos = array_merge($this->get_vista_form()->get_js_files(), $archivos);
		return $archivos;
	}
	
	function prepare()
	{
		$this->data = array();
		$this->data['renglones_comisiones'] = $this->controlador->get_lista_comisiones();
		$this->data['renglones_examenes'] = $this->controlador->get_lista_mesas();
		$this->data['renglones_actas_fc'] = $this->controlador->get_lista_actas_fc();
		
		//---- Configuración de filtro -----------------------------------------
		$this->add_var_js('url_filtrar', kernel::vinculador()->crear('ficha_alumno', 'index'));
		
		$botones = "<div class='clearfix'>
						<div class='pull-right'>".
							kernel::traductor()->trans('ficha_alumno.filtro.mostrar')
							.": <button id='btn-comisiones' name='btn-comisiones' class='btn btn-info js-btn-mostrar' >".kernel::traductor()->trans('comisiones')."</button>
							 <button id='btn-mesas' name='btn-mesas' class='btn btn-info js-btn-mostrar' >".kernel::traductor()->trans('mesas')."</button>".
							 "<button id='btn-actas_fc' name='btn-actas_fc' class='btn btn-info js-btn-mostrar' >".kernel::traductor()->trans('actas_fc')."</button>".
						"</div>
					</div>
					<hr/>";
		
		$this->add_var_js('botones_filtro', $botones);
		
		$this->add_var_js('url_autocomplete_filtro', kernel::vinculador()->crear('ficha_alumno', 'auto_alumno'));
		$this->add_mensaje_js('no_se_encontraron_alumnos', ucfirst(kernel::traductor()->trans('ficha_alumno.no_se_encontraron_alumnos')));
		
		
	}
}
?>