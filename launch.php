<?php

// Function area

function remove0x($offset)
	{
		if(substr(strtolower($offset), 0, 2) == "0x")
			{
				$offset=substr($offset, 2);
			}
		
		while(substr($offset, 0, 1) == "0")
			{
				$offset=substr($offset, 1);
			}
			
		return $offset;
	}

function printusage()
	{
		echo "Usage: php launch.php ecu.bin dump.ecu [0x_Main_Code] [0x_Variables] [0x_Counter_NLS]\n";
		echo "Example: php launch.php ecu.bin dump.ecu 0x76980 0x170A0\n";
		echo "Example: php launch.php ecu.bin dump.ecu\n";
		echo "Notice: Values inner [] are optional\n\n";
	}

function genOutputname($targetName)
	{
		// Find Dot
		$dotPos=strrpos($targetName, ".");
		if($dotPos < 1)
			{
				$dotPos=strlen($targetName);
				$targetName.=".bin";
			}
		
		// Insert _mod to filename
		$targetName=substr($targetName, 0, $dotPos)."_mod".substr($targetName, $dotPos);

		return $targetName;
	}

function writeResult($filename, $result)
	{
		$open=fopen($filename, "w");
		if(!$open)
			{
				echo "Error: Cannot open ".$filename." to write results!\n";
				return false;
			}
		
		fwrite($open, $result);
		fclose($open);
		
		return true;
	}
	
function bitmask2int($bitmask)
	{
		// Ist ein 0x davor
		if(substr(strtolower($bitmask), 0, 2) == "0x")
			{
				$bitmask=substr($bitmask, 2);
			}
			
		$iTemp=hexdec($bitmask);
		
		$i=0;
		
		while($iTemp > 1)
			{
				$iTemp/=2;
				$i++;
			}
		return $i;
	}
	
function offset2bit($offset)
	{
		$relativeInt=hexdec($offset) - hexdec("FD00");
		return dechex($relativeInt / 2);
	}
	
function getmask($mask)
	{
		if ($mask ==1) return 0;
		if ($mask ==2) return 1;
		if ($mask ==4) return 2;
		if ($mask ==8)  return 3;
		if ($mask ==10) return 4;
		if ($mask ==20) return 5;
		if ($mask ==40) return 6;
		if ($mask ==80) return 7;
		if ($mask ==100) return 8;
		if ($mask ==200) return 9;
		if ($mask ==400) return 10;
		if ($mask ==800) return 11;
		if ($mask ==1000) return 12;
		if ($mask ==2000) return 13;
		if ($mask ==4000) return 14;
		if ($mask ==8000) return 15;
	}

function bitwiseandsum($value)
	{
		global $bin, $counter;
		$firstbyte = substr(dechex(hexdec($value)+hexdec(8000)),4,2);
		$secondbyte = substr(dechex(hexdec($value)+hexdec(8000)),2,2);
		
		$bin[$counter] = hex2raw($firstbyte);
		$counter++;
		$bin[$counter] = hex2raw($secondbyte);
		$counter++;
		return;

	}


function bitwise($value,$bin,$offset)
	{
		global $bin,$counter;
		$firstbyte = substr(dechex($value),-2);
		$secondbyte = substr(dechex($value),-4,-2);
		$bin[$counter] = hex2raw($firstbyte);
		$counter++;
		$bin[$counter] = hex2raw($secondbyte);
		$counter++;
		return;
	}

function bitwisehexdec($value)
	{
		global $bin,$counter;
		$firstbyte = substr(dechex(hexdec($value)),-2);
		$secondbyte = substr(dechex(hexdec($value)),-4,-2);
		
		$bin[$counter] = hex2raw($firstbyte);
		$counter++;
		$bin[$counter] = hex2raw($secondbyte);
		$counter++;
		return;

	}

function raw2hex($raw) 
	{
		$m = @unpack('H*', $raw);
		return $m[1];
	}

function hex2raw($hex)
	{
		return @pack('H*', $hex);
	}

