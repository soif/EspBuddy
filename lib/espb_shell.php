<?php
/*
--------------------------------------------------------------------------------------------------------------------------------------
EspBuddy - EspBuddy Shell class
--------------------------------------------------------------------------------------------------------------------------------------
Copyright (C) 2018  by François Déchery - https://github.com/soif/

You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------------------------------------------------------------------
*/


class EspBuddy_Shell {

	private $fg_colors=array(
		//https://misc.flogisoft.com/bash/tip_colors_and_formatting
		'default'	=> 39,
		'black'		=> 30,
		'red'		=> 31,
		'green'		=> 32,
		'yellow'	=> 33,
		'blue'		=> 34,
		'purple'	=> 35,
		'cyan'		=> 36,
		'grey'		=> 37,
		'grey2'		=> 90,
		'red2'		=> 91,
		'green2'	=> 92,
		'yellow2'	=> 93,
		'blue2'		=> 94,
		'purple2'	=> 95,
		'cyan2'		=> 96,
		'white'		=> 97,
	);

	private $bg_colors=array(
		'default'	=> 49,
		'black'		=> 40,
		'red'		=> 41,
		'green'		=> 42,
		'yellow'	=> 43,
		'blue'		=> 44,
		'purple'	=> 45,
		'cyan'		=> 46,
		'grey'		=> 47,
		'grey2'		=> 100,
		'red2'		=> 101,
		'green2'	=> 102,
		'yellow2'	=> 103,
		'blue2'		=> 104,
		'purple2'	=> 105,
		'cyan2'		=> 106,
		'white'		=> 107,
	);

	private $styles=array(
		'default'		=> 0,
		'bold'			=> 1,
		'dim'			=> 2,
//		'italic'		=> 3,
		'underline'		=> 4,
		'blink'			=> 5,
//		'blink2'		=> 6,
		'reverse'		=> 7,
		'hide'			=> 8,
	);

	/*
	// ----------------------------------------------------------------------------
	public function PrintRed($string, $new_line=true){
		$this->_Print($string, 'red','','',$new_line);
	}
	*/

	// ----------------------------------------------------------------------------
	public function PrintAnswer($string, $new_line=true){
		$this->_Print("--> $string", 'green','','',$new_line);
	}

	// ----------------------------------------------------------------------------
	public function PrintError($string, $new_line=true){
		$this->_Print("$string !", 'white','red','',$new_line);
	}

	// ----------------------------------------------------------------------------
	public function PrintCommand($string, $new_line=true){
		$this->_Print("$string", 'grey2','','',$new_line);
	}

	// ----------------------------------------------------------------------------
	public function EchoStyleCommand(){
		echo $this->GetStyleOpen('grey2');
	}

	// ----------------------------------------------------------------------------
	public function EchoStyleStep(){
		echo $this->GetStyleOpen('blue');
	}

	// ----------------------------------------------------------------------------
	public function EchoStyleClose(){
		echo $this->GetStyleClose();
	}


	// #############################################################################
	// ##### PRIVATE ###############################################################
	// #############################################################################

	// ----------------------------------------------------------------------------
	private function _Print($string, $fg_color='', $bg_color="", $style="", $new_line=true){
		echo $this->_Style($string, $fg_color, $style, $bg_color);
		if($new_line){
			echo "\n";
		}
	}

	// ----------------------------------------------------------------------------
	private function _Style($string, $fg_color='', $style="", $bg_color=""){
		if($fg_color or $style or $bg_color){
			$f=$this->fg_colors[$fg_color]	or $f=$this->fg_colors['default'];
			$b=$this->bg_colors[$bg_color]	or $b=$this->bg_colors['default'];
			$s=$this->styles[$bg_color]		or $s=$this->bg_colors['default'];
			$string		 =	$this->GetStyleOpen($fg_color, $style, $bg_color)
							.$string
							.$this->GetStyleClose();
		}
		return $string;
	}

	// ----------------------------------------------------------------------------
	private function GetStyleOpen($fg_color='', $style="", $bg_color=""){
		if($fg_color or $style or $bg_color){
			$f=$this->fg_colors[$fg_color]	or $f=$this->fg_colors['default'];
			$b=$this->bg_colors[$bg_color]	or $b=$this->bg_colors['default'];
			$s=$this->styles[$bg_color]		or $s=$this->bg_colors['default'];
			return "\033[{$s};{$f};{$b}m";
		}
	}

	// ----------------------------------------------------------------------------
	private function GetStyleClose(){
		return "\033[0m";
	}



}
?>