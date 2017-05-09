<?php
defined( 'MVC_FRAMEWORK') or die;

if(AdminPackagesComponent::paddedVersion($current) > AdminPackagesComponent::paddedVersion('2.6.3.2')) return;

$Model = new S2Model;

$query = "
	SELECT
		value
	FROM
		#__jreviews_config
	WHERE
		id = 'list_display_type'
";

$layout_id = $Model->query($query,'loadResult');

switch($layout_id)
{
	case 0:
	    $layout = 'tableview';
	    break;
	case 1:
	    $layout = 'blogview';
	    break;
	case 2:
	    $layout = 'thumbview';
	    break;
	case 3:
	    $layout = 'masonry';
	    break;
	default:
	    $layout = 'blogview';
	    break;
}

$query = "
	SELECT
		value
	FROM
		#__jreviews_config
	WHERE
		id = 'list_predefined_layout'
";

$list_predefined_layout = $Model->query($query,'loadResult');

if($list_predefined_layout)
{
	$list_predefined_layout = json_decode($list_predefined_layout,true);

	$list_predefined_layout[1]['layout'] = $layout;

	$query = "
		UPDATE
			#__jreviews_config
		SET
			value = '" . json_encode($list_predefined_layout) . "'
		WHERE
			id = 'list_predefined_layout'
	";

	$Model->query($query);
}
else {

	$list_predefined_layout = array(
			1=>array('layout'=>$layout,'suffix'=>'','icon'=>'jrIconList'),
			2=>array('layout'=>'','suffix'=>'','icon'=>'jrIconTable'),
			3=>array('layout'=>'','suffix'=>'','icon'=>'jrIconThumbs')
		);

	$query = "
		INSERT INTO
			#__jreviews_config
		(id,value)
		VALUES
			('list_predefined_layout','" . json_encode($list_predefined_layout) . "')
	";

	$Model->query($query);
}

$query = "
	DELETE FROM
		#__jreviews_config
	WHERE
		id = 'list_display_type'
";

$Model->query($query);