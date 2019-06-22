//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class slack_hook_profile extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'settings' => 
  array (
    0 => 
    array (
      'selector' => '#elSettingsTabs > div.ipsColumns.ipsColumns_collapsePhone.ipsColumns_bothSpacing > div.ipsColumn.ipsColumn_wide > div.ipsSideMenu > ul.ipsSideMenu_list',
      'type' => 'add_inside_end',
      'content' => '<li>
	<a href="{url=\'app=slack&module=profile&controller=settings\'}" id="slack_notifications" class="ipsType_normal ipsSideMenu_item " title="{lang="overview"}" role="tab" aria-selected="">
		<i class="fa fa-slack"> </i>
		{lang="__app_slack"}
  	</a>
</li>',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */


}
