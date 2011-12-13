<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODx Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('settings'))
{
	$e->setError(3);
	$e->dumpError();
}

// check to see the edit settings page isn't locked
$sql = "SELECT internalKey, username FROM $dbase.`".$table_prefix."active_users` WHERE $dbase.`".$table_prefix."active_users`.action=17";
$rs = mysql_query($sql);
$limit = mysql_num_rows($rs);
if($limit>1) {
	for ($i=0;$i<$limit;$i++)
	{
		$lock = mysql_fetch_assoc($rs);
		if($lock['internalKey']!=$modx->getLoginUserID())
		{
			$msg = sprintf($_lang["lock_settings_msg"],$lock['username']);
			$e->setError(5, $msg);
			$e->dumpError();
		}
	}
}
// end check for lock

// reload system settings from the database.
// this will prevent user-defined settings from being saved as system setting
$settings = array();
$sql = "SELECT setting_name, setting_value FROM $dbase.`".$table_prefix."system_settings`";
$rs = mysql_query($sql);
$number_of_settings = mysql_num_rows($rs);
while ($row = mysql_fetch_assoc($rs)) $settings[$row['setting_name']] = $row['setting_value'];
extract($settings, EXTR_OVERWRITE);

$displayStyle = ($_SESSION['browser']!=='ie') ? 'table-row' : 'block' ;

// load languages and keys
$lang_keys = array();
$dir = dir("includes/lang");
while ($file = $dir->read())
{
	if(strpos($file, ".inc.php")>0)
	{
		$endpos = strpos ($file, ".");
		$languagename = substr($file, 0, $endpos);
		$lang_keys[$languagename] = get_lang_keys($file);
	}
}
$dir->close();

$isDefaultUnavailableMsg = $site_unavailable_message == $_lang['siteunavailable_message_default'];
$isDefaultUnavailableMsgJs = $isDefaultUnavailableMsg ? 'true' : 'false';
$site_unavailable_message_view = isset($site_unavailable_message) ? $site_unavailable_message : $_lang['siteunavailable_message_default'];

/* check the file paths */
$settings['filemanager_path'] = $filemanager_path = trim($settings['filemanager_path']) == '' ? MODX_BASE_PATH : $settings['filemanager_path'];
$settings['rb_base_dir'] = $rb_base_dir = trim($settings['rb_base_dir']) == '' ? MODX_BASE_PATH.'assets/' : $settings['rb_base_dir'];
$settings['rb_base_url'] =  $rb_base_url = trim($settings['rb_base_url']) == '' ? 'assets/' : $settings['rb_base_url'];
?>

<script type="text/javascript">

function showHide(what, onoff)
{
	var all = document.getElementsByTagName( "*" );
	var l = all.length;
	var buttonRe = what;
	var id, el, stylevar;

	if(onoff==1)
	{
		stylevar = "<?php echo $displayStyle; ?>";
	}
	else
	{
		stylevar = "none";
	}
	for ( var i = 0; i < l; i++ )
	{
		el = all[i]
		id = el.id;
		if ( id == "" ) continue;
		if (buttonRe.test(id))
		{
			el.style.display = stylevar;
		}
	}
};

function addContentType()
{
	var i,o,exists=false;
	var txt = document.settings.txt_custom_contenttype;
	var lst = document.settings.lst_custom_contenttype;
	for(i=0;i<lst.options.length;i++)
	{
		if(lst.options[i].value==txt.value) {
			exists=true;
			break;
		}
	}
	if (!exists)
	{
		o = new Option(txt.value,txt.value);
		lst.options[lst.options.length]= o;
		updateContentType();
	}
	txt.value='';
}
function removeContentType()
{
	var i;
	var lst = document.settings.lst_custom_contenttype;
	for(i=0;i<lst.options.length;i++)
	{
		if(lst.options[i].selected)
		{
			lst.remove(i);
			break;
		}
	}
	updateContentType();
}
function updateContentType()
{
	var i,o,ol=[];
	var lst = document.settings.lst_custom_contenttype;
	var ct = document.settings.custom_contenttype;
	while(lst.options.length)
	{
		ol[ol.length] = lst.options[0].value;
		lst.options[0]= null;
	}
	if(ol.sort) ol.sort();
	ct.value = ol.join(",");
	for(i=0;i<ol.length;i++)
	{
		o = new Option(ol[i],ol[i]);
		lst.options[lst.options.length]= o;
	}
	documentDirty = true;
}
/**
 * @param element el were language selection comes from
 * @param string lkey language key to look up
 * @param id elupd html element to update with results
 * @param string default_str default value of string for loaded manager language - allows some level of confirmation of change from default
 */
function confirmLangChange(el, lkey, elupd)
{
	lang_current = document.getElementById(elupd).value;
	lang_default = document.getElementById(lkey+'_hidden').value;
	changed = lang_current != lang_default;
	proceed = true;
	if(changed)
	{
		proceed = confirm('<?php echo $_lang['confirm_setting_language_change']; ?>');
	}
	if(proceed)
	{
		//document.getElementById(elupd).value = '';
		lang = el.options[el.selectedIndex].value;
		var myAjax = new Ajax('index.php?a=118',
		{
			method: 'post',
			data: 'action=get&lang='+lang+'&key='+lkey
		}).request();
		myAjax.addEvent('onComplete', function(resp)
		{
			document.getElementById(elupd).value = resp;
		});
	}
}
</script>
<form name="settings" action="index.php?a=30" method="post" enctype="multipart/form-data">
	<h1><?php echo $_lang['settings_title']; ?></h1>
	<div id="actions">
		<ul class="actionButtons">
			<li id="Button1">
				<a href="#" onclick="documentDirty=false; document.settings.submit();">
					<img src="<?php echo $_style["icons_save"]?>" /> <?php echo $_lang['save']; ?>
				</a>
			</li>
			<li id="Button5">
				<a href="#" onclick="documentDirty=false;document.location.href='index.php?a=2';">
					<img src="<?php echo $_style["icons_cancel"]?>" /> <?php echo $_lang['cancel']; ?>
				</a>
			</li>
		</ul>
	</div>
