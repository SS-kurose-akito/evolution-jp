<?php
if(!isset($modx) || !$modx->isLoggedin()) exit;
if (!$modx->hasPermission('save_user')) {
	$e->setError(3);
	$e->dumpError();
}

$modx->loadExtension('phpass');

if(isset($_POST['userid']) && preg_match('@^[0-9]+$@',$_POST['userid'])) $id = $_POST['userid'];
$mode = $_POST['mode'];
$oldusername = $_POST['oldusername'];
$newusername = !empty ($_POST['newusername']) ? trim($_POST['newusername']) : "New User";
$fullname = $modx->db->escape($_POST['fullname']);
$genpassword = $_POST['newpassword'];
$passwordgenmethod = $_POST['passwordgenmethod'];
$passwordnotifymethod = $_POST['passwordnotifymethod'];
$specifiedpassword = $_POST['specifiedpassword'];
$email = $modx->db->escape($_POST['email']);
$oldemail = $_POST['oldemail'];
$phone = $modx->db->escape($_POST['phone']);
$mobilephone = $modx->db->escape($_POST['mobilephone']);
$fax = $modx->db->escape($_POST['fax']);
$dob = !empty ($_POST['dob']) ? $modx->toTimeStamp($_POST['dob']) : 0;
$country = $_POST['country'];
$street = $modx->db->escape($_POST['street']);
$city   = $modx->db->escape($_POST['city']);
$state = $modx->db->escape($_POST['state']);
$zip = $modx->db->escape($_POST['zip']);
$gender = !empty ($_POST['gender']) ? $_POST['gender'] : 0;
$photo = $modx->db->escape($_POST['photo']);
$comment = $modx->db->escape($_POST['comment']);
$role = !empty ($_POST['role']) ? $_POST['role'] : 0;
$failedlogincount = $_POST['failedlogincount'];
$blocked = !empty ($_POST['blocked']) ? $_POST['blocked'] : 0;
$blockeduntil = !empty ($_POST['blockeduntil']) ? $modx->toTimeStamp($_POST['blockeduntil']) : 0;
$blockedafter = !empty ($_POST['blockedafter']) ? $modx->toTimeStamp($_POST['blockedafter']) : 0;

// verify password
if ($passwordgenmethod == "spec" && $_POST['specifiedpassword'] != $_POST['confirmpassword']) {
	webAlert("Password typed is mismatched");
	exit;
}

// verify email
if ($email == '' || !preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,20}$/i", $email)) {
	webAlert("E-mail address doesn't seem to be valid!");
	exit;
}

// verify admin security
if ($_SESSION['mgrRole'] != 1) {
	// Check to see if user tried to spoof a "1" (admin) role
	if ($role == 1)
	{
		if(!$modx->hasPermission('edit_role')
		    || !$modx->hasPermission('save_role')
		    || !$modx->hasPermission('delete_role')
		    || !$modx->hasPermission('new_role')
		    )
			{
				webAlert("Illegal attempt to create/modify administrator by non-administrator!");
				exit;
			}
	}
	// Verify that the user being edited wasn't an admin and the user ID got spoofed
	if (isset($id) && $rs = $modx->db->select('role','[+prefix+]user_attributes',"internalKey='{$id}'")) {
		if (0 < $modx->db->getRecordCount($rs))
		{	// There should only be one if there is one
			$row = $modx->db->getRow($rs);
			if ($row['role'] == 1) {
				webAlert("You cannot alter an administrative user.");
				exit;
			}
		}
	}
}

