<?php

/*
--------------------------------------------------------------------------------------------------------------------------------------
EspBuddy - Web Server Auto Index
--------------------------------------------------------------------------------------------------------------------------------------
This Server is NOT secure and should not be exposed to public IPs . Use it on your own LAN only, or secure it!
--------------------------------------------------------------------------------------------------------------------------------------
Copyright (C) 2023  by François Déchery - https://github.com/soif/

EspBuddy is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

EspBuddy is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------------------------------------------------------------------
 Based on the excellent work of @NabiKAZ
 https://gist.github.com/NabiKAZ
 https://gist.github.com/NabiKAZ/91f716faa89aab747317fe09db694be8

*/


// ## Settings ###########################################################################################
$ignore 	= array('.', '..','.DS_Store','.git','.gitignore','.github', '.htaccess', 'index.php', 'icon.php', 'Thumbs.db', 'web.config'); // ignoring these files
$rootname 	= 'Root';
$date_format= 'Y-m-d H:i:s';
$path_espb	= dirname(dirname($_SERVER['SCRIPT_FILENAME'])).'/';
$path_icons	= $path_espb.'media/icons/';
$path_root 	= $_SERVER['DOCUMENT_ROOT'];
$cur_url	= urldecode($_SERVER["REQUEST_URI"]);
$prefs_file	= sys_get_temp_dir(). '/EspBuddy_ServerSession';
$cur_sort	= 'name';
$cur_sortd	= 0;
$dirs		= array();
$files		= array();
$prefs		= array();

## Handle Existing files #####################################################
//directly serves existing file of symlink asis
$my_file = $path_root . $cur_url;
if (file_exists($my_file) and is_file($my_file)) {
	//this makes php builin server to directly serve the file
	return false;
}

## Handle favicon ############################################################
if($cur_url=='/favicon.ico'){
	header('Location: /?icon=espblogo');
	exit;
}

// ## Serves icons ###########################################################################################
if (isset($_GET['icon'])) {
	$e = $_GET['icon'];
	$my_icon=$path_icons.$e.'.png';
	if(!file_exists($my_icon)){
		$my_icon=$path_icons.'file.png';
	}
	header('Cache-control: max-age=2592000');
	header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 2592000));
	header('Content-type: image/png');
	print file_get_contents($my_icon);
	exit;
}

// make $dir and $path
$dir = isset($_GET['dir']) ? $_GET['dir'] : '';
if (strstr($dir, '..')) $dir = '';
$path = "$path_root/";
$dir and $path.="$dir/";

## Handle 404 ################################################################
if (!is_dir($path) || !opendir($path)){
	header('HTTP/1.0 404 Not Found');
	Page1();
	Page2();
	echo '<h3>Directory does not exist!</h3>';
	Page3();
	exit(1);
}

## preferences #######################################################################
if(file_exists($prefs_file)){
	$prefs=json_decode(file_get_contents($prefs_file),true);
}
if(isset($_GET['sort']) and isset($_GET['sortd'])){
	$prefs['sort'][$path]=[$_GET['sort'],intval($_GET['sortd'])];
	file_put_contents($prefs_file,json_encode($prefs));
	exit('saved to: '.$prefs_file);
}
else{
	isset($prefs['sort'][$path]) or $prefs['sort'][$path]=[$cur_sort,$cur_sortd];
}
list($cur_sort,$cur_sortd)=$prefs['sort'][$path];

## Main #######################################################################