function prepareArray($file)
	{
		// Ist uns ein Array gegeben worden?
		if(!is_array($file))
			{
				return false;
			}
		
		// Welche Zeichen am anfang der Kette werden ignoriert
		$ignore=array(";", "#", "/", "[");
		
		// Bereite Variablen vor
		$return=array();

		for($i=0; $i < count($file); $i++)
			{
				// Ersetze Windows Zeilenumbrüche
				$file[$i]=str_replace("\r", "", $file[$i]);
				
				// Ist das erste Zeichen in der ignore liste?
				if(in_array(substr($file[$i], 0, 1), $ignore))
					{
						continue;
					}
				
				// Hole alle Kommentare heraus
				$tempplode=explode("{", $file[$i]);
				$comments=array();
				$countComments=0;
				
				for($x=1; $x < count($tempplode); $x++)
					{
						$comments[$x - 1]=substr($tempplode[$x], 0, strpos($tempplode[$x], "}"));
						$tempplode[$x]="#COMMENT".$countComments.substr($tempplode[$x], strpos($tempplode[$x], "}"));
						$countComments++;
					}
					
				// Setze Zeile wieder zusammen
				$file[$i]=implode("{", $tempplode);
					
				// Ersetze TAB und Leerzeichen
				$file[$i]=str_replace("\t", "", $file[$i]);
				$file[$i]=str_replace("\x20", "", $file[$i]);
				
				
				// Trenne Zeile in array
				$thisLine=explode(",", $file[$i]);
				
				
				// Hat die Zeile weniger als 10 Spalten dann überspringe diese
				if(count($thisLine) < 10)
					{
						continue;
					}
				
				
				// Erstelle Array und übergebe die Werte
				$thisLine[0]=strtolower($thisLine[0]);
				$return[$thisLine[0]]=array();
				$inKlammern=0;
				
				for($x=1; $x < count($thisLine); $x++)
					{
						// Ersetze Kommentare
						for($z=0; $z < count($comments); $z++)
							{
								$thisLine[$x]=str_replace("#COMMENT".$z, $comments[$z], $thisLine[$x]);
							}
						
						$return[$thisLine[0]][$x - 1]=$thisLine[$x];
					}
			}
		
		return $return;
		
	}

function obn($name)
	{
		global $ecu;
		
		$return=$ecu[$name][1];
		if(substr(strtolower($return), 0, 2) == "0x")
			{
				return substr($return, 2);
			}
		
		return $return;
	}
	
function showashex($bin)
	{
		for($i=0; $i < strlen($bin); $i++)
			{
				echo bin2hex($bin{$i})." ";
				
			}
	}

function findFTOMN($bin)
	{
		global $MemLayout;
		
		$found=array();
		
		for($i=0; $i < strlen($bin); $i++)
			{
				if( ($bin{$i} != "\x05") OR ($bin{$i + 1} == "\x05") OR ($bin{$i + 11} != "\x05") )
					{
						continue;
					}

				if( ($bin{$i + 24} != "\x08") OR ($bin{$i + 25} != "\x05") )			
					{
						continue;
					}
				
				// Erfolgreich gefunden!
				/*
				echo "FOUND: 0x".dechex($i + 22)." ";
				showashex(substr($bin, $i, 12));
				echo "\n";
				*/
				$found[]=dechex($i + 22);
			}
		
		
		// Wurde nichts gefunden so führe eine alternativroutine durch
		if(count($found) < 1)
			{
				for($i=0; $i < strlen($bin); $i++)
					{
						if( ($bin{$i} != "\x05") OR ($bin{$i + 1} == "\x05") OR ($bin{$i + 11} != "\x05") OR ($bin{$i + 12} != "\x07") )
							{
								continue;
							}
		
	
						// Erfolgreich gefunden!
						/*	
						echo "FOUND: 0x".dechex($i + 11)." ";
						showashex(substr($bin, $i, 12));
						echo "\n";
						*/
						$found[]=dechex($i + 11);
					}
			}
			
		// Wurde FTOMN immer noch nicht gefunden
		if( (count($found) < 1) AND ($MemLayout == 512) )
			{
				preg_match("/\xC2\xF4..\x40\x94\x9D\x02\xC2\xF9/s", $bin, $matches, PREG_OFFSET_CAPTURE);
				
				$temp=$matches[count($matches) - 1][1] + 10;
				$found[]="1".lowEndian(bin2hex(substr($bin, $temp, 2)));
			}
		
		return $found;
	}

