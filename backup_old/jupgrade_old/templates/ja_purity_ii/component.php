<?php
/*
 * ------------------------------------------------------------------------
 * JA Purity II template for Joomla 2.5
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2011 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * ------------------------------------------------------------------------
*/

// No direct access.
defined('_JEXEC') or die;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" >
<head>
<jdoc:include type="head" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/general.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/plugins/system/jat3/jat3/base-themes/default/css/addons.css" type="text/css" media="all" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/plugins/system/jat3/jat3/base-themes/default/css/template.css" type="text/css" media="all" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template; ?>/css/template.css" type="text/css" media="all" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template; ?>/css/component.css" type="text/css" media="all" />
</head>
<body id="bd" class="bd-component">
<div id="container" class="wrap">
	<div class="main container-main clearfix">
		<div id="content">
			<div class="inner content-inner clearfix">
				<div id="ja-message">
					<jdoc:include type="message" />
				</div>
				<jdoc:include type="component" />
			</div>
		</div>
	</div>
</div>
</html>
</body>