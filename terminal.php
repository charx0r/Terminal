<?php 

/* User config options */

// example (single user)
// $passwd = array('user' => 'passwd');

// example (multiple user)
// $passwd = array('usera' => 'passwd',
//		'userb' => 'passwd');
// and so on...

$passwd = array('' => '');

$aliases = array('la' 	=> 'ls -la',
		'll' 	=> 'ls -lvhF',
		'dir'	=> 'ls' );

/* do NOT change anything below this line */

error_reporting(E_ALL);

class phpTerm
{

function phpTerm()
{} // constructor

function formatPrompt()
{
	$user=shell_exec("whoami");
	$host=explode(".", shell_exec("uname -n"));
	$_SESSION['prompt'] = "".rtrim($user).""."@"."".rtrim($host[0])."";
}

function checkPassword($passwd)
{
if(!isset($_SERVER['PHP_AUTH_USER'])||
	!isset($_SERVER['PHP_AUTH_PW']) ||
	!isset($passwd[$_SERVER['PHP_AUTH_USER']]) ||
	$passwd[$_SERVER['PHP_AUTH_USER']] != $_SERVER['PHP_AUTH_PW'])
	{
		@session_destroy();
		return false;
	}
	else
	{
		@session_start();
		return true;
	}
}

function logout($logout)
{
if($logout==true){
	
	header('WWW-Authenticate: Basic realm="Terminal"');
	header('HTTP/1.0 401 Unauthorized');
	exit();
}
}

function phpCheckVersion($min_version)
{
$is_version=phpversion();

list($v1,$v2,$v3,$v4) = sscanf($is_version,"%d.%d.%d%s");
list($m1,$m2,$m3,$m4) = sscanf($min_version,"%d.%d.%d%s");

	if($v1>$m1)
	return(1);
		elseif($v1<$m1)
		return(0);
	if($v2>$m2)
	return(1);
		elseif($v2<$m2)
		return(0);
	if($v3>$m3)
	return(1);
		elseif($v3<$m3)
		return(0);

	if((!$v4)&&(!$m4))
	return(1);
	if(($v4)&&(!$m4))
	{
		$is_version=strpos($v4,"pl");
		if(is_integer($is_version))
		return(1);
		return(0);
	}
	elseif((!$v4)&&($m4))
	{
		$is_version=strpos($m4,"rc");
		if(is_integer($is_version))
		return(1);
	return(0);
	}
return(0);
}

function initVars()
{
if (empty($_SESSION['cwd']) || @!empty($_GET['reset']))
{
	$_SESSION['cwd'] = getcwd();
	$_SESSION['history'] = array();
	$_SESSION['output'] = '';
	$_REQUEST['command'] ='';
	$_SESSION['color'] = 'linux';
}
}

function buildCommandHistory()
{
if(!empty($_REQUEST['command']))
{
	if(get_magic_quotes_gpc())
	{
		$_REQUEST['command'] = stripslashes($_REQUEST['command']);
	}
	
	// drop old commands from list if exists
	if (($i = array_search($_REQUEST['command'], $_SESSION['history'])) !== false)
	{
		unset($_SESSION['history'][$i]);
	}
	array_unshift($_SESSION['history'], $_REQUEST['command']);

	// append commmand */
	$_SESSION['output'] .= "{$_SESSION['prompt']}".":>"."{$_REQUEST['command']}"."\n";
}
}

function buildJavaHistory()
{
	// build command history for use in the JavaScript 
	if (empty($_SESSION['history']))
	{
		$_SESSION['js_command_hist'] = '""';
	}
	else
	{
		$escaped = array_map('addslashes', $_SESSION['history']);
		$_SESSION['js_command_hist'] = '"", "' . implode('", "', $escaped) . '"';
	}
}

function setTerminalColor($color)
{
//$_SESSION['color']="$color";

// terminal colors
switch($color)
{
	case "linux":
	{
		echo "<style>textarea {width: 99.5%; border: none; margin: 0px; padding: 2px 2px 2px; color: #CCCCCC; background-color: #000000;}
		p {font-family: monospace; margin: 0px; padding: 0px 2px 2px; background-color: #000000; color: #CCCCCC;}
		input.prompt {border: none; font-family: monospace; background-color: #000000; color: #CCCCCC;}</style>";
	break;
	}
	case "green":
	{
		echo "<style>
		textarea {width: 99.5%; border: none; margin: 0px; padding: 2px 2px 2px; color: #00C000; background-color: #000000;}
		p {font-family: monospace; margin: 0px; padding: 0px 2px 2px; background-color: #000000; color: #00C000;}
		input.prompt {border: none; font-family: monospace; background-color: #000000; color: #00C000;}</style>";
	break;
	}
	case "black":
	{
		echo "<style>
		textarea {width: 99.5%; border: none; margin: 0px; padding: 2px 2px 2px; color: #000000; background-color: #00C000;}
		p {font-family: monospace; margin: 0px; padding: 0px 2px 2px; background-color: #00C000; color: #000000;}
		input.prompt {border: none; font-family: monospace; background-color: #00C000; color: #000000;}</style>";
	break;
	}
	case "gray":
	{
		echo "<style>
		textarea {width: 99.5%; border: none; margin: 0px; padding: 2px 2px 2px; color: #CCCCCC; background-color: #0000FF;}
		p {font-family: monospace; margin: 0px; padding: 0px 2px 2px; background-color: #0000FF; color: #CCCCCC;}
		input.prompt {border: none; font-family: monospace; background-color: #0000FF; color: #CCCCCC;}</style>";
	break;
	}
	default: 
	{
		echo "<style>textarea {width: 99.5%; border: none; margin:0px; padding: 2px 2px 2px; color: #CCCCCC; background-color: #000000;}
		p {font-family: monospace; margin: 0px; padding: 0px 2px 2px; background-color: #000000; color: #CCCCCC;}
		input.prompt {border: none; font-family: monospace; background-color: #000000; color: #CCCCCC;}</style>";
	break;
	}
}
}

function outputHandle($aliases)
{
if (preg_match('/^[[:blank:]]*cd[[:blank:]]*$/', @$_REQUEST['command']))
{
	$_SESSION['cwd'] = getcwd(); //dirname(__FILE__);
}
elseif(preg_match('/^[[:blank:]]*cd[[:blank:]]+([^;]+)$/', @$_REQUEST['command'], $regs))
{
	// The current command is 'cd', which we have to handle as an internal shell command. 
	// absolute/relative path ?"
	($regs[1][0] == '/') ? $new_dir = $regs[1] : $new_dir = $_SESSION['cwd'] . '/' . $regs[1];
		
	// cosmetics 
	while (strpos($new_dir, '/./') !== false)
	$new_dir = str_replace('/./', '/', $new_dir);
	while (strpos($new_dir, '//') !== false)
	$new_dir = str_replace('//', '/', $new_dir);
	while (preg_match('|/\.\.(?!\.)|', $new_dir))
	$new_dir = preg_replace('|/?[^/]+/\.\.(?!\.)|', '', $new_dir);

	if(empty($new_dir)): $new_dir = "/"; endif;

	(@chdir($new_dir)) ? $_SESSION['cwd'] = $new_dir : $_SESSION['output'] .= "could not change to: $new_dir\n";
}
else
{
		/* The command is not a 'cd' command, so we execute it after
		changing the directory and save the output. */
		chdir($_SESSION['cwd']);

		/* Alias expansion. */
		$length = strcspn(@$_REQUEST['command'], " \t");
		$token = substr(@$_REQUEST['command'], 0, $length);
		if (isset($aliases[$token]))
			$_REQUEST['command'] = $aliases[$token] . substr($_REQUEST['command'], $length);
		
			
		if($this->phpCheckVersion("4.3.0"))
		{	
			$p = proc_open(@$_REQUEST['command'],
				array(1 => array('pipe', 'w'),
				2 => array('pipe', 'w')), $io);
	
			/* Read output sent to stdout. */
			while (!feof($io[1])) {
			$_SESSION['output'] .= htmlspecialchars(fgets($io[1]),ENT_COMPAT, 'UTF-8');
			}
			/* Read output sent to stderr. */
			while (!feof($io[2])) {
			$_SESSION['output'] .= htmlspecialchars(fgets($io[2]),ENT_COMPAT, 'UTF-8');
			}
			
			fclose($io[1]);
			fclose($io[2]);
			proc_close($p);
		}
		else
		{
			$stdout=shell_exec($_REQUEST['command']);
			$_SESSION['output'] .= htmlspecialchars($stdout,ENT_COMPAT, 'UTF-8');
		}
	}
}
} // end

/*##########################################################
## The main thing starts here
## 
##########################################################*/

$terminal = new phpTerm;

$terminal->logout(@$_GET['logout']);

if(!$terminal->checkPassword($passwd))
{
		header('WWW-Authenticate: Basic realm="Terminal"');
		header('HTTP/1.0 401 Unauthorized');
}
else
{
$terminal->initVars();
$terminal->buildCommandHistory();
$terminal->buildJavaHistory();
if(!isset($_SESSION['prompt'])):$terminal->formatPrompt(); endif;
$terminal->outputHandle($aliases);
if(isset($_GET['color'])) : $_SESSION['color']=$_GET['color']; endif; 
/*
echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
*/
?>
<!--<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title>Terminal </title>
  <?php $terminal->setTerminalColor(@$_SESSION['color']); ?>
  
  <link rel="stylesheet" type="text/css" href="shell.css" />  
  
  <script type="text/javascript" language="JavaScript">
  var current_line = 0;
  var command_hist = new Array(<?php echo $_SESSION['js_command_hist']; ?>);
  var last = 0;
	
  function key(e) {
    if (!e) var e = window.event;

    if (e.keyCode == 38 && current_line < command_hist.length-1) {
      command_hist[current_line] = document.shell.command.value;
      current_line++;
      document.shell.command.value = command_hist[current_line];
    }

    if (e.keyCode == 40 && current_line > 0) {
      command_hist[current_line] = document.shell.command.value;
      current_line--;
      document.shell.command.value = command_hist[current_line];
    }

  }

function init() {
  document.shell.setAttribute("autocomplete", "off");
  document.shell.output.scrollTop = document.shell.output.scrollHeight;
  document.shell.command.focus();
}

</script>
<script type="text/javascript" src="menu.js"></script>
</head>

<body onload="init()">
<center>
<?php if (empty($_REQUEST['rows'])) $_REQUEST['rows'] = 24; ?>
<table border="0" class="main" cellpadding="0" cellspacing="0">
<tr>
	<td class="head_x" width="2%"><b>&nbsp;X</b></td>
	<td class="head"><?php echo $_SESSION['prompt'].":"."$_SESSION[cwd]"; ?>	</td>
</tr>

<tr><td colspan='2'>
	<table width="100%" cellpadding="0" cellspacing="0" class="ddm1" id="menu1" >
	<tr>
		<td><a class='item1' href='javascript:void(0)'><b>Edit</b></a>
			<div class='section'>
				<a class='item2' href='<?php echo  $_SERVER['PHP_SELF']."?reset=true"?>'>Reset Console</a>
			</div>
		</td>
		<td><a class='item1' href='javascript:void(0)'><b>Colors</b></a>
			<div class='section'>
				<a class='item2' href='<?php echo  $_SERVER['PHP_SELF']."?color=linux"?>'>Linux Default</a>
				<a class='item2' href='<?php echo  $_SERVER['PHP_SELF']."?color=green"?>'>Green on Black</a>
				<a class='item2' href='<?php echo  $_SERVER['PHP_SELF']."?color=gray"?>'>Gray on Blue</a>
				<a class='item2' href='<?php echo  $_SERVER['PHP_SELF']."?color=black"?>'>Black on Green</a>

			</div>
		</td>
		<td><a class='item1' href='javascript:void(0)'><b>Size</b></a>
			<div class='section'>
				<a class='item2' href='<?php echo  $_SERVER['PHP_SELF']."?rows=24"?>'>80x24 (default)</a>
				<a class='item2' href='<?php echo  $_SERVER['PHP_SELF']."?rows=30"?>'>80x30</a>
				<a class='item2' href='<?php echo  $_SERVER['PHP_SELF']."?rows=35"?>'>80x35</a>
				<a class='item2' href='<?php echo  $_SERVER['PHP_SELF']."?rows=40"?>'>80x40</a>

			</div>
		</td>
		<td><a class='item1' href='#'><b>Tools</b></a>
			<div class='section'>
				<a class='item2' href="#">nothing yet</a>
			</div>
		</td>
		<td><a class='item1' href="<?php echo $_SERVER['PHP_SELF']?>"><b>Help</b></a>
			<div class='section'>
				<a class='item2' href="#">nothing yet</a>
			</div>
		</td>
		<td><a class='item1' href="<?php echo $_SERVER['PHP_SELF']."?logout=true"?>"><b>Logout</b></a>
		</td>
	</tr>
	</table>
</td></tr>




<form name="shell" action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
<tr>
	<td colspan='2' nowrap>
	<textarea name="output" readonly="readonly" rows="<?php echo $_REQUEST['rows']; ?>"><?php
		$lines = substr_count($_SESSION['output'], "\n");
		$padding = str_repeat("\n", max(0, $_REQUEST['rows']+1 - $lines));
		echo rtrim($padding . $_SESSION['output']);
	?>
	</textarea>
	<p><font size="-1">
		<?php echo $_SESSION['prompt']."/".str_replace('/', '', strrchr($_SESSION['cwd'], '/')).">"; ?>
		<input class="prompt" name="command" type="text"  size='50' onkeyup="key(event)" tabindex="1">
	</font></p>
	</td>
</tr></form>

</table>
<script type="text/javascript">
var ddm1 = new DropDownMenu1('menu1');
ddm1.init();
</script>
</center>
</body>
</html>
<?php } ?>
