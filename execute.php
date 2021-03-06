<?php

/**
 * @file
 * @author  Mark Elo 
 * @version 1.0
 *
 * @section LICENSE 
 *
 * This program is the property of Mark Elo
 *
 * @section DESCRIPTION
 *
 *  Main Execution Class
 *  Run the code !
 */


/**
 * \brief Execution of code class
 */ 
class executeCode extends dataBaseConnect {  

    private $rs;
	private $con; 
	private $userNum;
	private $fileId; 
	
	/**
	* Establish connection
	*/ 
    function __construct($userNum, $fileId){ 
		parent::__construct(); 	
		$this->con = new MySQLConnect($this->getHostName(), $this->getUserName(), $this->getPassword()); 
		$this->userNum = $userNum;
		$this->fileId = $fileId; 
	}   

	
	/** 
	* Run Code.
	*/
	function run(){ 
		$strsql = "SELECT * FROM codelines WHERE userNum = ".$this->userNum." AND fileNum = ".$this->fileId." ORDER BY lineNum ASC";
		$rs=$this->con->createResultSet($strsql, $this->getDataBaseName());
		// This is the main code loop
		$outFile = $this->userNum."-output.php";
		$fh = fopen($outFile, 'w') or die("can't open file");
		$stringData = "<?php \n";
		fwrite($fh, $stringData);
		while($row = $rs->getRow()){
		    $command = $row['lineCmd'];
			$attribute = $row['lineAttribute'];
			$stringData = $this->processCommand($command, $attribute);
			fwrite($fh, $stringData);
		}
		fclose($fh);
		include($outFile);
		print "<br \>";
		return 1;
    } 
	
	function processCommand($command, $attribute) {
		switch($command)
		{
			case 'REM':
				break;
			case 'FOR':
				$stringData = $this->processFor($attribute);
				break;
			case 'NEXT':
				$stringData = " } \n";
				break;
			case 'IF':
				$stringData = $this->processIf($attribute);
				$stringData = htmlspecialchars_decode($stringData);
				break;
			case 'ELSE':
				$stringData = " } else { \n";
				break;
			case 'ENDIF':
				$stringData = " } \n";
				break;
			case 'PRINT':
				$stringData = $this->processPrint($attribute);
				$stringData = htmlspecialchars_decode($stringData);
				break;
			case 'LET':
				$stringData = $this->processLet($attribute);
				$stringData = htmlspecialchars_decode($stringData);
				break;
			case 'DIM':
				$stringData = $this->processDim($attribute);
				$stringData = htmlspecialchars_decode($stringData);
				break;
			case 'GRAPHBEGIN':
				$stringData = $this->processGraphBegin($attribute);
				break;
			case 'GRAPHEND':
				$stringData = $this->processGraphEnd();
				break;
			case 'LINE':
				$stringData = $this->processLine($attribute);
				break;
			case 'POLYGON':
				$stringData = $this->processPoly($attribute);
				break;
			case 'PAUSE':
				$stringData = "sleep(".$attribute.");";
				break;
			case 'END':
				print "<br \>";
				$stringData = " \n ?> ";
				break;
			default:
				print "Syntax Error on line ".$row['lineNum']."<br \>";;
		}
	return $stringData;
	}
	
	function processFor($attribute) {
		//$attribute = strtoupper($attribute);
		$attribute = str_replace("to", "TO", $attribute);
		$attribute = str_replace("To", "TO", $attribute);
		$attribute = str_replace("tO", "TO", $attribute);
		$variable = (explode("=",$attribute));
		$subAttribute = (explode("TO",$variable[1]));
		$attributeLetElement[0] = str_replace(" ", "", $attributeLetElement[0]);
		$limitVar = "";
		if(!is_numeric($subAttribute[1])) 
			$limitVar="$".str_replace(" ", "", $subAttribute[1])."+"; 
		$stringData = "for(\$".$variable[0]."=".($subAttribute[0])."; \$".$variable[0]."<".$limitVar.($subAttribute[1]+1)."; \$".$variable[0]."++) { \n";
	    return $stringData;
	}
	
