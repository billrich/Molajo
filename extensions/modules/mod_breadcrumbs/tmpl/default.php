<?php
/**
 * @version		$Id: default.php 19022 2010-10-02 14:51:33Z infograf768 $
 * @package		Joomla.Site
 * @subpackage	mod_breadcrumbs
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('JPATH_PLATFORM') or die;
?>

<div class="breadcrumbs<?php echo $params->get('layout_class_suffix'); ?>">
<?php if ($params->get('showHere', 1))
	{
		echo JText::_('MOD_BREADCRUMBS_HERE');
	}
?>
<?php for ($i = 0; $i < $count; $i ++) :

	// If not the last item in the breadcrumbs add the separator
	if ($i < $count -1) {
		if (!empty($list[$i]->link)) {
			echo '<a href="'.$list[$i]->link.'" class="pathway">'.$list[$i]->name.'</a>';
		} else {
		    echo '<span>';
			echo $list[$i]->name;
			  echo '</span>';
		}
		if($i < $count -2){
			echo ' '.$separator.' ';
		}
	}  else if ($params->get('showLast', 1)) { // when $i == $count -1 and 'showLast' is true
		if($i > 0){
			echo ' '.$separator.' ';
		}
		 echo '<span>';
		echo $list[$i]->name;
		  echo '</span>';
	}
endfor; ?>
</div>