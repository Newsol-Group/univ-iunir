<?php
namespace siu\operaciones\ficha_alumno;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_ficha extends vista_g3w2
{
	const reporte_BITACORA = 'bitacora';
	
	// Reportes disponibles
	static $REPORTES = array(
		'datos_personales',
		self::reporte_BITACORA
	);
	
	protected $rep_activo;
	protected $id_rep_activo;	
	
	protected $campos_pagelet;
	protected $campos_faltantes;
	
    function ini()
    {
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('ficha_alumno'));
		
		$this->set_template('template_vista_ficha');
		
		$clase = 'operaciones\\ficha_alumno\\pagelet_lista_reportes';
		$pl = kernel::localizador()->instanciar($clase, 'lista_reportes');
		$this->add_pagelet($pl);
	}
	
	function get_accion_volver_ficha()
	{
		return $this->controlador->get_accion_volver_ficha();
	}
	
	function get_datos_alumno()
	{
		return $this->controlador->encabezado_persona();
	}
	
	function get_reportes()
	{
		$salida = self::$REPORTES;
		if (!$this->controlador->tiene_derechos_bitacora()) {
			$salida = array_diff($salida, array(self::reporte_BITACORA));
		}
		return $salida;
	}
	
	function existe_form($id)
	{
		return in_array($id, self::$REPORTES);
	}
	
	function get_id_rep_activo()
	{
		return $this->id_rep_activo;
	}
	
	function set_rep_activo($pagelet)
	{
		$this->rep_activo = $pagelet;
		$this->id_rep_activo = $pagelet->get_id();
		$this->add_pagelet($pagelet, 0, 'reporte');
	}
	
    function pagelet($id)
    {
		if (isset($this->pagelets[$id])) {
			return parent::pagelet($id);
		}
        
		if ($this->existe_form($id)) {
			$clase = "operaciones\\ficha_alumno\\pagelet_$id";
			return kernel::localizador()->instanciar($clase, $id);
		}
		
		throw new \siu\errores\error_guarani_seguridad("El pagelet '$id' no existe");
    }
	
}
?>