function lowEndian($value)
	{
		$return="";
		$value=str_replace("\t", "", $value);
		$value=str_replace(" ", "", $value);
		
		$pieces=str_split($value, 2);
		
		for($i=count($pieces); $i > 0; $i-=2)
			{
				$return.=$pieces[$i - 1];
				$return.=$pieces[$i - 2];
			}
		
		return $return;
	}

function isVariableUsed($offset)
	{
		global $ecu;
		$keys=array_keys($ecu);
		remove0x($offset);
		
		for($i=0; $i < count($keys); $i++)
			{
				if(in_array("0x".$offset, $ecu[$keys[$i]]))
					{
						return $keys[$i];
					}
				
			}
		
		return false;
	}

function findHole($bin, $size=256, $from=0, $to=0)
	{
		$return="";
		$ffCount=0;
		$binsize=strlen($bin);
		
		// Wurde ein Falsches oder kein Ende eingetragen
		if( ($to >= $binsize) OR ($to == 0) )
			{
				$to=$binsize - 64;
			}
			
		// Wurde ein Falscher Anfang eingetragen
		if($from < 0)
			{
				$from=0;
			}
		
		for($i=$to, $x=0; $i > $from; $i--)
			{
				if($bin{$i} == "\xFF")
					{
						$ffCount++;
					}
				elseif($ffCount >= $size)
					{
						// Schiebe so weit wie möglich
						$thisOffset=16 - (($i + 16) % 16) + $i + 16;
						$space=$ffCount - ($thisOffset - $i);
						
						if($space > $size)
							{
								$return=$thisOffset;
								break;
							}
							
						$ffCount=0;
						continue;
					}
				else
					{
						$ffCount=0;
					}
				
			}
		
		// Ergebniss
		if($return > 0)
			{
				return $return;
			}
			
		return false;
	
	}
	
function findFreeBool($safesearch=true)
	{
		// Globale Variablen
		global $bin;
		
		// Temporäre ablage
		$hex=bin2hex($bin);
		$result=array();
		
		
		// Suche nach Freien Variablen
		for($i=1; $i < 127; $i++)
			{
				// Wurde eine Sichere Suche gewählt?
				if($safesearch)
					{
						// Preg match nach JNB
						preg_match_all("/9a".dechex($i)."...0/s", $hex, $matches, PREG_OFFSET_CAPTURE);
						
						// Schaue ob es durch 2 teilbar ist
						for($x=0; $x < count($matches); $x++)
							{
								if( $matches[$x][1] % 2 != 0)
									{
										continue 2;
									}
							}
					}
				
				// Preg match nach JB
				preg_match_all("/8a".dechex($i)."...0/s", $hex, $matches, PREG_OFFSET_CAPTURE);
				
				// Schaue ob es durch 2 teilbar ist
				for($x=0; $x < count($matches); $x++)
					{
						if( $matches[$x][1] % 2 != 0)
							{
								continue 2;
							}
					}
					
				// Schreibe das in das Ergebniss rein
				$result[]=dechex($i);
			}
		
		
		// Wurde nichts gefunden?
		if( (count($result) < 1) AND ($safesearch) )
			{
				echo "Cannot find unused variable to inject status flags, i try unsafe search!\n";
				$unsaferesult=findFreeBool(false);
				return $unsaferesult;
			}
		elseif(count($result) < 1)
			{
				echo "Im sorry i cannot find unused variable for status flags!\n";
				die();
			}
			
		// Gebe Freie Adressen aus
		echo "Found usable status flag variable at 0x00FD".dechex(hexdec($result[0]) * 2)."\n";
		return $result[0];
	}

// Existiert str_split
if(!function_exists("str_split"))
	{
		// Bilde str_split nach
		function str_split($string, $ln)
			{
				$return=array();
				for($i=0; $i < strlen($string); $i += $ln)
					{
						$return[]=substr($string, $i, $ln);
					}
					
				return $return;
			}
	}


// Init area


if( ($argv[1] == "") AND (file_exists("ecu.bin")) )
	{
		$argv[1]="ecu.bin";
	}

if( ($argv[2] == "") AND (file_exists("dump.ecu")) )
	{
		$argv[1]="dump.ecu";
	}

