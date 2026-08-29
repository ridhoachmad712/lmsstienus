<?php
/*
 * Konfigurasi database tidak boleh disimpan di repository publik.
 * Gunakan environment hosting atau config/local.php yang diabaikan Git.
 */
$siakad_config = array();
$local_config_file = __DIR__.'/local.php';
if (is_file($local_config_file)) {
	$loaded_config = require $local_config_file;
	if (is_array($loaded_config)) {
		$siakad_config = $loaded_config;
	}
}

function siakad_config($environment_key, $local_key, $default = '')
{
	global $siakad_config;
	$environment_value = getenv($environment_key);
	if ($environment_value !== false && $environment_value !== '') {
		return $environment_value;
	}

	return isset($siakad_config[$local_key]) && $siakad_config[$local_key] !== ''
		? $siakad_config[$local_key]
		: $default;
}

$db_host = siakad_config('SIAKAD_DB_HOST', 'db_host', '127.0.0.1');
$db_port = (int) siakad_config('SIAKAD_DB_PORT', 'db_port', 3306);
$db_name = siakad_config('SIAKAD_DB_NAME', 'db_name');
$db_user = siakad_config('SIAKAD_DB_USER', 'db_user');
$db_password = siakad_config('SIAKAD_DB_PASSWORD', 'db_password');

if (!$db_name || !$db_user) {
	http_response_code(503);
	die('Konfigurasi database SIAKAD belum lengkap. Hubungi administrator.');
}

$koneksi = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);
if (!$koneksi) {
	error_log('Koneksi database SIAKAD gagal: '.mysqli_connect_error());
	http_response_code(503);
	die('SIAKAD sementara tidak dapat terhubung ke database.');
}

mysqli_set_charset($koneksi, 'utf8mb4');
date_default_timezone_set(siakad_config('SIAKAD_TIMEZONE', 'timezone', 'Asia/Makassar'));
function time_since($original)
{
	$chunks = array(
		array(60 * 60 * 24 * 365, 'Tahun'),
		array(60 * 60 * 24 * 30, 'bulan'),
		array(60 * 60 * 24 * 7, 'minggu'),
		array(60 * 60 * 24, 'hari'),
		array(60 * 60, 'jam'),
		array(60, 'menit'),
	);

	$today = time();
	$since = $today - $original;

	if ($since > 604800)
	{
		$print = date("M jS" , $original);
		if ($since > 31536000)
		{
			$print .= ", " . date("Y", $original);
		}
		return $print;
	}
	for ($i = 0, $j = count($chunks); $i < $j; $i++)
	{
		$seconds = $chunks[$i][0];
		$name = $chunks[$i][1];

		if (($count = floor($since / $seconds)) != 0)
			break;
	}

	$print = ($count == 1) ? '1 ' . $name : "$count {$name}";
	return $print . ' yang lalu';
}


$ip      = $_SERVER['REMOTE_ADDR'];
$user_agent     =   $_SERVER['HTTP_USER_AGENT'];
function getOS() { 
	global $user_agent;
	$os_platform    =   "Unknown";
	$os_array       =   array(
		'/windows nt 10/i'     =>  'Windows 10',
		'/windows nt 6.3/i'     =>  'Windows 8.1',
		'/windows nt 6.2/i'     =>  'Windows 8',
		'/windows nt 6.1/i'     =>  'Windows 7',
		'/windows nt 6.0/i'     =>  'Windows Vista',
		'/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
		'/windows nt 5.1/i'     =>  'Windows XP',
		'/windows xp/i'         =>  'Windows XP',
		'/windows nt 5.0/i'     =>  'Windows 2000',
		'/windows me/i'         =>  'Windows ME',
		'/win98/i'              =>  'Windows 98',
		'/win95/i'              =>  'Windows 95',
		'/win16/i'              =>  'Windows 3.11',
		'/macintosh|mac os x/i' =>  'Mac OS X',
		'/mac_powerpc/i'        =>  'Mac OS 9',
		'/linux/i'              =>  'Linux',
		'/ubuntu/i'             =>  'Ubuntu',
		'/iphone/i'             =>  'iPhone',
		'/ipod/i'               =>  'iPod',
		'/ipad/i'               =>  'iPad',
		'/android/i'            =>  'Android',
		'/blackberry/i'         =>  'BlackBerry',
		'/webos/i'              =>  'Mobile'
	);

	foreach ($os_array as $regex => $value) { 
		if (preg_match($regex, $user_agent)) {
			$os_platform    =   $value;
		}
	}   
	return $os_platform;
}

function getBrowser() {
	global $user_agent;
	$browser        =   "Unknown";
	$browser_array  =   array(
		'/msie/i'       =>  'Explorer',
		'/firefox/i'    =>  'Firefox',
		'/safari/i'     =>  'Safari',
		'/chrome/i'     =>  'Chrome',
		'/opera/i'      =>  'Opera',
		'/netscape/i'   =>  'Netscape',
		'/maxthon/i'    =>  'Maxthon',
		'/konqueror/i'  =>  'Konqueror',
		'/mobile/i'     =>  'Handheld'
	);

	foreach ($browser_array as $regex => $value) { 
		if (preg_match($regex, $user_agent)) {
			$browser    =   $value;
		}
	}
	return $browser;
}

$user_os        =   getOS();
$user_browser   =   getBrowser();


 // finally get the correct version number
$known = array('Version', $user_browser, 'other');
$pattern = '#(?<browser>' . join('|', $known) .
')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
if (!preg_match_all($pattern, $user_agent, $matches)) {
		        // we have no matching number just continue
}

$i = count($matches['browser']);
if ($i != 1) {
		        //we will have two since we are not using 'other' argument yet
		        //see if version is before or after the name
	if (strripos($user_agent,"Version") < strripos($user_agent,$user_browser)){
		$version= $matches['version'][0];
	}
	else {
		$version= $matches['version'][1];
	}
}
else {
	$version= $matches['version'][0];
}

function get_client_ip() {
	$ipaddress = '';
	if (getenv('HTTP_CLIENT_IP'))
		$ipaddress = getenv('HTTP_CLIENT_IP');
	else if(getenv('HTTP_X_FORWARDED_FOR'))
		$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	else if(getenv('HTTP_X_FORWARDED'))
		$ipaddress = getenv('HTTP_X_FORWARDED');
	else if(getenv('HTTP_FORWARDED_FOR'))
		$ipaddress = getenv('HTTP_FORWARDED_FOR');
	else if(getenv('HTTP_FORWARDED'))
		$ipaddress = getenv('HTTP_FORWARDED');
	else if(getenv('REMOTE_ADDR'))
		$ipaddress = getenv('REMOTE_ADDR');
	else
		$ipaddress = 'IP tidak dikenali';
	return $ipaddress;
}

//menampilkan ip address menggunakan function $_SERVER
function get_client_ip_2() {
	$ipaddress = '';
	if (isset($_SERVER['HTTP_CLIENT_IP']))
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	else if(isset($_SERVER['HTTP_X_FORWARDED']))
		$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
		$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	else if(isset($_SERVER['HTTP_FORWARDED']))
		$ipaddress = $_SERVER['HTTP_FORWARDED'];
	else if(isset($_SERVER['REMOTE_ADDR']))
		$ipaddress = $_SERVER['REMOTE_ADDR'];
	else
		$ipaddress = 'IP tidak dikenali';
	return $ipaddress;
}
?>