// makes $dirs and $files
$h = opendir($path);
while (false !== ($f = readdir($h))) {
	if (in_array($f, $ignore)) continue;
	if(is_link($path . $f)){
		$f_dir=0;
		$real_file=realpath($path.$f);		
		if(is_dir($real_file)){
			$f_dir=1;
			if(preg_match("#^{$path}#",$real_file)){
				$link_dir=str_replace($path,'',$real_file);
			}
			else{
				$link_dir='';
			}	
			$f_url	="?dir=".trim("$dir/" . rawurlencode($link_dir), '/');
		}
		else{
			$f_url=trim("$dir/" . rawurlencode($f), '/');
		}
		$f_link =1;
		$f_to	=readlink($path . $f);
		$f_size	=0;
		$f_time	=@filemtime($path . $f);
		$f_date =date($date_format, $f_time);
		$f_icon	='link';
		$f_type ='__link';
		if($f_dir){
			$f_icon	='link_dir';
			$f_type ='__dir __link';
		}
	}
	else{
		$f_link	=0;
		$f_to	='';
		$f_url	=trim("$dir/" . rawurlencode($f), '/');
		$f_size	=filesize($path . $f);
		$f_time	=filemtime($path . $f);
		$f_date =date($date_format, $f_time);
		$f_icon	=strtolower(pathinfo($path . $f, PATHINFO_EXTENSION));
		$f_type =$f_icon or $f_type ='__z';
		$f_dir	=0;
		if(is_dir($path . $f)){
			$f_dir=1;
			$f_url="?dir=".$f_url;
			$f_size	=0;
			$f_type ='__dir';
			$f_icon	=DirNameToIcon($f);
		}
	}
	$files[] = array(
		'name'	=> $f, 
		'size'	=> $f_size, 
		'time'	=> $f_time, 
		'date'	=> $f_date, 
		'url'	=> $f_url,
		'icon'	=> $f_icon, 
		'link'	=> $f_link, 
		'to' 	=> $f_to, 
		'dir'	=> $f_dir, 
		'type'	=> $f_type, 
	);	
} 

closedir($h);

// makes up url
$up_dir = dirname($dir);
$up_url = ($up_dir != '' && $up_dir != '.') ? '?dir=' . rawurlencode($up_dir) : '?';

//make breadcrumb
$current_dir_name = basename($dir);
$breadcrumb = "/ <a href='?'>$rootname</a> ";
if($dir){
	$path=explode('/',$dir);
	$cp=count($path);
	$breadcrumb .="/ ";
	foreach($path as $p){
		$cp--;
		$current .=$p;
		if($cp){
			$breadcrumb .="<a href='?dir={$current}'>$p</a> / " ;
			$current.="/";
		}
		else{
			$breadcrumb .=$p;
		}	
	}
}
else{
	$breadcrumb .="<span id='rootdir'>($path_root)</span>";	
}

// ---------------------------------------------------------------------------------------------
function DirNameToIcon($name){
	if($name=="Firmwares"){return 'dir_o';}
	if($name=="_COMMON"){return 'dir_g';}
	if(preg_match("#^Settings#",$name)){return 'dir_p';}
	return 'dir';
}