if( ($argv[1] == "") OR (!file_exists($argv[1])) OR ($argv[2] == "") OR (!file_exists($argv[2])) )
	{
		printusage();
		die();
	}

// Lese Datei in Variable
$file = file_get_contents($argv[2]);

// Initialize $ecu array from dump.ecu
$ecu=prepareArray(explode("\n", $file));

// Prepare Variables

// Zero unused $argv
for($i=0; $i < 10; $i++)
	{
		if(!isset($argv[$i]))
			{
				$argv[$i]="";
			}
	}

// Wurde kein Variablenoffset für Counter_NLS eingegeben so benutze den Standard Offset
if($argv[5] == "")
	{
		$argv[5]="0x384FF0";
	}

// Filter Counter_NLS
$argv[5]=remove0x($argv[5]);

$tempString=dechex(hexdec($argv[5]) - hexdec("380000"));
$tempString=lowEndian($tempString);
$NLSCOUNTER=str_split($tempString, 2);

if (!$file)
	{
		die('echo you have to run me7info.exe -n yourbin.bin, and then rename it to '.$argv[2].', in the same folder of this file');
	}
	
// FINDING OFFSETS!!!
// FINDING OFFSETS!!!
// FINDING OFFSETS!!!
// FINDING OFFSETS!!!
// FINDING OFFSETS!!!
// FINDING OFFSETS!!!


// Hole tsrldyn
echo "finding tsrldyn...\r\n";

$tsrldyn = obn("tsrldyn");

if ($tsrldyn) 
echo "found: $tsrldyn\r\n";
else
die("fatal error not found tsrlydn");


echo "finding vfil_w...\r\n";

$vfil_w = obn("vfil_w");

if ($vfil_w) 
echo "found: $vfil_w\r\n";
else
die("fatal error not found vfil_w");


echo "finding nmot_w...\r\n";

$nmot_w = obn("nmot_w");

if ($nmot_w) 
echo "found: $nmot_w\r\n";
else
die("fatal error not found nmot_w");



echo "finding wped...\r\n";

$wped = obn("wped");
$dwped = obn("dwped");

if ($wped)
	{
		echo "found: $wped\r\n";
	}
elseif($dwped)
	{
		$wped=strtoupper(dechex(hexdec($dwped) + 2));
		echo "wped not found, using dwped + 2: $wped\r\n";
	}
else
	{
		die("fatal error not found wped");
	}


// Find tmotlin
echo "finding tmotlin...\r\n";
$tmotlin = obn("tmotlin");
$tmot = obn("tmot");

if ($tmotlin)
	{
		echo "found: $tmotlin\r\n";
	}
elseif($tmot)
	{
		$tmotlin = $tmot;
		echo "tmotlin not found, using tmot: ".$tmotlin."\r\n";
	}
else
	{
		die("fatal error not found tmotlin/tmot");
	}



echo "finding B_kuppl (clutch pedal)...\r\n";

$kuppl = obn("b_kuppl");
$kupplmask = bitmask2int($ecu["b_kuppl"][3]);

if ($kuppl) 
echo "found: $kuppl.$kupplmask \r\n";
else
die("fatal error not found kuppl");

echo "finding b_br (brems), brake pedal...\r\n";
$brems = obn("b_br");
$bremsmask = bitmask2int($ecu["b_br"][3]);


if ($brems)
echo "found: $brems.$bremsmask \r\n";
else
die("fatal error not found brems");



// SET START ADDRESS FOR BASE:

$bin = file_get_contents($argv[1]);

if (!$bin)
	{
		die('i cant find any ecu to read or write, put in same folder with name '.$argv[1]);
	}
	
	
// Prüfe ob es ein 29F400 oder 29F800 chip ist
$MemLayout=0;

if( (strlen($bin) / 1024) == "512")
	{
		echo "Memory Layout: 29F400 Found\n";
		$MemLayout=512;
	}
elseif( (strlen($bin) / 1024) == "1024")
	{
		echo "Memory Layout: 29F800 Found\n";
		$MemLayout=1024;
	}
else
	{
		die("Invalid Filesize, possible are 512/1024k, current size is ".(strlen($bin) / 1024)."\n");
	}


