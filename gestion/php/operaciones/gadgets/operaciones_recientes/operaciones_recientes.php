<style type="text/css">
#op {
	text-align:left;
	margin-left:-35px;
	background:none
}

#lnk:hover {
	text-decoration:none;
	font-weight:bold;
	color:#336600;
	font-weight:bold
}

#lnk {
	text-decoration:none;
	font-weight:normal;
	color:#666666;
	font-weight:normal
}


</style>
<?php
	$items = toba::consulta_php('co_operaciones')->get_operaciones_recientes();
	$proyecto = toba_proyecto::get_id();
	echo '<div style=\'background:none\'> <ul style=\'background:none\'>';
	foreach ($items as $item) {
		echo '<p id=op>'.
			 (!is_null($item['imagen'])?toba_recurso::imagen(toba_recurso::imagen_de_origen($item['imagen'], $item['origen'])):toba_recurso::imagen_proyecto('item.png', true, 15)).
			 ' <a id=lnk href="#" title="'.$item['nombre'].'" onclick=\'return toba.ir_a_operacion("',$proyecto,'", "',$item['item'],'", false)','\'>',((strlen($item['nombre'])>35)?substr($item['nombre'],0,35).'...':$item['nombre']),'</a></p>';
	}
	echo '</ul></div>';
?>