<?php
/*
Name  : php-vlc-control.php (linux php vlc control)
Author: lala (at) linuxor (dot) sk
Date  : 2012

File  : vlc.php


Changes:
2014-07-01 - add function delete()

2014-07-29 - add parameter "directory" to function delete()
2014-07-29 - refactoring

2015-11-19 - change function background() to support chroot environments (when vlc daemon is running in chroot)
2015-11-19 - change function showversion() to support chroot environments (when vlc daemon is running in chroot)
*/


/*
Check if process identified by PID ($pid) is running
Return values:
- 0   - process is not running
- 1   - process is running
*/
function isrunning($pid){

		$escape_pid = escapeshellarg($pid);
		$escape_pid = str_replace ("'", "", $escape_pid);

		exec("/bin/ps $escape_pid", $ProcessState);
		if (count($ProcessState) >= 2)
				return 1;
		else
				return 0;
}


/*
Run vlc-wrapper command with arguments ($arg) in background
Return values:
- PID   - command was successfully started
- 1     - command failed to start
*/
function background($arg){

		$escape_arg = escapeshellarg($arg);
		$escape_arg = str_replace ("'", "", $escape_arg);

		$parts = explode(";",$escape_arg);
		$escape_arg = $parts['0'];

		// Old way - without chroot environment
		// $PID = shell_exec("nohup /usr/bin/vlc-wrapper $escape_arg > /dev/null & echo $!");

		// New way - with chroot environment
		$PID = shell_exec("/usr/bin/sudo /usr/sbin/chroot /var/chroot/deb7 /bin/su -c 'cd /var/www/;/usr/bin/nohup /usr/bin/vlc-wrapper $escape_arg > /dev/null & echo $!' www-data");

		// check if VLC is running
		exec("/bin/ps $PID", $ProcessState);
		if (count($ProcessState) >= 2)
				/* command was successfully started */
				return($PID);
		else
				/* command failed to start */
				return 1;
}


/*
Stop process identified by PID ($pid)
Return values:
- 1     - process (PID) was successfully stopped
- 1     - process (PID) do not exists = was stopped earlier ;-)
- 0     - process (PID) was not stopped
- 0     - process (PID) is not a vlc process
*/
function stop($pid){

		$escape_pid = escapeshellarg($pid);
		$escape_pid = str_replace ("'", "", $escape_pid);

		if ( trim(exec("/bin/ps -p $escape_pid --no-headers -o comm")) != trim("vlc"))
				/* process (PID) is not a vlc process */
				return 0;

		if (isrunning($escape_pid))
				{
				exec("/bin/kill -KILL $escape_pid");
				sleep (1);
				if (!isrunning($escape_pid))
						/* process (PID) was successfully stopped */
						return 1;
				else
						/* process (PID) was not stopped */
						return 0;
				}
		else
				/* process (PID) do not exists */
				return 0;
}


/*
Stop all VLC processes
Return values:
- 1     - all VLC processes was successfully stopped
- PIDs  - PIDs of VLC processes that was not successfully stopped
*/
function stopall(){

		exec ("/usr/bin/killall vlc");
		sleep(1);
		exec ("/bin/ps --no-headers -C vlc -o pid", $output);
		if (count($output)>0)
		{
				foreach ($output as $value)
				{
				echo "$value<br>";
				}
		}
		else
				return 1;
}


/*
Delete recorded files
Return values:
- 0     - file cannot be deleted
- 1     - file does not exist
*/
function delete($directory,$filename){

		if ( isset($_GET['directory']) && is_string($_GET['directory']) && $_GET['directory']!="" )
		{
				switch ($_GET['directory'])
				{
						case "signage-media":
						$directory = $_GET['directory'];
						break;

						case "portal-media":
						$directory = $_GET['directory'];
						break;

						case "portal-upload":
						$directory = $_GET['directory'];
						break;

						case "signage-record":
						$directory = $_GET['directory'];
						break;

						default:
						echo "DIRECTORY name is invalid !!!";
						exit;
				}
		}
		else
		{
				echo "DIRECTORY is not specified !!!";
				exit;
		}

		if (isset($_GET['filename']) && is_string($_GET['filename']) && $_GET['filename']!="" )
				$filename = $_GET['filename'];
		else
		{
				echo "FILENAME is not specified !!!";
				exit;
		}

		$escape_filename = escapeshellcmd($filename);
		$escape_filename = str_replace ("..", "", $escape_filename);
		$escape_filename = str_replace ("/", "", $escape_filename);

		$escape_directory = escapeshellcmd($directory);
		$escape_directory = str_replace ("..", "", $escape_directory);
		$escape_directory = str_replace ("/", "", $escape_directory);

		$dir = "/var/www/";
		$fd  = "$dir" . "$escape_directory" . "/" . "$escape_filename";

		if ( file_exists($fd) )
		{
			if ( !unlink($fd) )
			{
				return 0;
			}
			else
			{
				return 1;
			}
		}
		else
		{
			return 1;
		}
}


/*
Show all VLC processes
Return values:
- INFO  - INFO about all running VLC processes
- 0     - No VLC process is running
*/
function showall(){

		exec ("/bin/ps --no-headers -C vlc -o pid -o args", $output);
		if (count($output)>0)
		{
				foreach ($output as $value)
				{
				echo "$value<br>";
				}
		}
		else
				return 0;
}


/*
Show VLC version
Return values:
- VERSION - show VLC version
- 1       - command failed to execute
*/
function showversion(){

		// Old way - without chroot environment
		// $VERSION = shell_exec("/usr/bin/vlc-wrapper --version | /usr/bin/head -n 1 | /usr/bin/awk '{print $3}'");		

		// New way - with chroot environment
		$VERSION = shell_exec("/usr/bin/sudo /usr/sbin/chroot /var/chroot/deb7 /bin/su -c '/usr/bin/vlc-wrapper --version' | /usr/bin/head -n 1 | /usr/bin/awk '{print $3}'");

		if (!is_null($VERSION))
				return $VERSION;
		else
				return 0;
}



/*
MAIN LOOP
*/

if (isset($_GET['command']) && is_string($_GET['command']))
		$command = $_GET['command'];

switch ($command)
{
		case "start":

				if (isset($_GET['arguments']) && is_string($_GET['arguments']) && $_GET['arguments']!="" )
						$arguments = $_GET['arguments'];
				else
				{
						echo "ARGUMENTS not specified !!!";
						exit;
				}

		echo background($arguments);
		break;


		case "stop":

				if (isset($_GET['pid']) && is_string($_GET['pid']) && $_GET['pid']!="")
						$pid = $_GET['pid'];
				else
				{
						echo "PID not specified !!!";
						exit;
				}

		echo stop($pid);
		break;

		case "stopall":
		echo stopall();
		break;

		case "delete":
		echo delete($directory,$filename);
		break;

		case "isrunning":

				if (isset($_GET['pid']) && is_string($_GET['pid']) && $_GET['pid']!="")
						$pid = $_GET['pid'];
				else
				{
						echo "PID not specified !!!";
						exit;
				}

		echo isrunning($pid);
		break;

		case "showall":
		echo showall();
		break;

		case "showversion":
		echo showversion();
		break;

		default:
		echo "COMMAND is invalid or empty !!!";
		break;
}


?> 