// Suche Freie 16Bit Bool Variable
$freeBool=findFreeBool();


// Suche FTOMN

$search=findFTOMN($bin);

// Wurden mehrere Adressen gefunden
if(count($search) > 1)
	{
		echo "FTOMN found: " . implode(", ", $search[0]);
		echo "FTOMN ATTENTION: find multiple FTOMN offsets, will zero 0x".$search[0]."\n";
		echo "\r\nFTOMN IS: ";
		echo bin2hex($bin{hexdec($search[0])});
		
		echo "\r\nFTOMN CHANGED TO 0x00\r\n";
		$bin{hexdec($search[0])} = "\x00";
	}
elseif(count($search) > 0)
	{
		echo "FTOMN found: " . $search[0];
		echo "\r\nFTOMN IS: ";
		echo bin2hex($bin{hexdec($search[0])});
		
		echo "\r\nFTOMN CHANGED TO 0x00\r\n";
		$bin{hexdec($search[0])} = "\x00";
	}
else
	{
		echo "position of FTOMN cannot be found.\n";
	}

// Ende der Suche nach FTOMN


// Handle aus wo der Platz für den Main Code ist
if($argv[3] == "")
	{
		echo "Finding a good space for Main Function..\r\n";
		
		// OLD Function $codecave = strpos($bin,"\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF",479232)+8;
		$codecave=findHole($bin, 256);
		
		// Wurde kein Platz gefunden?
		if(!$codecave)
			{
				echo "cannot find space for Main Function, please input offset by argument!\n";
				die();
			}
	}
else
	{
		echo "Using space given by argument for code cave..\r\n";
		$codecave=hexdec(remove0x($argv[3]));
	}


echo "space located at: 0x" . dechex($codecave)."\n";


// Handle aus wo der Platz für die Konfiguration liegt
if($argv[4] == "")
	{
		echo "Finding a good space for launch control configuration variables..\r\n";

		// OLD Function $launchvars = strpos($bin,"\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF",97700)+17;
		$launchvars=findHole($bin, 32, hexdec("17000"), hexdec("18000"));
		
		// Wurde kein Platz gefunden?
		if(!$launchvars)
			{
				echo "cannot find space for configuration variables, please input offset by argument!\n";
				die();
			}
	}
else
	{
		echo "Using space given by argument for launch control configuration variables..\r\n";
		$launchvars=hexdec(remove0x($argv[4]));
	}

echo "space located at: 0x" . dechex($launchvars)."\n";


// Prüfe ob der Variablen Offset bereits in benutzung ist
$isUsed=isVariableUsed($argv[5]);
if($isUsed)
	{
		echo "ERROR: variable 0x".$argv[5]." is already used by '".$isUsed."'\n";
		die();
	}
	
echo "using 0x".$argv[5]." for NLS Counter variable\n";



// Suche den ub abruf zum injetieren des calls
echo "Finding the offset for call to the code cave..\r\n";
$jump=0;

if($MemLayout == 1024)
	{
		$search=0;
		while ($search=strpos($bin,"\xD7\x40\x06\x02\x03\xF8",$search+1))
			{
				if ($search != 0)
				$jump=$search-4;
			}
	}
elseif($MemLayout == 512)
	{
		// Suche nach Vorkomniss
		preg_match("/\xF0\x49\xF7\xF8..\xF3\xF8/s", $bin, $matches, PREG_OFFSET_CAPTURE);
		
		
		// Hole Letztes Ergebniss
		$jump=$matches[count($matches) - 1][1];
		
		// Addiere Länge
		$jump+=6;
	}


// Wurde was gefunden
if($jump < 1)
	{
		die("cannot find offset for code cave!\n");
	}


echo "call will be located at: 0x" . dechex($jump);



$firstbyte = substr(dechex($codecave+hexdec(800000)),0,2);
$secondbyte = substr(dechex($codecave+hexdec(800000)),4,2);
$thirdbyte = substr(dechex($codecave+hexdec(800000)),2,2);
//echo "$firstbyte, $secondbyte, $thirdbyte";

//we save 2 bytes we will use at the end for go back.

$jumpback = $bin[$jump+2];
$jumpback2 = $bin[$jump+3];