<div style="margin: 0 10px 0 20px">
	<input type="hidden" name="site_id" value="<?php echo $site_id; ?>" />
	<input type="hidden" name="settings_version" value="<?php echo $modx_version; ?>" />
	<!-- this field is used to check site settings have been entered/ updated after install or upgrade -->
<?php
	if(!isset($settings_version) || $settings_version!=$modx_version)
	{
		include(MODX_MANAGER_PATH.'includes/locale/' . $manager_language . '/system_settings.php');
	?>
	<div class='sectionBody'><p><?php echo $_lang['settings_after_install']; ?></p></div>
<?php
	}
?>
	<script type="text/javascript" src="media/script/tabpane.js"></script>
	<div class="tab-pane" id="settingsPane">
	<script type="text/javascript">
		tpSettings = new WebFXTabPane( document.getElementById( "settingsPane" ), <?php echo $modx->config['remember_last_tab'] == 0 ? 'false' : 'true'; ?> );
	</script>
<!-- Site Settings -->
<div class="tab-page" id="tabPage2">
<h2 class="tab"><?php echo $_lang["settings_site"] ?></h2>
<script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage2" ) );</script>
<style type="text/css">
	table.settings {border-collapse:collapse;width:100%;}
	table.settings tr {border-bottom:1px dotted #ccc;}
	table.settings th {font-size:inherit;vertical-align:top;text-align:left;}
	table.settings th,table.settings td {padding:5px;}
	table.settings td input[type=text] {width:250px;}
</style>
<table class="settings">
<tr>
	<th><?php echo $_lang["sitename_title"] ?></th>
	<td >
		<input onchange="documentDirty=true;" type="text" maxlength="255" style="width:200px;" name="site_name" value="<?php echo $site_name; ?>" /><br />
		<?php echo $_lang["sitename_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["language_title"]?></th>
	<td>
		<select name="manager_language" size="1" class="inputBox" onchange="documentDirty=true;">
		<?php echo get_lang_options(null, $manager_language);?>
		</select><br />
		<?php echo $_lang["language_message"]?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["charset_title"]?></th>
	<td>
		<select name="modx_charset" size="1" class="inputBox" style="width:250px;" onchange="documentDirty=true;">
		<?php include "charsets.php"; ?>
		</select><br />
		<?php echo $_lang["charset_message"]?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["xhtml_urls_title"] ?></th>
	<td>
		<?php echo form_radio('xhtml_urls','1',$xhtml_urls=='1');?>
		<?php echo $_lang["yes"]?><br />
		<?php echo form_radio('xhtml_urls','0',$xhtml_urls=='0');?>
		<?php echo $_lang["no"]?><br />
		<?php echo $_lang["xhtml_urls_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["sitestart_title"] ?></th>
	<td>
		<input onchange="documentDirty=true;" type="text" maxlength="10" size="5" name="site_start" value="<?php echo $site_start; ?>" /><br />
		<?php echo $_lang["sitestart_message"] ?></td>
</tr>
<tr>
	<th><?php echo $_lang["errorpage_title"] ?></th>
	<td>
		<input onchange="documentDirty=true;" type="text" maxlength="10" size="5" name="error_page" value="<?php echo $error_page; ?>" /><br />
		<?php echo $_lang["errorpage_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["unauthorizedpage_title"] ?></th>
	<td>
		<input onchange="documentDirty=true;" type="text" maxlength="10" size="5" name="unauthorized_page" value="<?php echo $unauthorized_page; ?>" /><br />
		<?php echo $_lang["unauthorizedpage_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["sitestatus_title"] ?></th>
	<td>
		<?php echo form_radio('site_status','1',$site_status=='1');?>
		<?php echo $_lang["online"]?><br />
		<?php echo form_radio('site_status','0',$site_status=='0');?>
		<?php echo $_lang["offline"]?><br />
		<?php echo $_lang["sitestatus_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["siteunavailable_page_title"] ?></th>
	<td>
		<input onchange="documentDirty=true;" name="site_unavailable_page" type="text" maxlength="10" size="5" value="<?php echo $site_unavailable_page; ?>" /><br />
		<?php echo $_lang["siteunavailable_page_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["siteunavailable_title"] ?><br />
	<p>
		<?php echo $_lang["update_settings_from_language"]; ?>
	</p>
		<select name="reload_site_unavailable" id="reload_site_unavailable_select" onchange="confirmLangChange(this, 'siteunavailable_message_default', 'site_unavailable_message_textarea');">
			<?php echo get_lang_options('siteunavailable_message_default');?>
		</select>
	</th>
	<td>
		<textarea name="site_unavailable_message" id="site_unavailable_message_textarea" style="width:100%; height: 120px;"><?php echo $site_unavailable_message_view; ?></textarea>
		<input type="hidden" name="siteunavailable_message_default" id="siteunavailable_message_default_hidden" value="<?php echo addslashes($_lang['siteunavailable_message_default']);?>" /><br />
		<?php echo $_lang['siteunavailable_message'];?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["track_visitors_title"] ?></th>
	<td>
		<?php echo form_radio('track_visitors','1',$track_visitors=='1');?>
		<?php echo $_lang["yes"]?><br />
		<?php echo form_radio('track_visitors','0',$track_visitors=='0');?>
		<?php echo $_lang["no"]?><br />
		<?php echo $_lang["track_visitors_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["top_howmany_title"] ?></th>
	<td>
		<input onchange="documentDirty=true;" type="text" maxlength="50" size="5" name="top_howmany" value="<?php echo $top_howmany; ?>" /><br />
		<?php echo $_lang["top_howmany_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["defaulttemplate_logic_title"];?></th>
	<td>
		<p>
		<?php echo $_lang["defaulttemplate_logic_general_message"];?></p>
		<?php echo form_radio('auto_template_logic','system', $auto_template_logic == 'system');?>
		<?php echo $_lang["defaulttemplate_logic_system_message"]; ?><br />
		<?php echo form_radio('auto_template_logic','parent', $auto_template_logic == 'parent');?>
		<?php echo $_lang["defaulttemplate_logic_parent_message"]; ?><br />
		<?php echo form_radio('auto_template_logic','sibling',$auto_template_logic == 'sibling');?>
		<?php echo $_lang["defaulttemplate_logic_sibling_message"]; ?><br />
	</td>
</tr>
<tr>
	<th><?php echo $_lang["defaulttemplate_title"] ?></th>
	<td>
<?php
	$sql = 'SELECT t.templatename, t.id, c.category FROM '.$table_prefix.'site_templates t LEFT JOIN '.$table_prefix.'categories c ON t.category = c.id ORDER BY c.category, t.templatename ASC';
	$rs = mysql_query($sql);
?>
		<select name="default_template" class="inputBox" onchange="documentDirty=true;wrap=document.getElementById('template_reset_options_wrapper');if(this.options[this.selectedIndex].value != '<?php echo $default_template;?>'){wrap.style.display='block';}else{wrap.style.display='none';}" style="width:150px">
<?php
	$currentCategory = '';
	while ($row = mysql_fetch_assoc($rs))
	{
		$thisCategory = $row['category'];
		if($thisCategory == null)
		{
			$thisCategory = $_lang["no_category"];
		}
		if($thisCategory != $currentCategory)
		{
			if($closeOptGroup)
			{
				echo "\t\t\t\t\t</optgroup>\n";
			}
			echo "\t\t\t\t\t<optgroup label=\"$thisCategory\">\n";
			$closeOptGroup = true;
		}
		else
		{
			$closeOptGroup = false;
		}
		$selectedtext = $row['id'] == $default_template ? ' selected="selected"' : '';
		if ($selectedtext)
		{
			$oldTmpId = $row['id'];
			$oldTmpName = $row['templatename'];
		}
		echo "\t\t\t\t\t".'<option value="'.$row['id'].'"'.$selectedtext.'>'.$row['templatename']."</option>\n";
		$currentCategory = $thisCategory;
	}
	if($thisCategory != '')
	{
		echo "\t\t\t\t\t</optgroup>\n";
	}
?>
		</select><br />
		<div id="template_reset_options_wrapper" style="display:none;">
			<?php echo form_radio('reset_template','1');?> <?php echo $_lang["template_reset_all"]; ?><br />
			<?php echo form_radio('reset_template','2');?> <?php echo sprintf($_lang["template_reset_specific"],$oldTmpName); ?>
		</div>
		<input type="hidden" name="old_template" value="<?php echo $oldTmpId; ?>" /><br />
		<?php echo $_lang["defaulttemplate_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["defaultpublish_title"] ?></th>
	<td>
		<?php echo form_radio('publish_default','1',$publish_default=='1');?>
		<?php echo $_lang["yes"]?><br />
		<?php echo form_radio('publish_default','0',$publish_default=='0');?>
		<?php echo $_lang["no"]?><br />
		<?php echo $_lang["defaultpublish_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["defaultcache_title"] ?></th>
	<td>
		<?php echo form_radio('cache_default','1',$cache_default=='1');?>
		<?php echo $_lang["yes"]?><br />
		<?php echo form_radio('cache_default','0',$cache_default=='0');?>
		<?php echo $_lang["no"]?><br />
		<?php echo $_lang["defaultcache_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["defaultsearch_title"] ?></th>
	<td>
		<?php echo form_radio('search_default','1',$search_default=='1');?>
		<?php echo $_lang["yes"]?><br />
		<?php echo form_radio('search_default','0',$search_default=='0');?>
		<?php echo $_lang["no"]?><br />
		<?php echo $_lang["defaultsearch_message"] ?></td>
</tr>
<tr> 
	<th><?php echo $_lang["defaultmenuindex_title"] ?></th>
	<td>
		<?php echo form_radio('auto_menuindex','1',$auto_menuindex=='1');?>
		<?php echo $_lang["yes"]?><br /> 
		<?php echo form_radio('auto_menuindex','0',$auto_menuindex=='0');?>
		<?php echo $_lang["no"]?><br />
		<?php echo $_lang["defaultmenuindex_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["custom_contenttype_title"] ?></th>
	<td>
		<input name="txt_custom_contenttype" type="text" maxlength="100" style="width:200px;" value="" /> <input type="button" value="<?php echo $_lang["add"]; ?>" onclick='addContentType()' /><br />
		<table>
			<tr>
			<td valign="top">
			<select name="lst_custom_contenttype" style="width:200px;" size="5">
<?php
	$ct = explode(",",$custom_contenttype);
	for($i=0;$i<count($ct);$i++)
	{
		echo "<option value=\"".$ct[$i]."\">".$ct[$i]."</option>";
	}
?>
			</select>
			<input name="custom_contenttype" type="hidden" value="<?php echo $custom_contenttype; ?>" />
			</td>
			<td valign="top">
				&nbsp;<input name="removecontenttype" type="button" value="<?php echo $_lang["remove"]; ?>" onclick='removeContentType()' />
			</td>
			</tr>
		</table><br />
		<?php echo $_lang["custom_contenttype_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["serveroffset_title"] ?></th>
	<td>
		<select name="server_offset_time" size="1" class="inputBox">
<?php
	for($i=-24; $i<25; $i++)
	{
		$seconds = $i*60*60;
		$selectedtext = $seconds==$server_offset_time ? "selected='selected'" : "" ;
?>
		<option value="<?php echo $seconds; ?>" <?php echo $selectedtext; ?>><?php echo $i; ?></option>
<?php
	}
?>
		</select><br />
		<?php printf($_lang["serveroffset_message"], strftime('%H:%M:%S', time()), strftime('%H:%M:%S', time()+$server_offset_time)); ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["server_protocol_title"] ?></th>
	<td>
		<?php echo form_radio('server_protocol','http', $server_protocol=='http');?>
		<?php echo $_lang["server_protocol_http"]?><br />
		<?php echo form_radio('server_protocol','https',$server_protocol=='https');?>
		<?php echo $_lang["server_protocol_https"]?><br />
		<?php echo $_lang["server_protocol_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["validate_referer_title"] ?></th>
	<td>
		<?php echo form_radio('validate_referer','1', $validate_referer=='1');?>
		<?php echo $_lang["yes"]?><br />
		<?php echo form_radio('validate_referer','0', $validate_referer=='0');?>
		<?php echo $_lang["no"]?><br />
		<?php echo $_lang["validate_referer_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["rss_url_news_title"] ?></th>
	<td>
		<input onchange="documentDirty=true;" type="text" maxlength='350' style="width:350px;" name="rss_url_news" value="<?php echo $rss_url_news; ?>" /><br />
		<?php echo $_lang["rss_url_news_message"] ?>
	</td>
</tr>
<tr>
	<th><?php echo $_lang["rss_url_security_title"] ?></th>
	<td>
		<input onchange="documentDirty=true;" type="text" maxlength='350' style="width:350px;" name="rss_url_security" value="<?php echo $rss_url_security; ?>" /><br />
		<?php echo $_lang["rss_url_security_message"] ?>
	</td>
</tr>
<tr class="row1" style="border-bottom:none;">
	<td colspan="2">
<?php
	// invoke OnSiteSettingsRender event
	$evtOut = $modx->invokeEvent("OnSiteSettingsRender");
	if(is_array($evtOut)) echo implode("",$evtOut);
?>
	</td>
</tr>
</table>
</div>

<!-- Friendly URL settings  -->
<div class="tab-page" id="tabPage3">
<h2 class="tab"><?php echo $_lang["settings_furls"] ?></h2>
<script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage3" ) );</script>
<table class="settings">
<tr>
	<th><?php echo $_lang["friendlyurls_title"] ?></th>
	<td>
		<?php echo form_radio('friendly_urls','1', $friendly_urls=='1','onclick="showHide(/furlRow/, 1);"');?>
		<?php echo $_lang["yes"]?><br />
		<?php echo form_radio('friendly_urls','0', $friendly_urls=='0','onclick="showHide(/furlRow/, 0);"');?>
		<?php echo $_lang["no"]?><br />
		<?php echo $_lang["friendlyurls_message"] ?>
	</td>
</tr>
<tr id="furlRow1" class="row1" style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
	<th><?php echo $_lang["friendlyurlsprefix_title"] ?></th>
	<td>
		<input onchange="documentDirty=true;" type="text" maxlength="50" style="width:200px;" name="friendly_url_prefix" value="<?php echo $friendly_url_prefix; ?>" /><br />
		<?php echo $_lang["friendlyurlsprefix_message"] ?></td>
</tr>
<tr id='furlRow4' class="row1" style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
	<th><?php echo $_lang["friendlyurlsuffix_title"] ?></th>
	<td>
		<input onchange="documentDirty=true;" type="text" maxlength="50" style="width:200px;" name="friendly_url_suffix" value="<?php echo $friendly_url_suffix; ?>" /><br />
		<?php echo $_lang["friendlyurlsuffix_message"] ?></td>
</tr>
<tr id='furlRow7' class="row1" style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["friendly_alias_title"] ?></th>
<td>
	<?php echo form_radio('friendly_alias_urls','1', $friendly_alias_urls=='1');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('friendly_alias_urls','0', $friendly_alias_urls=='0');?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["friendly_alias_message"] ?></td>
</tr>
<tr id='furlRow10' class="row1" style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["use_alias_path_title"] ?></th>
<td>
	<?php echo form_radio('use_alias_path','1', $use_alias_path=='1');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('use_alias_path','0', $use_alias_path=='0');?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["use_alias_path_message"] ?>
</td>
</tr>
<tr id='furlRow16' class='row2' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["duplicate_alias_title"] ?></th>
<td>
	<?php echo form_radio('allow_duplicate_alias','1', $allow_duplicate_alias=='1');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('allow_duplicate_alias','0', $allow_duplicate_alias=='0');?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["duplicate_alias_message"] ?>
</td>
</tr>
<tr id='furlRow13' class="row1" style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["automatic_alias_title"] ?></th>
<td>
	<?php echo form_radio('automatic_alias','1', $automatic_alias=='1');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('automatic_alias','0', $automatic_alias=='0');?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["automatic_alias_message"] ?>
</td>
</tr>
<tr class="row1" style="border-bottom:none;">
<td colspan="2">
<?php
// invoke OnFriendlyURLSettingsRender event
$evtOut = $modx->invokeEvent("OnFriendlyURLSettingsRender");
if(is_array($evtOut)) echo implode("",$evtOut);
?>
</td>
</tr>
</table>
</div>

<!-- User settings -->
<div class="tab-page" id="tabPage4">
<h2 class="tab"><?php echo $_lang["settings_users"] ?></h2>
<script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage4" ) );</script>
<table class="settings">
<tr>
	<th><?php echo $_lang["udperms_title"] ?></th>
	<td>
	<?php echo form_radio('use_udperms','1', $use_udperms=='1','onclick="showHide(/udPerms/, 1);"');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('use_udperms','0', $use_udperms=='0','onclick="showHide(/udPerms/, 0);"');?>
	<?php echo $_lang["no"]?><br />
<?php echo $_lang["udperms_message"] ?></td>
</tr>
<tr id='udPermsRow1' class="row1" style="display: <?php echo $use_udperms==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["udperms_allowroot_title"] ?></th>
<td>
	<?php echo form_radio('udperms_allowroot','1', $udperms_allowroot=='1');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('udperms_allowroot','0', $udperms_allowroot=='0');?>
	<?php echo $_lang["no"]?> <br />
	<?php echo $_lang["udperms_allowroot_message"] ?>
</td>
</tr>
<tr>
<th><?php echo $_lang["failed_login_title"] ?></th>
<td><input type="text" name="failed_login_attempts" style="width:50px" value="<?php echo $failed_login_attempts; ?>" /><br />
<?php echo $_lang["failed_login_message"] ?></td>
</tr>
<tr>
<th><?php echo $_lang["blocked_minutes_title"] ?></th>
<td><input type="text" name="blocked_minutes" style="width:100px" value="<?php echo $blocked_minutes; ?>" /><br />
<?php echo $_lang["blocked_minutes_message"] ?></td>
</tr>
<?php
// Check for GD before allowing captcha to be enabled
$gdAvailable = extension_loaded('gd');
?>
<tr>
<th><?php echo $_lang["captcha_title"] ?></th>
<td>
	<?php echo form_radio('use_captcha','1', $use_captcha=='1' && $gdAvailable,'',!$gdAvailable);?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('use_captcha','0', $use_captcha=='0' || !$gdAvailable,'',!$gdAvailable);?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["captcha_message"] ?>
</td>
</tr>
<tr>
<th><?php echo $_lang["captcha_words_title"];?>
<br />
<p><?php echo $_lang["update_settings_from_language"]; ?></p>
<select name="reload_captcha_words" id="reload_captcha_words_select" onchange="confirmLangChange(this, 'captcha_words_default', 'captcha_words_input');">
<?php echo get_lang_options('captcha_words_default');?>
</select>
</th>
<td><input type="text" id="captcha_words_input" name="captcha_words" style="width:250px" value="<?php echo $captcha_words; ?>" />
<input type="hidden" name="captcha_words_default" id="captcha_words_default_hidden" value="<?php echo addslashes($_lang["captcha_words_default"]);?>" /><br />
<?php echo $_lang["captcha_words_message"] ?></td>
</tr>
<tr>
<th><?php echo $_lang["emailsender_title"] ?></th>
<td ><input onchange="documentDirty=true;" type="text" maxlength="255" name="emailsender" value="<?php echo $emailsender; ?>" /><br />
<?php echo $_lang["emailsender_message"] ?></td>
</tr>
<tr>
<th><?php echo $_lang["emailsubject_title"];?>
<br />
<p><?php echo $_lang["update_settings_from_language"]; ?></p>
<select name="reload_emailsubject" id="reload_emailsubject_select" onchange="confirmLangChange(this, 'emailsubject_default', 'emailsubject_field');">
<?php echo get_lang_options('emailsubject_default');?>
</select>
</th>
<td ><input id="emailsubject_field" name="emailsubject" onchange="documentDirty=true;" type="text" maxlength="255" value="<?php echo $emailsubject; ?>" />
<input type="hidden" name="emailsubject_default" id="emailsubject_default_hidden" value="<?php echo addslashes($_lang['emailsubject_default']);?>" /><br />
<?php echo $_lang["emailsubject_message"] ?></td>
</tr>
<tr>
<td nowrap class="warning" valign="top"><b><?php echo $_lang["signupemail_title"] ?></b>
<br />
<p><?php echo $_lang["update_settings_from_language"]; ?></p>
<select name="reload_signupemail_message" id="reload_signupemail_message_select" onchange="confirmLangChange(this, 'system_email_signup', 'signupemail_message_textarea');">
<?php echo get_lang_options('system_email_signup');?>
</select>
</td>
<td><textarea id="signupemail_message_textarea" name="signupemail_message" style="width:100%; height: 120px;"><?php echo $signupemail_message;?></textarea>
<input type="hidden" name="system_email_signup_default" id="system_email_signup_hidden" value="<?php echo addslashes($_lang['system_email_signup']);?>" /><br />
<?php echo $_lang["signupemail_message"] ?></td>
</tr>
<tr>
<td nowrap class="warning" valign="top"><b><?php echo $_lang["websignupemail_title"] ?></b>
<br />
<p><?php echo $_lang["update_settings_from_language"]; ?></p>
<select name="reload_websignupemail_message" id="reload_websignupemail_message_select" onchange="confirmLangChange(this, 'system_email_websignup', 'websignupemail_message_textarea');">
<?php echo get_lang_options('system_email_websignup');?>
</select>
</td>
<td><textarea id="websignupemail_message_textarea" name="websignupemail_message" style="width:100%; height: 120px;"><?php echo $websignupemail_message;?></textarea>
<input type="hidden" name="system_email_websignup_default" id="system_email_websignup_hidden" value="<?php echo addslashes($_lang['system_email_websignup']);?>" /><br />
<?php echo $_lang["websignupemail_message"] ?></td>
</tr>
<tr>
<td nowrap class="warning" valign="top"><b><?php echo $_lang["webpwdreminder_title"] ?></b>
<br />
<p><?php echo $_lang["update_settings_from_language"]; ?></p>
<select name="reload_system_email_webreminder_message" id="reload_system_email_webreminder_select" onchange="confirmLangChange(this, 'system_email_webreminder', 'system_email_webreminder_textarea');">
<?php echo get_lang_options('system_email_webreminder');?>
</select>
</td>
<td><textarea id="system_email_webreminder_textarea" name="webpwdreminder_message" style="width:100%; height: 120px;"><?php echo $webpwdreminder_message;?></textarea>
<input type="hidden" name="system_email_webreminder_default" id="system_email_webreminder_hidden" value="<?php echo addslashes($_lang['system_email_webreminder']);?>" /><br />
<?php echo $_lang["webpwdreminder_message"] ?></td>
</tr>
<tr class="row1" style="border-bottom:none;">
<td colspan="2">
<?php
// invoke OnUserSettingsRender event
$evtOut = $modx->invokeEvent("OnUserSettingsRender");
if(is_array($evtOut)) echo implode("",$evtOut);
?>
</td>
</tr>
</table>
</div>

<!-- Interface & editor settings -->
<div class="tab-page" id="tabPage5">
<h2 class="tab"><?php echo $_lang["settings_ui"] ?></h2>
<script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage5" ) );</script>
<table class="settings">
<tr>
<th><?php echo $_lang["manager_theme"]?></th>
<td><select name="manager_theme" size="1" class="inputBox" onchange="documentDirty=true;document.forms['settings'].theme_refresher.value = Date.parse(new Date())">
<?php
$dir = dir("media/style/");
while ($file = $dir->read())
{
	if($file!="." && $file!=".." && is_dir("media/style/$file") && substr($file,0,1) != '.')
	{
		$themename = $file;
		$selectedtext = $themename==$manager_theme ? "selected='selected'" : "" ;
		echo "<option value='$themename' $selectedtext>".ucwords(str_replace("_", " ", $themename))."</option>";
	}
}
$dir->close();
?>
</select><br />
<input type="hidden" name="theme_refresher" value="" />
<?php echo $_lang["manager_theme_message"]?></td>
</tr>

<tr>
<th><?php echo $_lang["warning_visibility"] ?></th>
<td>
	<?php echo form_radio('warning_visibility','0',$warning_visibility=='0');?>
	<?php echo $_lang["administrators"]?><br />
	<?php echo form_radio('warning_visibility','1',$warning_visibility=='1');?>
	<?php echo $_lang["everybody"]?><br /><?php echo $_lang["warning_visibility_message"]?>
</td>
</tr>

<tr>
<th><?php echo $_lang["tree_page_click"] ?></th>
<td>
	<?php echo form_radio('tree_page_click','27',$tree_page_click=='27');?>
	<?php echo $_lang["edit_resource"]?><br />
	<?php echo form_radio('tree_page_click','3',$tree_page_click=='3');?>
	<?php echo $_lang["doc_data_title"]?><br />
	<?php echo form_radio('tree_page_click','auto',$tree_page_click=='auto');?>
	<?php echo $_lang["tree_page_click_option_auto"]?><br />
	<?php echo $_lang["tree_page_click_message"]?>
</td>
</tr>
<tr>
<th><?php echo $_lang["remember_last_tab"] ?></th>
<td>
	<?php echo form_radio('remember_last_tab','2',$remember_last_tab=='2');?>
	<?php echo $_lang["yes"]?> (Full)<br />
	<?php echo form_radio('remember_last_tab','1',$remember_last_tab=='1');?>
	<?php echo $_lang["yes"]?> (Stay mode)<br />
	<?php echo form_radio('remember_last_tab','0',$remember_last_tab=='0');?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["remember_last_tab_message"]?>
</td>
</tr>
<tr>
<th><?php echo $_lang["tree_show_protected"] ?></th>
<td>
	<?php echo form_radio('tree_show_protected','1',$tree_show_protected=='1');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('tree_show_protected','0',$tree_show_protected=='0');?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["tree_show_protected_message"]?>
</td>
</tr>
<tr>
<th><?php echo $_lang["show_meta"] ?></th>
<td>
	<?php echo form_radio('show_meta','1',$show_meta=='1');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('show_meta','0',$show_meta=='0');?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["show_meta_message"]?>
</td>
</tr>

<tr>
<th><?php echo $_lang["datepicker_offset"] ?></th>
<td><input onchange="documentDirty=true;" type="text" maxlength="50" size="5" name="datepicker_offset" value="<?php echo $datepicker_offset; ?>" /><br />
<?php echo $_lang["datepicker_offset_message"]?></td>
</tr>
<tr>
<th><?php echo $_lang["datetime_format"]?></th>
<td><select name="datetime_format" size="1" class="inputBox">
<?php
$datetime_format_list = array('dd-mm-YYYY', 'mm/dd/YYYY', 'YYYY/mm/dd');
$str = '';
foreach($datetime_format_list as $value)
{
$selectedtext = ($datetime_format == $value) ? ' selected' : '';
$str .= '<option value="' . $value . '"' . $selectedtext . '>';
$str .= $value . "</option>\n";
}
echo $str;
?>
</select><br />
<?php echo $_lang["datetime_format_message"]?></td>
</tr>
<tr>
<th><?php echo $_lang["nologentries_title"]?></th>
<td><input onchange="documentDirty=true;" type="text" maxlength="50" size="5" name="number_of_logs" value="<?php echo $number_of_logs; ?>" /><br />
<?php echo $_lang["nologentries_message"]?></td>
</tr>
<tr>
<th><?php echo $_lang["mail_check_timeperiod_title"] ?></th>
<td><input type="text" name="mail_check_timeperiod" onchange="documentDirty=true;" size="5" value="<?php echo $mail_check_timeperiod; ?>" /><br />
<?php echo $_lang["mail_check_timeperiod_message"] ?></td>
</tr>
<tr>
<th><?php echo $_lang["nomessages_title"]?></th>
<td><input onchange="documentDirty=true;" type="text" maxlength="50" size="5" name="number_of_messages" value="<?php echo $number_of_messages; ?>" /><br />
<?php echo $_lang["nomessages_message"]?></td>
</tr>
<tr>
<th><?php echo $_lang["noresults_title"]?></th>
<td><input onchange="documentDirty=true;" type="text" maxlength="50" size="5" name="number_of_results" value="<?php echo $number_of_results; ?>" /><br />
<?php echo $_lang["noresults_message"]?></td>
</tr>
<tr>
<th><?php echo $_lang["rb_title"]?></th>
<td>
	<?php echo form_radio('use_browser','1',$use_browser=='1','onclick="showHide(/rbRow/, 1);"');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('use_browser','0',$use_browser=='0','onclick="showHide(/rbRow/, 0);"');?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["rb_message"]?>
</td>
</tr>

<tr id='rbRow19' class="row3" style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["settings_strip_image_paths_title"]?></th>
<td>
	<?php echo form_radio('strip_image_paths','1',$strip_image_paths=='1');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('strip_image_paths','0',$strip_image_paths=='0');?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["settings_strip_image_paths_message"]?>
</td>
</tr>

<?php if(!isset($use_browser)) $use_browser=1; ?>

<tr id='rbRow1' class="row3" style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["rb_webuser_title"]?></th>
<td>
	<?php echo form_radio('rb_webuser','1',$rb_webuser=='1');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('rb_webuser','0',$rb_webuser=='0');?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["rb_webuser_message"]?>
</td>
</tr>
<tr id='rbRow4' class='row3' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["rb_base_dir_title"]?></th>
<td><?php
function getResourceBaseDir() {
global $base_path;
return "{$base_path}assets/";
}
?>
<?php echo $_lang['default']; ?> <span id="default_rb_base_dir"><?php echo getResourceBaseDir()?></span><br />
<input onchange="documentDirty=true;" type="text" maxlength="255" name="rb_base_dir" id="rb_base_dir" value="<?php echo $rb_base_dir; ?>" /> <input type="button" onclick="reset_path('rb_base_dir');" value="<?php echo $_lang["reset"]; ?>" name="reset_rb_base_dir"><br />
<?php echo $_lang["rb_base_dir_message"]?></td>
</tr>
<tr id='rbRow7' class='row3' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["rb_base_url_title"]?></th>
<td><?php
function getResourceBaseUrl() {
global $site_url;
return $site_url . "assets/";
}
?>
<input onchange="documentDirty=true;" type="text" maxlength="255" name="rb_base_url" value="<?php echo $rb_base_url; ?>" /><br />
<?php echo $_lang["rb_base_url_message"]?></td>
</tr>
<tr id='rbRow10' class='row3' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["uploadable_images_title"]?></th>
<td>
<input onchange="documentDirty=true;" type="text" maxlength="255" name="upload_images" value="<?php echo $upload_images; ?>" /><br />
<?php echo $_lang["uploadable_images_message"]?></td>
</tr>
<tr id='rbRow13' class='row3' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["uploadable_media_title"]?></th>
<td>
<input onchange="documentDirty=true;" type="text" maxlength="255" name="upload_media" value="<?php echo $upload_media; ?>" /><br />
<?php echo $_lang["uploadable_media_message"]?></td>
</tr>
<tr id='rbRow16' class='row3' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["uploadable_flash_title"]?></th>
<td>
<input onchange="documentDirty=true;" type="text" maxlength="255" name="upload_flash" value="<?php echo $upload_flash; ?>" /><br />
<?php echo $_lang["uploadable_flash_message"]?></td>
</tr>
<tr id='rbRow172' class='row3' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["clean_uploaded_filename"]?></th>
<td>
	<?php echo form_radio('clean_uploaded_filename','1',$clean_uploaded_filename=='1');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('clean_uploaded_filename','0',$clean_uploaded_filename=='0');?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["clean_uploaded_filename_message"];?>
</td>
</tr>

<tr>
<th><?php echo $_lang["use_editor_title"]?></th>
<td>
	<?php echo form_radio('use_editor','1',$use_editor=='1','onclick="showHide(/editorRow/, 1);"');?>
	<?php echo $_lang["yes"]?><br />
	<?php echo form_radio('use_editor','0',$use_editor=='0','onclick="showHide(/editorRow/, 0);"');?>
	<?php echo $_lang["no"]?><br />
	<?php echo $_lang["use_editor_message"]?>
</td>
</tr>

<?php if(!isset($use_editor)) $use_editor=1; ?>

<tr id='editorRow0' class="row3" style="display: <?php echo $use_editor==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["which_editor_title"]?></th>
<td>
<select name="which_editor" onchange="documentDirty=true;">
<?php
// invoke OnRichTextEditorRegister event
$evtOut = $modx->invokeEvent("OnRichTextEditorRegister");
echo "<option value='none'".($which_editor=='none' ? " selected='selected'" : "").">".$_lang["none"]."</option>\n";
if(is_array($evtOut)) for($i=0;$i<count($evtOut);$i++) {
$editor = $evtOut[$i];
echo "<option value='$editor'".($which_editor==$editor ? " selected='selected'" : "").">$editor</option>\n";
}
?>
</select><br />
<?php echo $_lang["which_editor_message"]?></td>
</tr>
<tr id='editorRow4' class="row3" style="display: <?php echo $use_editor==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["fe_editor_lang_title"]?></th>
<td><select name="fe_editor_lang" size="1" class="inputBox" onchange="documentDirty=true;">
<?php echo get_lang_options(null, $fe_editor_lang);?>
</select><br />
<?php echo $_lang["fe_editor_lang_message"]?></td>
</tr>
<tr id='editorRow14' class="row3" style="display: <?php echo $use_editor==1 ? $displayStyle : 'none' ; ?>">
<th><?php echo $_lang["editor_css_path_title"]?></th>
<td><input onchange="documentDirty=true;" type="text" maxlength="255" name="editor_css_path" value="<?php echo $editor_css_path; ?>" /><br />
<?php echo $_lang["editor_css_path_message"]?></td>
</tr>
<tr class="row1" style="border-bottom:none;">
<td colspan="2">
<?php
// invoke OnInterfaceSettingsRender event
$evtOut = $modx->invokeEvent("OnInterfaceSettingsRender");
if(is_array($evtOut)) echo implode("",$evtOut);
?>
</td>
</tr>
</table>
</div>

<!-- Miscellaneous settings -->
<div class="tab-page" id="tabPage7">
<h2 class="tab"><?php echo $_lang["settings_misc"] ?></h2>
<script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage7" ) );</script>
<table class="settings">
<tr>
<th><?php echo $_lang["filemanager_path_title"]?></th>
<td>
<?php echo $_lang['default']; ?> <span id="default_filemanager_path"><?php echo $base_path; ?></span><br />
<input onchange="documentDirty=true;" type="text" maxlength="255" name="filemanager_path" id="filemanager_path" value="<?php echo $filemanager_path; ?>" /> <input type="button" onclick="reset_path('filemanager_path');" value="<?php echo $_lang["reset"]; ?>" name="reset_filemanager_path"><br />
<?php echo $_lang["filemanager_path_message"]?></td>
</tr>
<tr>
<th><?php echo $_lang["uploadable_files_title"]?></th>
<td>
<input onchange="documentDirty=true;" type="text" maxlength="255" name="upload_files" value="<?php echo $upload_files; ?>" /><br />
<?php echo $_lang["uploadable_files_message"]?></td>
</tr>
<tr>
<th><?php echo $_lang["upload_maxsize_title"]?></th>
<td>
<?php
if(version_compare(ini_get('upload_max_filesize'), ini_get('post_max_size'),'<'))
{
	$limit_size = ini_get('upload_max_filesize');
}
else $limit_size = ini_get('post_max_size');

if(version_compare(ini_get('memory_limit'), $limit_size,'<'))
{
	$limit_size = ini_get('memory_limit');
}
?>
<input onchange="documentDirty=true;" type="text" maxlength="255" name="upload_maxsize" value="<?php echo !empty($upload_maxsize) ? $upload_maxsize : $limit_size ; ?>" /><br />
<?php echo sprintf($_lang["upload_maxsize_message"],$limit_size);?></td>
</tr>
<tr>
<th><?php echo $_lang["new_file_permissions_title"]?></th>
<td>
<input onchange="documentDirty=true;" type="text" maxlength='4' style="width:50px;" name="new_file_permissions" value="<?php echo $new_file_permissions; ?>" /><br />
<?php echo $_lang["new_file_permissions_message"]?></td>
</tr>
<tr>
<th><?php echo $_lang["new_folder_permissions_title"]?></th>
<td>
<input onchange="documentDirty=true;" type="text" maxlength='4' style="width:50px;" name="new_folder_permissions" value="<?php echo $new_folder_permissions; ?>" /><br />
<?php echo $_lang["new_folder_permissions_message"]?></td>
</tr>
<tr class="row1" style="border-bottom:none;">
<td colspan="2">
<?php
// invoke OnMiscSettingsRender event
$evtOut = $modx->invokeEvent("OnMiscSettingsRender");
if(is_array($evtOut)) echo implode("",$evtOut);
?>
</td>
</tr>
</table>
</div>
</div>
</div>
</form>
<?php
/**
* get_lang_keys
* 
* @return array of keys from a language file
*/
function get_lang_keys($filename)
{
	$file = MODX_MANAGER_PATH.'includes/lang' . DIRECTORY_SEPARATOR . $filename;
	if(is_file($file) && is_readable($file))
	{
		include($file);
		return array_keys($_lang);
	}
	else
	{
		return array();
	}
}
/**
* get_langs_by_key
* 
* @return array of languages that define the key in their file
*/
function get_langs_by_key($key)
{
	global $lang_keys;
	$lang_return = array();
	foreach($lang_keys as $lang=>$keys)
	{
		if(in_array($key, $keys))
		{
			$lang_return[] = $lang;
		}
	}
	return $lang_return;
}

/**
* get_lang_options
*
* returns html option list of languages
* 
* @param string $key specify language key to return options of langauges that override it, default return all languages
* @param string $selected_lang specify language to select in option list, default none
* @return html option list
*/
function get_lang_options($key=null, $selected_lang=null)
{
	global $lang_keys, $_lang;
	$lang_options = '';
	if($key)
	{
		$languages = get_langs_by_key($key);
		sort($languages);
		$lang_options .= '<option value="">'.$_lang['language_title'].'</option>';
		foreach($languages as $language_name)
		{
			$uclanguage_name = ucwords(str_replace("_", " ", $language_name));
			$lang_options .= '<option value="'.$language_name.'">'.$uclanguage_name.'</option>';
		}
		return $lang_options;
	}
	else
	{
		$languages = array_keys($lang_keys);
		sort($languages);
		foreach($languages as $language_name)
		{
			$uclanguage_name = ucwords(str_replace("_", " ", $language_name));
			$sel = $language_name == $selected_lang ? ' selected="selected"' : '';
			$lang_options .= '<option value="'.$language_name.'" '.$sel.'>'.$uclanguage_name.'</option>';
		}
		return $lang_options;
	}
}

function form_radio($name,$value,$checked=false,$add='',$disabled=false)
{
	if($checked)  $checked  = ' checked="checked"';
	if($disabled) $disabled = ' disabled';
	if($add)     $add = ' ' . $add;
	return '<input onchange="documentDirty=true;" type="radio" name="' . $name . '" value="' . $value . '"' . $checked . $disabled . $add . ' />';
}

?>