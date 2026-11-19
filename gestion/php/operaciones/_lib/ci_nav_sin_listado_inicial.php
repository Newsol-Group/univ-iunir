<?php 
/**
 *	Se extiende el ci de navegación genérico para modelar un comportamiento diferente en el filtro y cuadro inicial.
 *	El mismo se utilizara para operaciones con mucha carga de registros, para las cuales es conveniente que en el pedido inicial
 *	no se realice la consulta, si no que se haga recién cuando el usuario presione el botón filtrar (con algún campo completado o
 *	con el filtro vacío). También se implementa un comportamiento análogo ante el evento Limpiar del filtro.
*/

class ci_nav_sin_listado_inicial extends ci_navegacion 
{
	//------ Cuadro ----------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial();
	}
	
}

?>