// ---------------------------------------------------------------------------------------------
function Page1($title=''){
	global $rootname,$current_dir_name;
	$title or $current_dir_name == '' ? $title=$rootname : $title=$current_dir_name;
	echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">

<head>
	<meta http-equiv="Content-type" content="text/html; charset=iso-8859-1" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>$title - EspBuddy</title>
	<style type="text/css">
		body {
			font-family: tahoma, verdana, arial;
			font-size: 0.7em;
			color: black;
			margin: 0;
			cursor: default;
			background-color: #f5f5f5;
		}
		#page {
			padding: 5px 15px;
			max-width: 700px;
			position: relative;
		}
		#head{
			padding: 6px 10px;
			font-weight: bold;
			font-size: 1.5em;
			line-height: 32px;
			background-color: 			rgba(100,200,255,0.4);
			border-bottom: 1px solid 	rgba(100,200,255,0.9);
			background: linear-gradient(to bottom,  #d6f9ff 0%,#9ee8fa 100%);
		}
		#head IMG{
			vertical-align: top;
		}
		#head A{
			text-decoration:none;
			color:#000;
		}
		#path{
			margin-top: 10px;
			padding-left: 5px;
		}
		#path strong {
			/* font-family: "Trebuchet MS", tahoma, arial; */
			font-size: 1.3em;
			font-weight: bold;
			color: #202020;
			padding-bottom: 3px;
			padding-right: 10px;
			margin: 0px;
		}
		#path #rootdir{
			padding-left: 20px;
			font-size: 0.8em;
			font-weight: normal;
			color: rgba(0,0,0,0.6);
		}
		#info{
			margin-top: 6px;
			padding-left: 5px;
			color: rgba(0,0,0,0.6);
			font-size: 0.85em;
		}
		.pnav{
			position: absolute;
			right: 0;
			text-align:right;
			padding-right: 18px;			
		}
		#pnav1{
			margin-top: -18px;
		}
		#pnav2{
			margin-top: 6px;
		}
		#page img {
			margin-bottom: -2px;
		}
		table {
			width: 100%;
			margin-top: 10px;
		}
		thead th{
			font-weight: normal;
			text-align: left;
			border-bottom: 1px solid #f0f0f0;
		}
		thead th A{
			color: rgba(0,0,0,0.5);
			cursor: pointer;
		}
		thead th A.active{
			text-decoration: underline;
			color: rgba(0,0,0,0.8);
		}
		thead th A:hover{
			text-decoration: underline;
			color: rgba(0,0,0,0.8);
		}
		TR TD:first-child{
			border-left: 1px solid #f0f0f0;
		}
		TR TD:last-child {
			border-right: 1px solid #f0f0f0;
		}
		TD {
			border-top: 1px solid transparent;
			border-bottom: 1px solid #f0f0f0;
			padding: 3px 4px;
			margin: 0;
			vertical-align: top;
			color: rgba(0,0,0,0.8);
		}
		.td_type {
			text-align: right;
			width: 35px;
			padding-left: 0;
		}
		TR.__link .td_name{
			font-style:italic;
		}

		.icon{
			height:16px;
		}
		.td_name {
			white-space: nowrap;
		}
		tbody .td_to {
			font-style: italic;
			color: rgba(0,0,0,0.5);
		}
		.td_size {
			text-align: right;
			padding-right: 10px;
		}
		.td_date {
			width: 100px;
			white-space: nowrap;
		}
		.td_copy{
			width: 20px;

		}
		.td_copy IMG{
			opacity:0.7;
			cursor: pointer;
		}
		.td_copy IMG:hover{
			opacity:1;
		}
		TR.odd td {
			background-color: #fefefe ;
		}
		TR.even td {
			background-color: #fafafc ;
		}
		TR:hover TD{
			background-color: #eeeeef ;
			border-top-color: rgba(0,0,50,0.15);
			border-bottom-color: rgba(0,0,0,0.05);
			background: linear-gradient(to bottom,  #eee 0%,#f2f2f2 100%);
		}
		.link {
			color: #0066DF;
			cursor: pointer;
		}
		#page a:link {
			color: #0066CC;
		}
		#page a:visited {
			color: #003366;
		}
		#page a:hover {
			text-decoration: none;
		}
		#page a:active {
			color: #9DCC00;
		}
		#foot{
			padding: 20px;
		}
	</style>
EOF;
}

// ---------------------------------------------------------------------------------------------
function Page2(){
	echo <<<EOF
</head>
	<body>
	<div id="head">
		<a href='/' title="Go to the Root Directory">
		<img src="?icon=espblogo">
		EspBuddy Server
		</a>
	</div>
	<div id="page">
EOF;
}

// ---------------------------------------------------------------------------------------------
function Page3(){
	echo <<<EOF
	</div>
	<div id="foot"></div>
</body>
</html>
EOF;
}

