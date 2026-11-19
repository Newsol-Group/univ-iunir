<?php
/**
 *
 *
 * @package 	Operaciones Generales
 * @subpackage 	Videos
 *
 */
class ci_video extends guarani_pers_ci
{
	protected $s__video;
	
	function ini()
	{
		parent::ini();		
		$this->s__video = toba::memoria()->get_parametro('video');
	}
	
	function mostrar_video($ancho=600, $alto=400, $previsualizacion='')
	{
		$url_base = toba_recurso::url_proyecto().'/videos/';
		$url_js = $url_base.'jwplayer/';
		$url_video = $url_base.'videos/';
		
		return '
				<script type="text/javascript" src="'.$url_js.'jwplayer.js"></script>
				<div id="pelicula">Cargando el visualizador...</div>
				<script type="text/javascript">
					jwplayer("pelicula").setup({
						"flashplayer": "'.$url_js.'player.swf",
						"file": "'.$url_video.$this->s__video.'",
						"image": "'.$url_video.$previsualizacion.'",
						"height": "'.($alto + 24).'",
						"width": "'.$ancho.'",
						"controlbar": "bottom"
					});
				</script>
	   			';
	   	
	}
}

?>