switch ($mode) {
	case '11' : // new user
		// check if this user name already exist
		if (!$rs = $modx->db->select('id','[+prefix+]manager_users',"username='".$modx->db->escape($newusername)."'") )
		{
			webAlert("An error occurred while attempting to retrieve all users with username $newusername.");
			exit;
		}
		$limit = $modx->db->getRecordCount($rs);
		if ($limit > 0) {
			webAlert("User name is already in use!");
			exit;
		}

		// check if the email address already exist
		if (!$rs = $modx->db->select('id','[+prefix+]user_attributes',"email='{$email}'"))
		{
			webAlert("An error occurred while attempting to retrieve all users with email {$email}.");
			exit;
		}
		$limit = $modx->db->getRecordCount($rs);
		if ($limit > 0) {
				webAlert("Email is already in use!");
				exit;
		}
		// generate a new password for this user
		if ($specifiedpassword != '' && $passwordgenmethod == "spec") {
			if (strlen($specifiedpassword) < 6) {
				webAlert("Password is too short!");
				exit;
			} else {
				$newpassword = $specifiedpassword;
			}
		}
		elseif ($specifiedpassword == '' && $passwordgenmethod == "spec") {
			webAlert("You didn't specify a password for this user!");
			exit;
		}
		elseif ($passwordgenmethod == 'g') {
			$newpassword = generate_password(8);
		} else {
			webAlert("No password generation method specified!");
			exit;
		}
		// invoke OnBeforeUserFormSave event
    $tmp = array (
			"mode" => "new",
			"id" => $id
		);
		$modx->invokeEvent("OnBeforeUserFormSave", $tmp);

		// build the SQL
		$field = array();
		$field['username'] = $modx->db->escape($newusername);
		$internalKey = $modx->db->insert($field,'[+prefix+]manager_users');
		if (!$internalKey) {
			webAlert("An error occurred while attempting to save the user.");
			exit;
		}
		$field['password'] = $modx->phpass->HashPassword($newpassword);
		$modx->db->update($field,'[+prefix+]manager_users',"id='{$internalKey}'");
		
		$field = array();
		$field = compact('internalKey','fullname','role','email','phone','mobilephone','fax','zip','street','city','state','country','gender','dob','photo','comment','blocked','blockeduntil','blockedafter');
		$rs = $modx->db->insert($field,'[+prefix+]user_attributes');
		if (!$rs) {
			webAlert("An error occurred while attempting to save the user's attributes.");
			exit;
		}

		// Save User Settings
		saveUserSettings($internalKey);

		// invoke OnManagerSaveUser event
    $tmp = array (
			"mode" => "new",
			"userid" => $internalKey,
			"username" => $newusername,
			"userpassword" => $newpassword,
			"useremail" => $email,
			"userfullname" => $fullname,
			"userroleid" => $role
		);
		$modx->invokeEvent("OnManagerSaveUser", $tmp);

		// invoke OnUserFormSave event
    $tmp = array (
			"mode" => "new",
			"id" => $internalKey
		);
		$modx->invokeEvent("OnUserFormSave", $tmp);
		
		/*******************************************************************************/
		// put the user in the user_groups he/ she should be in
		// first, check that up_perms are switched on!
		if ($modx->config['use_udperms'] == 1) {
			$user_groups = $_POST['user_groups'];
			if (0 < count($user_groups)):
				foreach ($user_groups as $user_group):
					$user_group = intval($user_group);
					$rs = $modx->db->insert(array('user_group'=>$user_group,'member'=>$internalKey),'[+prefix+]member_groups');
					if (!$rs) {
						webAlert("An error occurred while attempting to add the user to a user_group.");
						exit;
					}
				endforeach;
			endif;
		}
		// end of user_groups stuff!

		if ($passwordnotifymethod == 'e') {
			sendMailMessage($email, $newusername, $newpassword, $fullname);
			if ($_POST['stay'] != '') {
				$a = ($_POST['stay'] == '2') ? "{$mode}&id=$id" : "11";
				$header = "Location: index.php?r=3&a=" . $a . "&stay=" . $_POST['stay'];
			} elseif($mode==='74') {
				$header = "Location: index.php?r=3&a=2";
			} else {
				$header = "Location: index.php?r=3&a=75";
			}
			header($header);
			exit;
		} else {
			if ($_POST['stay'] != '') {
				$a = ($_POST['stay'] == '2') ? "{$mode}&id={$internalKey}" : "11";
				$stayUrl = "index.php?r=3&a=" . $a . "&stay=" . $_POST['stay'];
			} elseif($mode==='74') {
				$stayUrl = "index.php?r=3&a=2";
			} else {
				$stayUrl = "index.php?r=3&a=75";
			}
			
			include_once(MODX_MANAGER_PATH . 'actions/header.inc.php');
?>
			<h1><?php echo $_lang['user_title']; ?></h1>

			<div id="actions">
			<ul class="actionButtons">
				<li class="mutate"><a href="<?php echo $stayUrl ?>"><img src="<?php echo $_style["icons_save"] ?>" /> <?php echo $_lang['close']; ?></a></li>
			</ul>
			</div>

			<div class="section">
			<div class="sectionHeader"><?php echo $_lang['user_title']; ?></div>
			<div class="sectionBody">
			<div id="disp">
			<p>
			<?php
				echo sprintf($_lang["password_msg"], $newusername, $newpassword);
			?>
			</p>
			</div>
			</div>
			</div>
		<?php

			include_once(MODX_MANAGER_PATH . 'actions/footer.inc.php');
		}
		break;

	case '12' : // edit user
	case '74' : // edit user profile
		// generate a new password for this user
		if ($genpassword == 1) {
			if ($specifiedpassword != '' && $passwordgenmethod == "spec") {
				if (strlen($specifiedpassword) < 6) {
					webAlert("Password is too short!");
					exit;
				} else {
					$newpassword = $specifiedpassword;
				}
			}
			elseif ($specifiedpassword == '' && $passwordgenmethod == "spec") {
				webAlert("You didn't specify a password for this user!");
				exit;
			}
			elseif ($passwordgenmethod == 'g') {
				$newpassword = generate_password(8);
			} else {
				webAlert("No password generation method specified!");
				exit;
			}
			$hashed_password = $modx->phpass->HashPassword($newpassword);
		}

		// check if the username already exist
		if (!$rs = $modx->db->select('id','[+prefix+]manager_users',"username='".$modx->db->escape($newusername)."'")) {
			webAlert("An error occurred while attempting to retrieve all users with username $newusername.");
			exit;
		}
		$limit = $modx->db->getRecordCount($rs);
		if ($limit > 0) {
			$row = $modx->db->getRow($rs);
			if ($row['id'] != $id) {
				webAlert("User name is already in use!");
				exit;
			}
		}

		// check if the email address already exists
		if (!$rs = $modx->db->select('internalKey','[+prefix+]user_attributes',"email='{$email}'")) {
			webAlert("An error occurred while attempting to retrieve all users with email $email.");
			exit;
		}
		$limit = $modx->db->getRecordCount($rs);
		if ($limit > 0) {
			$row = $modx->db->getRow($rs);
			if ($row['internalKey'] != $id) {
				webAlert("Email is already in use!");
				exit;
			}
		}

		// invoke OnBeforeUserFormSave event
    $tmp = array (
			"mode" => "upd",
			"id" => $id
		);
		$modx->invokeEvent("OnBeforeUserFormSave", $tmp);

		// update user name and password
		$field = array();
		$field['username'] = $modx->db->escape($newusername);
		if(isset($hashed_password)) $field['password'] = $hashed_password;
		if (!$rs = $modx->db->update($field,'[+prefix+]manager_users',"id='{$id}'")) {
			webAlert("An error occurred while attempting to update the user's data.");
			exit;
		}
		
		$field = compact('fullname','role','email','phone','mobilephone','fax','zip','street','city','state','country','gender','dob','photo','comment','failedlogincount','blocked','blockeduntil','blockedafter');
		if (!$rs = $modx->db->update($field,'[+prefix+]user_attributes',"internalKey='{$id}'")) {
			webAlert("An error occurred while attempting to update the user's attributes.");
			exit;
		}

		// Save user settings
		saveUserSettings($id);

		// invoke OnManagerSaveUser event
    $tmp = array (
			"mode" => "upd",
			"userid" => $id,
			"username" => $newusername,
			"userpassword" => $newpassword,
			"useremail" => $email,
			"userfullname" => $fullname,
			"userroleid" => $role,
			"oldusername" => (($oldusername != $newusername
      ) ? $oldusername : ''), "olduseremail" => (($oldemail != $email) ? $oldemail : ''));
		$modx->invokeEvent("OnManagerSaveUser", $tmp);

		// invoke OnManagerChangePassword event
		if (isset($hashed_password))
      $tmp = array (
				"userid" => $id,
				"username" => $newusername,
				"userpassword" => $newpassword
			);
			$modx->invokeEvent("OnManagerChangePassword", $tmp);

		if ($passwordnotifymethod == 'e' && $genpassword == 1) {
			sendMailMessage($email, $newusername, $newpassword, $fullname);
		}

		// invoke OnUserFormSave event
    $tmp = array (
			"mode" => "upd",
			"id" => $id
		);
		$modx->invokeEvent("OnUserFormSave", $tmp);
		$modx->clearCache();
		/*******************************************************************************/
		// put the user in the user_groups he/ she should be in
		// first, check that up_perms are switched on!
		if ($modx->config['use_udperms'] == 1) {
			// as this is an existing user, delete his/ her entries in the groups before saving the new groups
			$rs = $modx->db->delete('[+prefix+]member_groups', "member='{$id}'");
			if (!$rs) {
				webAlert("An error occurred while attempting to delete previous user_groups entries.");
				exit;
			}
			$user_groups = $_POST['user_groups'];
			if (0 < count($user_groups)):
				foreach ($user_groups as $user_group):
					$user_group = intval($user_group);
					$rs = $modx->db->insert(array('user_group'=>$user_group,'member'=>$id),'[+prefix+]member_groups');
					if (!$rs) {
						webAlert("An error occurred while attempting to add the user to a user_group.");
						exit;
					}
				endforeach;
			endif;
		}
		// end of user_groups stuff!
		/*******************************************************************************/
		if ($id == $modx->getLoginUserID() && ($genpassword !==1 && $passwordnotifymethod !='s')) {
?>
			<body bgcolor='#efefef'>
			<script language="JavaScript">
			alert("<?php echo $_lang["user_changeddata"]; ?>");
			top.location.href='index.php?a=8';
			</script>
			</body>
		<?php
			exit;
		}
		unset($_SESSION['mgrUsrConfigSet']);
		$modx->getSettings();
		if ($id == $modx->getLoginUserID() && $_SESSION['mgrRole'] !== $role)
		{
			$_SESSION['mgrRole'] = $role;
			$modx->webAlertAndQuit($_lang['save_user.processor.php1'],'index.php?a=75');
			exit;
		}
		if ($genpassword == 1 && $passwordnotifymethod == 's') {
			if ($_POST['stay'] != '') {
				$a = ($_POST['stay'] == '2') ? "{$mode}&id={$id}" : "11";
				$stayUrl = "index.php?a=" . $a . "&stay=" . $_POST['stay'];
			} else {
				$stayUrl = "index.php?a=75";
			}
			
			include_once(MODX_MANAGER_PATH . 'actions/header.inc.php');
?>
			<h1><?php echo $_lang['user_title']; ?></h1>

			<div id="actions">
			<ul class="actionButtons">
				<li class="mutate"><a href="<?php echo ($id == $modx->getLoginUserID()) ? 'index.php?a=8' : $stayUrl; ?>"><img src="<?php echo $_style["icons_save"] ?>" /> <?php echo ($id == $modx->getLoginUserID()) ? $_lang['logout'] : $_lang['close']; ?></a></li>
			</ul>
			</div>

			<div class="section">
			<div class="sectionHeader"><?php echo $_lang['user_title']; ?></div>
			<div class="sectionBody">
			<div id="disp">
			<p>
			<?php echo sprintf($_lang["password_msg"], $newusername, $newpassword).(($id == $modx->getLoginUserID()) ? ' '.$_lang['user_changeddata'] : ''); ?>
			</p>
			</div>
			</div>
			</div>
		<?php
			
			include_once(MODX_MANAGER_PATH . 'actions/footer.inc.php');
		} else {
			if ($_POST['stay'] != '') {
				$a = ($_POST['stay'] == '2') ? "{$mode}&id={$id}" : "11";
				$header = "Location: index.php?a={$a}&r=3&stay={$_POST['stay']}";
			} elseif($mode==='74') {
				$header = "Location: index.php?r=3&a=2";
			} else {
				$header = "Location: index.php?a=75&r=3";
			}
			header($header);
		}
		break;
	default :
		webAlert("Unauthorized access");
		exit;
}