Page1();
// ############################################################################################
?>

	<script type="text/javascript">
		var _maxFiles = 100;
		var _files = [];
		var _totalPages = null;
		var _total_size = 0;
		var _curPage = 1;
		var _cur_sort='<?= $cur_sort ?>';
		var _cur_sortd=<?= intval($cur_sortd) ?>;
		var _cur_dir='<?= $dir ?>';
		var _sort_direction = {
			'type': 0,
			'name': 0,
			'to': 0,
			'size': 0,
			'date': 1
		};
		var page = null;
		var info = null;
		var tbl = null;

		function copy2Clip(content){
			const textarea = document.createElement("textarea");
			textarea.textContent = content;
			document.body.appendChild(textarea);
			textarea.select();
			document.execCommand("copy");
			document.body.removeChild(textarea);
			console.log(content);
		}

		function _cpcb(content,e){
			copy2Clip(content);
			var f=e.closest('TR');
			setTimeout(function() {
				f.style.opacity='0.4';
				setTimeout(function() {
					f.style.opacity='1';
				}, 150);
			}, 50);
		}

		function _obj(s) {
			return document.getElementById(s);
		}

		function _getExtension(n) {
			n = n.substr(n.lastIndexOf('.') + 1);
			return n.toLowerCase();
		}

		function _nf(n, p) {
			if (p >= 0) {
				var t = Math.pow(10, p);
				return Math.round(n * t) / t;
			}
		}

		function _formatSize(v, u) {
			if (!u) u = 'B';
			if (v > 1024 && u == 'B') return _formatSize(v / 1024, 'KB');
			if (v > 1024 && u == 'KB') return _formatSize(v / 1024, 'MB');
			if (v > 1024 && u == 'MB') return _formatSize(v / 1024, 'GB');
			return _nf(v, 1) + '&nbsp;' + u;
		}

		function _addFile(name, size, time, date, url, icon, link, to, dir, type) {
			_files[_files.length] = {
				'name': name,
				'size': size,
				'time': time,
				'date': date,
				'type': _getExtension(name) + ','+ icon,
				'url': url,
				'icon': '?icon=' + icon,
				'link': link,
				'to': to,
				'dir': dir,
				'type': type
			};
			if(dir==0){
				_total_size += size;
			}
		}

		function _goNext() {
			_curPage++;
			_buildTable();
		}

		function _goPrev() {
			_curPage--;
			_buildTable();
		}

		function _ordType(l, r) {
			var sort=(l['type'] == r['type']) ? 0 : (l['type'] > r['type'] ? 1 : -1);
			if(sort=='0'){return _ordName(l,r);}
			return sort;
		}

		function _ordName(l, r) {
			var a = l['name'].toLowerCase();
			var b = r['name'].toLowerCase();
			return (a == b) ? 0 : (a > b ? 1 : -1);
		}

		function _ordTo(l, r) {
			var a = l['to'].toLowerCase();
			var b = r['to'].toLowerCase();
			return (a == b) ? 0 : (a > b ? 1 : -1);
		}

		function _ordSize(l, r) {
			var sort= (l['size'] == r['size']) ? 0 : (l['size'] > r['size'] ? 1 : -1);
			if(sort=='0'){return _ordName(l,r);}
			return sort;
		}

		function _ordDate(l, r) {
			var sort=(l['time'] == r['time']) ? 0 : (l['time'] > r['time'] ? 1 : -1);
			if(sort=='0'){return _ordName(l,r);}
			return sort;
		}

		function _sortFiles(c) {
			switch (c) {
				case 'type':
					_files.sort(_ordType);
					break;
				case 'name':
					_files.sort(_ordName);
					break;
				case 'to':
					_files.sort(_ordTo);
					break;
				case 'size':
					_files.sort(_ordSize);
					break;
				case 'date':
					_files.sort(_ordDate);
					break;
			}
			_cur_sort=c;
			_cur_sortd= + _sort_direction[c]; //bool to int
			if (_sort_direction[c]) _files.reverse();

			var _http= new XMLHttpRequest();
			_http.open('GET','?sort='+_cur_sort+'&sortd='+_cur_sortd+'&dir='+_cur_dir);
			_http.send();

			_sort_direction[c] = !_sort_direction[c];
			_sortSetActive('sort_'+c);
			_buildTable();
			return false;
		}

		function _sortSetActive(selected) {
			var types=['sort_type','sort_name','sort_size','sort_date','sort_to'];
			for (var i = 0; i < types.length; i++) {
				_obj(types[i]).classList.remove('active');
				if(types[i] == selected){
					_obj(types[i]).classList.add('active');
				}
			}
		}

		function _info() {
			info = _obj('info');
			info.innerHTML = '<b>'+ _files.length + '</b> items, <b>' + _formatSize(_total_size) + '</b> total.';
		}

		function _buildTable() {
			tbl = _obj('tbody');

			_totalPages = Math.ceil( _files.length  / _maxFiles);
			if (_curPage > _totalPages) {
				_curPage = _totalPages;
				return;
			} else if (_curPage < 1) {
				_curPage = 1;
				return;
			}
			var a = (_curPage - 1) * _maxFiles;
			var b = _curPage * _maxFiles;
			var j = 0;
			var html = '';
			
			var pnav='';
			var opnav= null;
			if (_totalPages > 1) {
				pnav = '<span class="link" onmousedown="_goPrev();return false;">Previous</span> (<span clas="pcount">' + _curPage + '/' + _totalPages + '</span>) <span class="link" onmousedown="_goNext();return false;">Next</span>';
			}
			opnav=_obj('pnav1');
			opnav.innerHTML=pnav;
			opnav=_obj('pnav2');
			opnav.innerHTML=pnav;


			for (var i = a; i < b && i < (_files.length); ++i) {
				var f = _files[i];
				var rc = j++ & 1 ? 'odd' : 'even';
				if(f['dir']==0){
					var copy='<a onclick="_cpcb(\'' + window.location.origin +'/'+ decodeURI(f['url']) +'\',this);return false;" title="Copy URL to Clipboard"><img class="icon" src="?icon=copy" alt="Copy"></a>';
					var tr_type='file';
				}
				else{
					var copy='';
					var tr_type='dir';
				}
				html += '<tr class="' + rc + ' '+tr_type+' '+f['type']+'"><td class="td_type"><img class="icon" src="' + f['icon'] + '" alt="" /></td><td class="td_name"><a href="' + f['url'] + '">' + f['name'] + '</a></td><td class="td_to">';
				if(f['link']==1){
					html +=	"&rarr; "+  f['to'];
				}
				html +='</td><td class="td_size">' + (f['dir'] || f['link'] ? '' : _formatSize(f['size'])) + '</td><td class="td_date">' + f['date'] + '</td><td class="td_copy">'+copy+'</td></tr>';
			}
			tbl.innerHTML = html + '';
		}
		
		/* Init _files[] from php ----------------------------------- */