if ($bin[$jump] == "\xDA") {
die('this file have already a code cave here, so we think that it have already the launch control, (or an attempt of it), please try using a original ecu!');
}



$bin[$jump] = "\xDA";
$bin[$jump+1] = hex2raw($firstbyte);
$bin[$jump+2] = hex2raw($secondbyte);
$bin[$jump+3] = hex2raw($thirdbyte);


//echo raw2hex($bin[$jump+2]);

// SET DEFAULT CONFIG FOR LAUNCH CONTROL:

$reeplace = array("\xA6","\x01","\x50","\x46","\x0A","\x00","\xF0","\x55","\xE6","\xA4");

for($i = 0; $i < count($reeplace); $i++)
	{
		$bin[$launchvars+$i] = $reeplace[$i];
	}

$line1 = array("\x9A","\x2B","\x13","\x80","\xF2","\xF4","\x40","\x8E","\xD7","\x00","\x81","\x00","\xF2","\xF9","\x00","\x7E");


//START THE FUN PART!!!

//LINE 1
//LINE 1
//LINE 1
//LINE 1
//LINE 1
//LINE 1
//LINE 1
//LINE 1

echo "\r\n\r\nWriting lines of code\r\n";

$counter = $codecave;

// Translin Threshold Check Line 0 C2 F4 81 C8 D7 00 81 00 C2 F9 A9 6E 40 49 FD 40
$bin[$counter] = "\xC2"; $counter++;
$bin[$counter] = "\xF4"; $counter++;

// Insert tmotlin
bitwiseandsum($tmotlin);

$bin[$counter] = "\xD7"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\x81"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\xC2"; $counter++;
$bin[$counter] = "\xF9"; $counter++;

// Read from Flash
bitwise($launchvars+9, $bin, $counter);

$bin[$counter] = "\x40"; $counter++;
$bin[$counter] = "\x49"; $counter++;
$bin[$counter] = "\xFD"; $counter++;
$bin[$counter] = "\x40"; $counter++;



// Begin LC Function
$bin[$counter] = "\x9A"; $counter++;
$bin[$counter] = hex2raw(offset2bit($kuppl)); $counter++;
$bin[$counter] = "\x13"; $counter++;
$bin[$counter] = hex2raw(dechex($kupplmask)."0"); $counter++;
$bin[$counter] = "\xF2"; $counter++;
$bin[$counter] = "\xF4"; $counter++;
bitwiseandsum($vfil_w);
$bin[$counter] = "\xD7"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\x81"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\xF2"; $counter++;
$bin[$counter] = "\xF9"; $counter++;
bitwise($launchvars,$bin,$counter);

//LINE 2
//LINE 2
//LINE 2
//LINE 2
//LINE 2

$bin[$counter] = "\x40"; $counter++;
$bin[$counter] = "\x49"; $counter++;
$bin[$counter] = "\x9D"; $counter++;
$bin[$counter] = "\x0B"; $counter++;
$bin[$counter] = "\xF2"; $counter++;
$bin[$counter] = "\xF4"; $counter++;
bitwisehexdec($nmot_w);
$bin[$counter] = "\xD7"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\x81"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\xF2"; $counter++;
$bin[$counter] = "\xF9"; $counter++;
bitwise($launchvars+2,$bin,$counter);

//LINE 3

$bin[$counter] = "\x40"; $counter++;
$bin[$counter] = "\x49"; $counter++;
$bin[$counter] = "\xFD"; $counter++;
$bin[$counter] = "\x03"; $counter++;
$bin[$counter] = "\xF7"; $counter++;
$bin[$counter] = "\x8E"; $counter++;
bitwiseandsum($tsrldyn);
$bin[$counter] = "\x0D"; $counter++;
$bin[$counter] = "\x2F"; $counter++;
$bin[$counter] = "\x9A"; $counter++;
$bin[$counter] = hex2raw(offset2bit($kuppl)); $counter++;
$bin[$counter] = "\x29"; $counter++;
$bin[$counter] = hex2raw(dechex($kupplmask)."0"); $counter++;
$bin[$counter] = "\x8A"; $counter++;
$bin[$counter] = hex2raw(offset2bit($brems)); $counter++;

//LINE 4