	function processIf($attribute) {
		$type = "";
		//print "--> ".$this->get_string_between($attribute, '"', '"')."<br>";
		preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $attribute, $matches);
		//print "<BR>".print_r($matches);
		//print "<BR>-->".$matches[0][0]." ".$matches[0][1]." ".$matches[0][2]." ".$matches[0][3];
		/*
		for($n=0; $n<sizeof($matches[0]); $n++) {
			print "<BR>-->".$matches[0][$n]; 
			}
		print "<BR>DONE"; */
	    $variable = (explode("THEN",$attribute));
		$variable[0] = $this->removeDummyChars($variable[0]);
		if(preg_match('/!=/', $variable[0])) { 
			$type="NOTEQALTO"; $args = (explode("!=",$variable[0])); }
			elseif(preg_match('/&lt;&gt;/', $variable[0])){ 
				$type="NOTEQALTO"; $args = (explode("&lt;&gt;",$variable[0])); }
				elseif(preg_match('/&lt;=/', $variable[0])) { 
					$type="LESSTHANEQAL"; $args = (explode("&lt;=",$variable[0])); }
					elseif(preg_match('/&gt;=/', $variable[0])) { 
						$type="GREATERTHANEQUAL"; $args = (explode("&gt;=",$variable[0])); }
						elseif(preg_match('/&gt;/', $variable[0])) { 
							$type="GREATERTHAN"; $args = (explode("&gt;",$variable[0])); }
							elseif(preg_match('/&lt;/', $variable[0])) { 
								$type="LESSTHAN"; $args = (explode("&lt;",$variable[0])); }
								elseif(preg_match('/=/', $variable[0])) { 
									$type="EQUALTO"; $args = (explode("=",$variable[0])); }

		for($n=0; $n<2; $n++) {
			$args[$n] = trim($args[$n]); 
	        if(preg_match('/\$/',  $args[$n])) {
			    $args[$n] = str_replace("$", "", $args[$n]);
				$isItArrayString = $this->get_string_between($args[$n], "[", "]");
				if($isItArrayString) { 
					$args[$n] = str_replace($isItArrayString."]", "", $args[$n]);
					$args[$n] = "$".str_replace("[", "THISISCHAR[$".$isItArrayString."]", $args[$n]);
					}
				else
					$args[$n] = "$".$args[$n]."THISISCHAR"; 
		    } else {
				if(is_numeric($args[$n]) || preg_match('/"/', $args[$n])) 
					$args[$n] = $args[$n]; 
				else {
				    $isItArrayString = $this->get_string_between($args[$n], "[", "]");
					if($isItArrayString) { 
						$args[$n] = str_replace($isItArrayString."]", "", $args[$n]);
						$args[$n] = str_replace("[", "[$".$isItArrayString."]", $args[$n]);
						}
					$args[$n] = "$".$args[$n]; 
				}
			}
		}
        
		//print "VAR1 -->".$variable[1]."<br>";
        if(preg_match('/ELSE/', $variable[1])) 
			$action = explode("ELSE",trim($variable[1]), 2);
		else
			$action[0]=$variable[1];
		
		$subCommandAttribute = explode(" ",trim($action[0]), 2);
		switch($type) {
			case 'EQUALTO':
				$text = "if(".$args[0]."==".$args[1].") { \n";
				if($variable[1]) {
					$text .= $this->processCommand($subCommandAttribute[0], $subCommandAttribute[1]);
					$text .= "}\n";
					}
				break;
			case 'NOTEQALTO':
				$text = "if(".$args[0]."!=".$args[1].") { \n";
				if($variable[1]) {
					$text .= $this->processCommand($subCommandAttribute[0], $subCommandAttribute[1]);
					$text .= "}\n";
					}
				break;
			case 'GREATERTHAN':
				$text = "if(".$args[0].">".$args[1].") { \n";
				if($variable[1]) {
					$text .= $this->processCommand($subCommandAttribute[0], $subCommandAttribute[1]);
					$text .= "}\n";
					}
				break;
			case 'LESSTHAN':
				$text = "if(".$args[0]."<".$args[1].") { \n";
				if($variable[1]) {
					$text .= $this->processCommand($subCommandAttribute[0], $subCommandAttribute[1]);
					$text .= "}\n";
					}
				break;
			case 'GREATERTHANEQUAL':
				$text = "if(".$args[0].">=".$args[1].") { \n";
				if($variable[1]) {
					$text .= $this->processCommand($subCommandAttribute[0], $subCommandAttribute[1]);
					$text .= "}\n";
					}
				break;
			case 'LESSTHANEQAL':
				$text = "if(".$args[0]."<=".$args[1].") { \n";
				if($variable[1]) {
					$text .= $this->processCommand($subCommandAttribute[0], $subCommandAttribute[1]);
					$text .= "}\n";
					}
				break;
		}
		
