<?php
namespace siu\operaciones\actas_cursadas;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\guarani;

class pagelet_acta extends pagelet
{
	public $actividad_elemento;
	public $periodo_lectivo;
	public $acta;
	
	function get_nombre()
	{
		return 'acta';
	}

	function prepare()
	{
		$parametros = array(
					'actividad' => empty($this->actividad_elemento) ? null : $this->actividad_elemento, 
					'per_lec' => empty($this->periodo_lectivo) ? null : $this->periodo_lectivo,
				);
		$this->data['si'] = \comunes::si;
		$this->data['acta_estado_invalidado'] = \acta::detalle_estado_invalidado;
		$this->data['url_volver'] = kernel::vinculador()->crear('actas_cursadas', 'index', $parametros);
		$parametros['acta'] = $this->acta;
		$this->add_var_js('url_generar_pdf', kernel::vinculador()->crear('actas_cursadas', 'generar_pdf_acta', $parametros));
		$this->add_var_js('url_generar_xls', kernel::vinculador()->crear('actas_cursadas', 'generar_xls_acta', $parametros));
		
		$this->add_var_js('ocultar_encabezado', ucfirst(kernel::traductor()->trans('actas_cursadas.ocultar_encabezado')));
		$this->add_var_js('mostrar_encabezado', ucfirst(kernel::traductor()->trans('actas_cursadas.mostrar_encabezado')));
	}
}