// Send an email to the user
function sendMailMessage($email, $uid, $pwd, $ufn)
{
	global $modx,$_lang;
	$ph['username'] = $uid;
	$ph['uid']      = $uid;
	$ph['password'] = $pwd;
	$ph['pwd']      = $pwd;
	$ph['fullname'] = $ufn;
	$ph['ufn']      = $ufn;
	$site_name      = $modx->config['site_name'];
	$ph['site_name'] = $site_name;
	$ph['sname']    = $site_name;
	$admin_email    = $modx->config['emailsender'];
	$ph['manager_email'] = $admin_email;
	$ph['saddr']    = $admin_email;
	$ph['semail']   = $admin_email;
	$site_url       = $modx->config['site_url'];
	$ph['site_url'] = $site_url;
	$ph['surl']     = "{$site_url}manager/";
	$message = $modx->parseText($modx->config['signupemail_message'],$ph);
	$message = $modx->mergeSettingsContent($message);

	$rs = $modx->sendmail($email,$message);
	if ($rs === false) //ignore mail errors in this cas
	{
		webAlert("{$email} - {$_lang['error_sending_email']}");
		exit;
	}
}

// Save User Settings
function saveUserSettings($id)
{
	global $modx;

	// array of post values to ignore in this function
	$ignore = array(
		'id',
		'oldusername',
		'oldemail',
		'newusername',
		'fullname',
		'newpassword',
		'newpasswordcheck',
		'passwordgenmethod',
		'passwordnotifymethod',
		'specifiedpassword',
		'confirmpassword',
		'email',
		'phone',
		'mobilephone',
		'fax',
		'dob',
		'country',
		'street',
		'city',
		'state',
		'zip',
		'gender',
		'photo',
		'comment',
		'role',
		'failedlogincount',
		'blocked',
		'blockeduntil',
		'blockedafter',
		'user_groups',
		'mode',
		'blockedmode',
		'stay',
		'save',
		'theme_refresher',
		'userid'
	);

	// determine which settings can be saved blank (based on 'default_{settingname}' POST checkbox values)
	$defaults = array(
		'manager_inline_style',
		'upload_images',
		'upload_media',
		'upload_flash',
		'upload_files'
	);

	// get user setting field names
	$settings= array ();
	foreach ($_POST as $n => $v)
	{
		if(is_array($v)) $v = implode(',', $v);
		if(in_array($n, $ignore) || (!in_array($n, $defaults) && trim($v) == '')) continue; // ignore blacklist and empties

		//if ($config[$n] == $v) continue; // ignore commonalities in base config

		$settings[$n] = $v; // this value should be saved
	}
	foreach ($defaults as $k)
	{
		if (isset($settings["default_{$k}"]) && $settings["default_{$k}"] == '1')
		{
			unset($settings[$k]);
		}
		unset($settings["default_{$k}"]);
	}

	$tbl_user_settings = $modx->getFullTableName('user_settings');

	$modx->db->delete($tbl_user_settings, "user='{$id}'");

	$savethese = array();
	foreach ($settings as $k => $v)
	{
		$v = $modx->db->escape($v);
		$savethese[] = "({$id}, '{$k}', '{$v}')";
	}
	if(empty($savethese)) return;
	$values = implode(', ', $savethese);
	$sql = "INSERT INTO {$tbl_user_settings} (user, setting_name, setting_value) VALUES {$values}";
	$rs = $modx->db->query($sql);
	if (!$rs) die('Failed to update user settings!');
	unset($_SESSION['openedArray']);
}

// Web alert -  sends an alert to web browser
function webAlert($msg) {
	global $id, $modx;
	$mode = $_POST['mode'];
	$url = "index.php?a={$mode}" . ($mode == '12' ? "&id={$id}" : '');
	$modx->manager->saveFormValues($mode);
	$modx->webAlertAndQuit($msg, $url);
}

// Generate password
function generate_password($length = 10) {
	return substr(str_shuffle('abcdefghjkmnpqrstuvxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, $length);
}