$bin[$counter] = "\x22"; $counter++;
$bin[$counter] = hex2raw(dechex($bremsmask)."0"); $counter++;
$bin[$counter] = "\xF2"; $counter++;
$bin[$counter] = "\xF4"; $counter++;
bitwisehexdec($nmot_w);
$bin[$counter] = "\xD7"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\x81"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\xF2"; $counter++;
$bin[$counter] = "\xF9"; $counter++;
bitwise($launchvars+6,$bin,$counter);
$bin[$counter] = "\x40"; $counter++;
$bin[$counter] = "\x49"; $counter++;


//LINE 5

$bin[$counter] = "\xFD"; $counter++;
$bin[$counter] = "\x1A"; $counter++;
$bin[$counter] = "\xC2"; $counter++;
$bin[$counter] = "\xF4"; $counter++;
bitwiseandsum($wped);
$bin[$counter] = "\xD7"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\x81"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\xC2"; $counter++;
$bin[$counter] = "\xF9"; $counter++;
bitwise($launchvars+8,$bin,$counter);
$bin[$counter] = "\x40"; $counter++;
$bin[$counter] = "\x49"; $counter++;

//LINE 6..

$bin[$counter] = "\xFD"; $counter++;
$bin[$counter] = "\x12"; $counter++;
$bin[$counter] = "\xD7"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\x38"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\xF2"; $counter++;
$bin[$counter] = "\xF4"; $counter++;

$bin[$counter] = hex2raw($NLSCOUNTER[0]); $counter++;
$bin[$counter] = hex2raw($NLSCOUNTER[1]); $counter++;

$bin[$counter] = "\xD7"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\x81"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\xF2"; $counter++;
$bin[$counter] = "\xF9"; $counter++;

//LINE 7

bitwise($launchvars+4,$bin,$counter);
$bin[$counter] = "\x40"; $counter++;
$bin[$counter] = "\x49"; $counter++;
$bin[$counter] = "\x9D"; $counter++;
$bin[$counter] = "\x11"; $counter++;
$bin[$counter] = "\xF7"; $counter++;
$bin[$counter] = "\x8E"; $counter++;
bitwiseandsum($tsrldyn);
$bin[$counter] = "\x08"; $counter++;
$bin[$counter] = "\x41"; $counter++;
$bin[$counter] = "\xD7"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\x38"; $counter++;

//LINE 8

$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\xF7"; $counter++;
$bin[$counter] = "\xF8"; $counter++;

$bin[$counter] = hex2raw($NLSCOUNTER[0]); $counter++;
$bin[$counter] = hex2raw($NLSCOUNTER[1]); $counter++;

$bin[$counter] = "\x0D"; $counter++;
$bin[$counter] = "\x09"; $counter++;
$bin[$counter] = "\xD7"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\x38"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\xF6"; $counter++;
$bin[$counter] = "\x8F"; $counter++;

$bin[$counter] = hex2raw($NLSCOUNTER[0]); $counter++;
$bin[$counter] = hex2raw($NLSCOUNTER[1]); $counter++;

$bin[$counter] = "\x0D"; $counter++;
$bin[$counter] = "\x04"; $counter++;


//LINE 9

$bin[$counter] = "\xD7"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\x38"; $counter++;
$bin[$counter] = "\x00"; $counter++;
$bin[$counter] = "\xF6"; $counter++;
$bin[$counter] = "\x8E"; $counter++;

$bin[$counter] = hex2raw($NLSCOUNTER[0]); $counter++;
$bin[$counter] = hex2raw($NLSCOUNTER[1]); $counter++;

$bin[$counter] = "\xF3"; $counter++;
$bin[$counter] = "\xF8"; $counter++;
$bin[$counter] = $jumpback; $counter++;
$bin[$counter] = $jumpback2; $counter++;
$bin[$counter] = "\xDB"; $counter++;
$bin[$counter] = "\x00"; $counter++;


// Write Result to File
$targetFilename=genOutputname($argv[1]);
writeResult($targetFilename, $bin);

echo "\r\ncode writed successfully to ".$targetFilename."!!\r\n\r\nREMEMBER TO MAKE CHECKSUMS BEFORE YOU PUT THIS FILE, \nCHECKSUMS ARE NOT CALCULATED ON THIS FILE\n";


















?>