<?php 
		foreach ($files as $f){ 
			print sprintf("_addFile('%s', %d, %d,'%s','%s','%s', %d, '%s', %d,'%s');\n", 
					addslashes($f['name']), 
					$f['size'], 
					$f['time'], 
					$f['date'],
					addslashes($f['url']), 
					$f['icon'], 
					$f['link'], 
					$f['to'], 
					$f['dir'], 
					$f['type'] 
				);
		}
?>
		window.onload = function() {
			_info();
			_sort_direction[_cur_sort]=_cur_sortd;
			_sortFiles(_cur_sort);
		};
	</script>

<?php Page2(); ?>

		<div id="path">
			<strong><?= $breadcrumb == '' ? $rootname : $breadcrumb ?> </strong> 
			<?= $dir != '' ? '&nbsp; (<a href="' . $up_url . '">Up</a>)' : '' ?>
		</div>
		
		<div id="info"></div>
		
		<div id="pnav1" class='pnav'></div>	
		
		<table cellspacing="0" cellpadding="5" border="0">
		<thead>
			<tr>
				<th class="td_type"><a  onmousedown="return _sortFiles('type');" id="sort_type">Type</a></th>
				<th class="td_name"><a  onmousedown="return _sortFiles('name');" id="sort_name">Name</a></th>
				<th class="td_to">	<a  onmousedown="return _sortFiles('to');" id="sort_to">Target</a></th>
				<th class="td_size"><a  onmousedown="return _sortFiles('size');" id="sort_size">Size</a></th>
				<th class="td_date"><a  onmousedown="return _sortFiles('date');" id="sort_date">Date</a></th>
				<th class="td_copy"></th>
			</tr>
		</thead>
			<tbody id="tbody"></tbody>
		</table>

		<div id="pnav2" class='pnav'></div>	


<?php Page3(); ?>
