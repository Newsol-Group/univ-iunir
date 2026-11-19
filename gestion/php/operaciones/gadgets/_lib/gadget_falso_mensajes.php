<style type="text/css">
#c {
	text-align:left;
	margin-left:-35px;
	background:none
}

.titulo {
	position:relative;
	float:left;
	margin-top:-10px;
}

.lista-link:hover {
	text-decoration:none;
	font-weight:bold;
	color:#669900;
	font-weight:bold
}

.lista-link {
	text-decoration:none;
	font-weight:normal;
	color:#666666;
	font-weight:normal
}


</style>
<?php
class gadget_falso_mensajes extends toba_gadget {

	protected $sql;
	protected $url;
	protected $ul;
	protected $tc=array();
	protected $lp=array();
	protected $proyecto;
	protected $item;	
	

//----- Agregado colo.
	protected $icono = '';	
	function set_icono($icono) 
	{
		$this->icono = $icono;
	}

	function get_icono() 
	{
//		return $this->icono;
		if ($this->icono <> '') {
			return "<span class='titulo'><img src=img/" . $this->icono . "></span>";
		};
		return "";
	}
//----------

	function set_text_cols($tc) 
	{
		$this->tc = $tc;
	}

	function set_link_params($lp) 
	{
		$this->lp = $lp;		
	}
	
	function set_proyecto($proyecto) 
	{
		$this->proyecto = $proyecto;
	}

	function set_item($item) 
	{
		$this->item = $item;
	}
	
	function generar_html() 
	{
		toba::logger()->seccion('== Inicio de gadget interno '.$this->get_titulo().' ==','toba');
		
		echo "<div id='gadget-chrome-{$this->get_orden()}' class='gadgets-gadget-chrome'>";
		echo "<div id='gadgets-gadget-title-bar-{$this->get_orden()}' class='gadgets-gadget-title-bar'>";
//------ Agregado colo.
		echo "{$this->get_icono()}";
//		echo "<span class='titulo'><img src=img\\{$this->get_icono()}></span>";
//-------
		echo "<span style='vertical-align:top' class='gadgets-gadget-title' id='remote_iframe_{$this->get_orden()}_title'>{$this->get_titulo()}</span> | ";
		echo "<span class='gadgets-gadget-title-button-bar' onclick='var c=document.getElementById(\"gadget-content-{$this->get_orden()}\");if(c.style.display==\"none\") {c.style.display=\"block\";this.innerHTML=\"Minimizar\";} else {c.style.display=\"none\";this.innerHTML=\"Maximizar\";}' style='text-decoration: underline; cursor:pointer'>Minimizar</span>";
		if ($this->es_eliminable()) {
		echo "<img style='float:right; margin-right: 5px; margin-left: 5px; margin-top: 1px; margin-bottom: 1px; width: 15px; height: 15px' onClick='this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode)' src='".toba_recurso::imagen_toba('finalizar_sesion.gif', false)."'/>";
		}
		echo "</div>\n";
		echo "<div id='gadget-content-{$this->get_orden()}' class='gadgets-gadget-content'>";
//		echo "<div style='background-color:white; width: 250px; padding: 3px;'>";
		echo "<div style='width: 250px; padding: 3px;'>";
				
		echo "<table><tr><td><img src='img/advertencia.png' /></td><td>Mañana hay mesa de examen de <a href='#'>Algebra</a> y Uds e encuentra como presidente de dicha mesa</td></tr>";
		echo "<tr><td><img src='img/mensaje.png' /></td><td>Oficina de Alumnos - <a href='#'>Les comunicamos a todos los docentes que deben pasar a firmar las actas...</a></td></tr>";
		echo "<tr><td><img src='img/mensaje.png' /></td><td>Juan Gimenez - <img src='img/adjunto.png' /><a href='#'>Profesor, le adjuntamos el trabajo práctico que realizamos sobre el tema...</a></td></tr>";
		echo "<tr><td><img src='img/advertencia.png' /></td><td>Hay <a href='#'>3 actas abiertas</a> de mesas de examen que ocurrieron hace más de 7 días</td></tr>";
		echo "<tr><td><img src='img/mensaje.png' /></td><td>Juan Gimenez - <img src='img/adjunto.png' /><a href='#'>Profesor, le adjuntamos el trabajo práctico que realizamos sobre el tema...</a></td></tr></table>";

/*		echo "<p><img src='img/advertencia.png' />Mañana hay mesa de examen de <a href='#'>Algebra</a> y Uds e encuentra como presidente de dicha mesa</p>";
		echo "<p><img src='img/mensaje.png' />Oficina de Alumnos - <a href='#'>Les comunicamos a todos los docentes que deben pasar a firmar las actas...</a></p>";
		echo "<p><img src='img/mensaje.png' />Juan Gimenez - <img src='img/adjunto.png' /><a href='#'>Profesor, le adjuntamos el trabajo práctico que realizamos sobre el tema...</a></p>";
		echo "<p><img src='img/advertencia.png' />Hay <a href='#'>3 actas abiertas</a> de mesas de examen que ocurrieron hace más de 7 días</p>";
		echo "<p><img src='img/mensaje.png' />Juan Gimenez - <img src='img/adjunto.png' /><a href='#'>Profesor, le adjuntamos el trabajo práctico que realizamos sobre el tema...</a></p>";
*/
		echo "</div>\n";
		echo "</div>\n";
		echo "</div>\n";
		toba::logger()->seccion('== Fin de gadget interno '.$this->get_titulo().' ==','toba');
	}
}
?>