		if($action[1]) {
			$subCommandAttribute = explode(" ",trim($action[1]), 2);
		    $text .= "else {";
			$text .= $this->processCommand($subCommandAttribute[0], $subCommandAttribute[1]);
			$text .= "}\n";
			}

	    return $text;
	}
	
	function processPrint($attribute) {
		$attribute = preg_replace('/;(?!(([^"]*"){2})*[^"]*$)/', "!_DUMMY_CHAR_!", $attribute);
		$attributeElement = (explode(";",$attribute));
		return $this->parsAttributes("PRINT", $attributeElement);
	}
	
	function processLet($attribute) {
		$attribute = preg_replace('/;(?!(([^"]*"){2})*[^"]*$)/', "!_DUMMY_CHAR_!", $attribute);
		$attributeElement = (explode(";",$attribute));
		return $this->parsAttributes("LET", $attributeElement);
	}
	
	function processDim($attribute) {
		$attribute = preg_replace('/;(?!(([^"]*"){2})*[^"]*$)/', "!_DUMMY_CHAR_!", $attribute);
		$attribute = str_replace(")", "", $attribute);
		$attributeElement = (explode("(",$attribute));
		return $this->parsAttributes("DIM", $attributeElement);
		return NULL;
	}
	
	function processPoly($attribute) {

		$attributeElement = (explode(",",$attribute));
		$numElements = sizeof($attributeElement);
		$code .= '<polygon points="'.$attributeElement[0].','.$attributeElement[1].' '.$attributeElement[2].','.$attributeElement[3].' '.$attributeElement[4].','.$attributeElement[5].'" style="fill:'.$attributeElement[6].';stroke:red;stroke-width:0" />';
		$text = "print '".$code."';";
		return $text;
		// print '<svg height="210" width="500"><polygon points="200,10 250,190 160,210" style="fill:red;stroke:red;stroke-width:1" /></svg>';
	}
	
	function processGraphBegin($attribute) {

		$attributeElement = (explode(",",$attribute));
		$numElements = sizeof($attributeElement);

		if($attributeElement[0]==0 && $attributeElement[1]==0)
			$code ='</svg>';
		else
			$code = '<svg height="'.$attributeElement[1].'" width="'.$attributeElement[0].'" >';
		
		$text = "print '".$code."'; \n";
		
		return $text;
		// print '<svg height="210" width="500"><polygon points="200,10 250,190 160,210" style="fill:red;stroke:rgb(255,0,0);stroke-width:1" /></svg>';
	}
	
	function processLine($attribute) {

		$attributeElement = (explode(",",$attribute));
		$attributeElementParsed = $this->parsAttributes("LINE", $attributeElement);
		$attributeElementParsedExploded = (explode("!-DELIMITER-!",$attributeElementParsed));
		if(!$attributeElementParsedExploded[4])$attributeElementParsedExploded[4]="green";
		$code = '<line x1="'.$attributeElementParsedExploded[0].'" y1="'.$attributeElementParsedExploded[1].'" x2="'.$attributeElementParsedExploded[2].'" y2="'.$attributeElementParsedExploded[3].'" style="stroke:'.$attributeElementParsedExploded[4].';stroke-width:2" />';
		$text = "print '".$code."'; \n";
		return $text;
		// print '<line x1="0" y1="0" x2="0" y2="200" style="stroke:rgb(255,0,0);stroke-width:2" /></svg>';
	}
	
	function processGraphEnd() {
		$code ='</svg>';
		$text = "print '".$code."'; \n";
		return $text;
	}
	
	function parsAttributes($function, $attributeElement) { 
	
		$numElements = sizeof($attributeElement);
		
		for($i=0; $i<$numElements; $i++) { 
		    //print $attributeElement[$i].$i."<BR>";
		    $lineAtt="";
			if(preg_match('/"/', $attributeElement[$i])) { // if quotes
			    switch($function) {
					case 'PRINT':
						$attributeElement[$i] = $this->removeDummyChars($attributeElement[$i]);
						$text .= "print '".preg_replace('/"/', "", $attributeElement[$i])."'; \n";
						break;
					case 'LET':
						$attributeLetElement = (explode("=",$attributeElement[$i]));
						$attributeLetElement[0] = str_replace("$", "", $attributeLetElement[0]);
						$attributeLetElement[0] = str_replace(" ", "", $attributeLetElement[0]);
						$attributeLetElement[1] = $this->removeDummyChars($attributeLetElement[1]);
						$isItArrayString = $this->get_string_between($attributeLetElement[0], "[", "]");
						if($isItArrayString) {
							$attributeLetElement[0] = str_replace($isItArrayString."]", "", $attributeLetElement[0]);
						    $attributeLetElement[0] = str_replace("[", "THISISCHAR[$".$isItArrayString."]", $attributeLetElement[0]);
							$text .= "$".$attributeLetElement[0]."=".$attributeLetElement[1]."; \n";
							}
						else
							$text .= "$".$attributeLetElement[0]."THISISCHAR=".$attributeLetElement[1]."; \n";
						break;
				}
			} else { 
				$attributeElement[$i] = str_replace("DUMMY_FORWARD_SLASH", "/", $attributeElement[$i]);
				if(preg_match('/\$/', $attributeElement[$i])) { 
					$attributeElement[$i] = str_replace("$", "", $attributeElement[$i]);
						$isItArrayString = $this->get_string_between($attributeElement[$i], "[", "]");
			    		if($isItArrayString) { 
							$attributeElement[$i] = str_replace($isItArrayString."]", "", $attributeElement[$i]);
						    $attributeElement[$i] = str_replace("[", "THISISCHAR[$".$isItArrayString."]", $attributeElement[$i]);
							//$text .= "print '".preg_replace('/"/', "", $attributeElement[0])."'; \n";
							}
						else
							$attributeElement[$i]=$attributeElement[$i]."THISISCHAR";
					$text .= $this->outputString($function, "$".$attributeElement[$i]);
				} else { 
					$found=FALSE;
					$strlen = strlen( $attributeElement[$i]);
					for( $j = 0; $j <= $strlen ; $j++ ) {
						$sub = substr( $attributeElement[$i], $j, 1 );
						if( (ord($sub)>64 && ord($sub)<91) || (ord($sub)>96 && ord($sub)<123)  ) {
							$regex = "/(SIN)|(COS)|(TAN)|(EXP)/";
							$concatStr = substr( $attributeElement[$i], $j, 4 );
							preg_match($regex, $concatStr, $matches);
								if($matches[0]) {
									$j=$j+3;
									$sub = $concatStr;
								} else {
									if($found==TRUE) {  
										$sub = $sub; 
									} else { 
									$sub = "$".$sub;
									$found = TRUE; }
								}
						} else {
							$found=FALSE;
						}
						$lineAtt .= $sub;
					}
				$text .= $this->outputString($function, $lineAtt);
				}
			}
		}	
		return $text;
	}
	
	private function removeDummyChars($attribute) {
		$attribute = str_replace("DUMMY_BACK_SLASH", "\\", $attribute);
		$attribute = preg_replace('/!_DUMMY_CHAR_!/', ";", $attribute);
		$attribute = str_replace("DUMMY_FORWARD_SLASH", "/", $attribute);
		$attribute = str_replace("DUMMY_BACK_SLASH", "\\", $attribute);
		return $attribute;
	}
	
	function outputString($function, $attribute) {
		switch($function) {
			case 'PRINT':
				$text = "print ".$attribute."; \n";
				break;
			case 'LET':
				$text = $attribute."; \n";
				break;
			case 'DIM':
				if(!is_numeric($attribute)) 
					$text = $attribute." = array(); \n";
				break;
			case 'LINE':
				if(is_numeric($attribute)) 
					$text = $attribute."!-DELIMITER-!";
				else
					$text = "'.(".$attribute.").'"."!-DELIMITER-!";
				break;
		}
	return $text;
	}
	
	private function get_string_between($string, $start, $end){
		$string = " ".$string;
		$ini = strpos($string,$start);
		if ($ini == 0) return "";
		$ini += strlen($start);
		$len = strpos($string,$end,$ini) - $ini;
		return substr($string,$ini,$len);
	}
	
	/** 
	* Close
	*/
	
	function closeDb() {
		$this->con = null;
	}
	
}

?>


				
