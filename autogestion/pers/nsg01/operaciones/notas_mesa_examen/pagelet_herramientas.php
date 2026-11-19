<?php
namespace nsg01\operaciones\notas_mesa_examen;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_herramientas extends pagelet
{
	function get_nombre()
	{
		return 'herramientas';
	}

	function prepare()
	{
		$this->data = array();

		$this->data['campos_autocompletar'] = $this->controlador->modelo()->info__opciones_autocompletar_campo($this->controlador->llamado_mesa_params);
		$this->data['resultado_ausente'] = \escalas_notas::resultado_ausente;
		$this->data['resultado_aprobado'] = \escalas_notas::resultado_aprobado;

		$encabezado = $this->controlador->get_encabezado();

		$this->add_var_js('fecha_inicio', $encabezado['fecha_formato_visual']);
		$this->add_var_js('fecha_fin', date('d/m/Y'));

		$this->add_mensaje_js('nota_invalida', kernel::traductor()->trans('nota_invalida'));

		$msj_fecha_invalida = kernel::traductor()->trans('carga_notas_mesa_examen.fecha_invalida', array('%1%' => $encabezado['fecha_formato_visual'], '%2%' => date('d/m/Y')));
		$this->add_mensaje_js('fecha_invalida', $msj_fecha_invalida);
	}

}
?>
