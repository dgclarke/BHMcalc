<?PHP
//////////////////////////////////////////////////////////////////////////////////
//STATISTICS
//////////////////////////////////////////////////////////////////////////////////
if(!isset($_SESSION)){session_start();}
$sessid=session_id();
echo "<P STYLE=font-size:12px>Session $sessid</P>";

$PYTHONCMD="MPLCONFIGDIR=/tmp python";
$out=shell_exec("hostname");
if($out=="urania"){
  $WEBDIR="facom/pages/binary-habitability.rs/files/binary-habitabilitygovwk/.Interactive/BHMcalc";
  $DIR="/websites/sitios/$WEBDIR";
}else{
  $WEBDIR="BHMcalc";
  $DIR="/var/www/$WEBDIR";
}

function generateFileList($sessid,$suffix){
    $files=array(
		 "output-$sessid.log",
		 "fulloutput-$sessid.log",
		 "cmd-$sessid.log",
		 "config-$sessid.log",
		 "HZ-$suffix.png","HZ-$suffix.png.txt",
		 "HZ+planet-$suffix.png","HZ+planet-$suffix.png.txt",
		 "HZevol-$suffix.png","HZevol-$suffix.png.txt",
		 "StellarOrbits-$suffix.png","StellarOrbits-$suffix.png.txt",
		 "InsolationPhotonDensity-$suffix.png","InsolationPhotonDensity-$suffix.png.txt",
		 "PeriodEvolution-$suffix.png","PeriodEvolution-$suffix.png.txt",
		 "FluxXUV-$suffix.png","FluxSW-$suffix.png.txt",
		 "RatiosFluxXUV-$suffix.png","RatiosFluxXUV-$suffix.png.txt",
		 "RatiosFluxSW-$suffix.png","IntFXUV-$suffix.png.txt",
		 "MassLoss-$suffix.png","MassLoss-$suffix.png.txt");
    return $files;
}

function selectFunction($name,$selection,$defvalue){
$sel=<<<SELECT
  <select name="$name">
SELECT;
 foreach(array_keys($selection) as $value){
   $option=$selection[$value];
   $selected="";
   if($value=="$defvalue"){$selected="selected";}
   $sel.="<option value='$value' $selected>$option\n";
 }
 $sel.="</select>";
 return $sel;
}

function checkFunction($name,$value){
  $checked="";
  if($value=="on" or $value==1){$checked="checked";}
  $check="<input type='checkbox' name='$name' $checked>";
  return $check;
}


function access($referer){
  global $DIR,$WEBDIR;

  date_default_timezone_set("EST");
  $PhpGlobal["TODAY"]=getdate();
  $PhpGlobal["YEAR"]=$PhpGlobal["TODAY"]['year'];//e.g. 2005
  $PhpGlobal["MONTH"]=100+$PhpGlobal["TODAY"]['mon'];
  $PhpGlobal["MONTH"]=substr($PhpGlobal["MONTH"],1,2);//e.g. 01, 12
  $PhpGlobal["DAY"]=$PhpGlobal["TODAY"]['mday'];//e.g. 12, 31
  $PhpGlobal["DATE"]="$PhpGlobal[DAY]-$PhpGlobal[MONTH]-$PhpGlobal[YEAR]";//e.g. 12-02-2005
  $date=$PhpGlobal["TODAY"]['hours']."-".$PhpGlobal["DATE"];
  $agent=$_SERVER["HTTP_USER_AGENT"];
  $remote=$_SERVER["REMOTE_ADDR"];
  $self=$_SERVER["PHP_SELF"];
  $hitstr="$date**$remote**$referer**$self**$agent\n";
  $logfile="$DIR/access.log";
  if(file_exists($logfile)){
    $fl=fopen($logfile,"a");
  }else{
    $fl=fopen($logfile,"w");
  }
  fwrite($fl,$hitstr);
  fclose($fl);
}

function generateRandomString($length = 10) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, strlen($characters) - 1)];
  }
  return $randomString;
}

//////////////////////////////////////////////////////////////////////////////////
//FOOTER
//////////////////////////////////////////////////////////////////////////////////
echo<<<CONTENT
<HTML>
<BODY>
<H1><A HREF="?">Binary Habitability Calculator</A>
<!--<SUP style='color:red;font-size:18px'>v2.0</SUP>-->
<br/>
<a href=changeslog style=font-size:10px>Changeslog/</a><a href=TODO style=font-size:10px>ToDo</a>
</H1>

<form>
CONTENT;

if(isset($_GET["admin"])){
  echo "<input type='hidden' name='admin' value=1>";
}

//////////////////////////////////////////////////////////////////////////////////
//DEFAULT VALUES
//////////////////////////////////////////////////////////////////////////////////
$randstr=generateRandomString(5);
$Z=0.0;
$FeH=0.0;
$M1=1.0;
$M2=0.5;
$Pbin=10.0;
$e=0.0;
$Mp=1.0;
$ap=1.5;
$ep=0.1;
$tau=1.0;
$tautot=2.0;
$incrit='recent venus';
$outcrit='early mars';
$confname="Configuration $randstr";
$zsvec="ZSVEC_siblings";
$earlywind="trend";
$qsaved=1;
$preconf="0";

//////////////////////////////////////////////////////////////////////////////////
//COMMON
//////////////////////////////////////////////////////////////////////////////////
foreach(array_keys($_GET) as $field){
    $$field=$_GET[$field];
}
foreach(array_keys($_POST) as $field){
    $$field=$_POST[$field];
}

//////////////////////////////////////////////////////////////////////////////////
//REPORT
//////////////////////////////////////////////////////////////////////////////////
if(isset($submit) and !isset($back)){
  /*
  if(!isset($qstring)){
    $qstring=$_SERVER["QUERY_STRING"];
  }
  echo "QSTRING: $qstring<br/>";
  */
  $qstring=$_SERVER["QUERY_STRING"];
  //SAVE CONFIGURATION
  $fc=fopen("tmp/config-$sessid.log","w");
  fwrite($fc,"URL: $qstring\n\n");
  foreach(array_keys($_GET) as $field){
    $$field=$_GET[$field];
    $value=$$field;
    fwrite($fc,"$field=$value\n");
  }
  fclose($fc);

  //CHECK PRECONFIGURATION
  if($preconf!="0"){
    //echo "Configuration loaded: $preconf.<br/>";
    $configuration=file("tmp/conf-$preconf/configuration");
    $i=1;
    $Z=rtrim($configuration[$i++]);
    $M1=rtrim($configuration[$i++]); 
    $M2=rtrim($configuration[$i++]); 
    $e=rtrim($configuration[$i++]);
    $Pbin=rtrim($configuration[$i++]);
    $tau=rtrim($configuration[$i++]);
    $Mp=rtrim($configuration[$i++]);
    $ap=rtrim($configuration[$i++]);
    $tautot=rtrim($configuration[$i++]);
    $qintegration=rtrim($configuration[$i++]);
    $i++;
    $zsvec=rtrim($configuration[$i++]);
    $qchz=rtrim($configuration[$i++]);
    $earlywind=rtrim($configuration[$i++]); 
    $FeH=rtrim($configuration[$i++]);
    $ep=rtrim($configuration[$i++]);
    $incrit=rtrim($configuration[$i++]);
    $outcrit=rtrim($configuration[$i++]);
    $i++;
    $qsaved=rtrim($configuration[$i++]);
    $qstring="?preconf=0&Z=$Z&M1=$M1&M2=$M2&e=$e&Pbin=$Pbin&tau=$tau&Mp=$Mp&ap=$ap&tautot=$tautot&qintegration=$qintegration&zsvec=$zsvec&qchz=$qchz&earlywind=$earlywind&FeH=$FeH&ep=$ep&incrit=$incrit&outcrit=$outcrit&confname=$confname&qsaved=$qsaved";
  }
  
  if(!preg_match("/\w+/",$qintegration)){$qintegration=0;}
  else{$qintegration=1;}
  if(!preg_match("/\w+/",$qchz)){$qchz=0;}
  else{$qchz=1;}
  if($qintegration){$qchz=1;}

  if(!isset($stat) and !isset($back)){access("run");}

  if(!isset($reload) and !isset($load) and !isset($save)){
  $cmd="$PYTHONCMD BHMcalc.py $Z $M1 $M2 $e $Pbin $tau $Mp $ap $tautot $qintegration $sessid $zsvec $qchz $earlywind $FeH $ep \"$incrit\" \"$outcrit\" \"$confname\" $qsaved &> tmp/fulloutput-$sessid.log";
  //echo "<p>$cmd</p>";return;

  exec($cmd,$output,$status); 
  shell_exec("echo '$cmd' > tmp/cmd-$sessid.log");
  $qreload="reload&$qstring";
  }else if(isset($reload)){
    $qreload=$qstring;
  }else if(isset($load)){
    echo "<i>Loading '$confname'...<br/><br/></i>";
    //SAVE DIR
    $savedir=$load;
    //LOADING RESULT
    $parts=array();
    for($i=0;$i<36;$i++){
      $name="parts_$i";
      $parts[$i]=$$name;
    }
    //SUFFIX
    $i=0;
    $md5inp=$parts[$i++];
    $g1=$parts[$i++];$T1=$parts[$i++];$R1=$parts[$i++];$L1=$parts[$i++];
    $Rmin1=$parts[$i++];$Rmax1=$parts[$i++];$Pini1=$parts[$i++];$Prot1=$parts[$i++];
    $lin1=$parts[$i++];$aE1=$parts[$i++];$aHZ1=$parts[$i++];$lout1=$parts[$i++];
    $g2=$parts[$i++];$T2=$parts[$i++];$R2=$parts[$i++];$L2=$parts[$i++];
    $Rmin2=$parts[$i++];$Rmax2=$parts[$i++];$Pini2=$parts[$i++];$Prot2=$parts[$i++];
    $lin2=$parts[$i++];$aE2=$parts[$i++];$aHZ2=$parts[$i++];$lout2=$parts[$i++];
    $abin=$parts[$i++];$acrit=$parts[$i++];$nsync=$parts[$i++];$Psync=$parts[$i++];
    $lin=$parts[$i++];$aE=$parts[$i++];$aHZ=$parts[$i++];$lout=$parts[$i++];
    $tsync1=$parts[$i++];$tsync2=$parts[$i++];
    $Z=$parts[$i++];
    $sessid_original=rtrim(shell_exec("cat $savedir/sessid"));
    $suffix_original=sprintf("%.2f%.2f%.3f%.2f-%s",$M1,$M2,$e,$Pbin,$sessid_original);
    $suffix_new=sprintf("%.2f%.2f%.3f%.2f-%s",$M1,$M2,$e,$Pbin,$sessid);
    //ORIGINAL SESSID
    $files_original=generateFileList($sessid_original,$suffix_original);
    $files_new=generateFileList($sessid,$suffix_new);
    //COPY FILES
    $numfiles=count($files_original);
    for($i=0;$i<$numfiles;$i++){
      $fileori=$files_original[$i];
      $filenew=$files_new[$i];
      //echo "Loading file $savedir/$fileori as tmp/$filenew...<br/>";
      shell_exec("cp -rf $savedir/$fileori tmp/$filenew");
    }
    //QSTRING
    $qstring=preg_replace("/confname=/","confname=Modified+",$qstring);
  }else if(isset($save)){
    echo "<i>Result has been saved as '$confname'...<br/><br/></i>";
    //QSTRING
    $qstring_save=preg_replace("/admin=1&/","",$qstring);
    $qstring_save=preg_replace("/&save/","",$qstring_save);
    //LOAD DATA
    $parts=array();
    for($i=0;$i<36;$i++){
      $name="parts_$i";
      $parts[$i]=$$name;
    }
    //SUFFIX
    $i=0;
    $md5inp=$parts[$i++];
    $g1=$parts[$i++];$T1=$parts[$i++];$R1=$parts[$i++];$L1=$parts[$i++];
    $Rmin1=$parts[$i++];$Rmax1=$parts[$i++];$Pini1=$parts[$i++];$Prot1=$parts[$i++];
    $lin1=$parts[$i++];$aE1=$parts[$i++];$aHZ1=$parts[$i++];$lout1=$parts[$i++];
    $g2=$parts[$i++];$T2=$parts[$i++];$R2=$parts[$i++];$L2=$parts[$i++];
    $Rmin2=$parts[$i++];$Rmax2=$parts[$i++];$Pini2=$parts[$i++];$Prot2=$parts[$i++];
    $lin2=$parts[$i++];$aE2=$parts[$i++];$aHZ2=$parts[$i++];$lout2=$parts[$i++];
    $abin=$parts[$i++];$acrit=$parts[$i++];$nsync=$parts[$i++];$Psync=$parts[$i++];
    $lin=$parts[$i++];$aE=$parts[$i++];$aHZ=$parts[$i++];$lout=$parts[$i++];
    $tsync1=$parts[$i++];$tsync2=$parts[$i++];
    $Z=$parts[$i++];
    $suffix=sprintf("%.2f%.2f%.3f%.2f-%s",$M1,$M2,$e,$Pbin,$sessid);
    $files=generateFileList($sessid,$suffix);
    //SAVE STRING
    $savestr=$md5inp;
    //CHECK IF IT IS ADMIN
    if(!isset($admin)){$savedir="repo/users/$sessid/$savestr";}
    else{$savedir="repo/admin/$savestr";}
    //CREATE DIRECTORY
    shell_exec("mkdir -p $savedir");
    //SAVE QSTRING
    shell_exec("echo '$qstring_save' > $savedir/qstring");
    shell_exec("echo '$sessid' > $savedir/sessid");
    shell_exec("echo '$confname' > $savedir/confname");
    shell_exec("echo '$md5inp' > $savedir/md5in");
    //SAVING FILES
    foreach($files as $file){
      //echo "Saving file $file...<br/>";
      shell_exec("cp -rf tmp/$file $savedir/$file");
    }
  }//END OF SAVE
  
  ////////////////////////////////////////////////////
  //LOAD A RESULT
  ////////////////////////////////////////////////////
  $result=shell_exec("cat tmp/output-$sessid.log");
  
  //ERROR
  if(preg_match("/ERROR/",$result)){
    echo "<P STYLE='color:red'>An error has occurred while executing the program</P>";
    echo "Executing: $cmd<br/>";
    echo "<pre style='background:yellow;padding:10px'>$result</pre>";
    echo "<a href=?back&$qstring>Back</a>";
    return;
  }

  $parts=preg_split("/\s+/",$result);
  echo "<a href=?back&$qstring>Back</a> - ";
  echo "<a href=?$qreload>Reload</a>";
  echo "<P><a href=tmp/fulloutput-$sessid.log target=_blank>Full Output</a> - <a href=tmp/config-$sessid.log target=_blank>Configuration</a> - <a href=tmp/cmd-$sessid.log target=_blank>Command</a></P>";

  //BASIC INFORMATION ON THE SYSTEM
  $Zinp=$Z;
  $i=0;
  $md5inp=$parts[$i++];
  $g1=$parts[$i++];$T1=$parts[$i++];$R1=$parts[$i++];$L1=$parts[$i++];
  $Rmin1=$parts[$i++];$Rmax1=$parts[$i++];$Pini1=$parts[$i++];$Prot1=$parts[$i++];
  $lin1=$parts[$i++];$aE1=$parts[$i++];$aHZ1=$parts[$i++];$lout1=$parts[$i++];
  $g2=$parts[$i++];$T2=$parts[$i++];$R2=$parts[$i++];$L2=$parts[$i++];
  $Rmin2=$parts[$i++];$Rmax2=$parts[$i++];$Pini2=$parts[$i++];$Prot2=$parts[$i++];
  $lin2=$parts[$i++];$aE2=$parts[$i++];$aHZ2=$parts[$i++];$lout2=$parts[$i++];
  $abin=$parts[$i++];$acrit=$parts[$i++];$nsync=$parts[$i++];$Psync=$parts[$i++];
  $lin=$parts[$i++];$aE=$parts[$i++];$aHZ=$parts[$i++];$lout=$parts[$i++];
  $tsync1=$parts[$i++];$tsync2=$parts[$i++];
  $Z=$parts[$i++];

  $q=$M2/$M1;
  /*
  if($q>0){$type="Binary";}
  else{$type="Single star";}
  */
	
  $suffix=sprintf("%.2f%.2f%.3f%.2f-%s",$M1,$M2,$e,$Pbin,$sessid);

  $zcalc="";
  if($Zinp==0){$zcalc="<sup>*Calculated</sup>";}

  $qstring_save=preg_replace("/reload&/","",$qstring);
  for($i=0;$i<36;$i++){$qstring_save.="&parts_$i=".$parts[$i];}
  if(!isset($load)){
    $save_button="<p><a href=\"?$qstring_save&save\" style=background:lightgray;padding:10px;>Save Result</a></p>";
  }else{
    $save_button="";
  }

echo<<<CONTENT
$save_button
<H2>Input properties</H2>
<B>Input signature</b>:$md5inp<br/>
<B>Configuration name</b>:$confname
<p></p>
<table>
  <tr><td>[Fe/H]:</td><td>$FeH</td></tr>
  <tr><td>Z:</td><td>$Z$zcalc</td></tr>
  <tr><td>M<sub>1</sub>:</td><td>$M1 M<sub>Sun</sub></td></tr>
  <tr><td>M<sub>2</sub>:</td><td>$M2 M<sub>Sun</sub></td></tr>
  <tr><td>q:</td><td>$q</td></tr>
  <tr><td>P<sub>bin</sub>:</td><td>$Pbin days</td></tr>
  <tr><td>e:</td><td>$e</td></tr>
  <tr><td>&tau;:</td><td>$tau Gyr</td></tr>
  <tr><td>M<sub>p</sub>:</td><td>$Mp M<sub>Earth</sub></td></tr>
  <tr><td>a<sub>p</sub>:</td><td>$ap AU</td></tr>
  <tr><td>e<sub>p</sub>:</td><td>$ep</td></tr>
</table>

<H2>Binary System Properties</H2>
<table>
  <tr><td>a<sub>bin</sub>:</td><td>$abin AU</td></tr>
  <tr><td>a<sub>crit</sub>:</td><td>$acrit AU</td></tr>
  <tr><td>n<sub>sync</sub>=&Omega;/n:</td><td>$nsync</td></tr>
  <tr><td>P<sub>sync</sub>=n<sub>sync</sub>P<sub>bin</sub></td><td>$Psync</td></tr>
  <tr><td>a<sub>in</sub>:</td><td>$lin AU</td></tr>
  <tr><td>a<sub>HZ</sub>:</td><td>$aHZ AU</td></tr>
  <tr><td>a<sub>out</sub>:</td><td>$lout AU</td></tr>
  <tr><td>t<sub>sync1</sub>:</td><td>$tsync1 Gyr</td></tr>
  <tr><td>t<sub>sync2</sub>:</td><td>$tsync2 Gyr</td></tr>
</table>

<H2>Instantaneous Stellar Properties</H2>
<P>The following properties of the stars as measured at &tau;=$tau
Gyr.</P>
<H3>Main Component</H3>
<table>
  <tr><td>M:</td><td>$M1 M<sub>sun</sub></td></tr>
  <tr><td>g:</td><td>$g1 cm/s<sup>2</sup></td></tr>
  <tr><td>T<sub>eff</sub>:</td><td>$T1 K</td></tr>
  <tr><td>R:</td><td>$R1 R<sub>sun</sub> (Range in &tau;=0.01-$tau Gyr: $Rmin1-$Rmax1 R<sub>sun</sub>)</td></tr>
  <tr><td>L:</td><td>$L1 L<sub>sun</sub></td></tr>
  <tr><td>P<sub>rot</sub>:</td><td>$Prot1 days</td></tr>
  <tr><td>HZ:</td><td>[$lin1,$aE1 ($aHZ1),$lout1] AU</td></tr>
</table>
<H3>Secondary Component</H3>
<table>
  <tr><td>M:</td><td>$M2 M<sub>sun</sub></td></tr>
  <tr><td>g:</td><td>$g2 cm/s<sup>2</sup></td></tr>
  <tr><td>T<sub>eff</sub>:</td><td>$T2 K</td></tr>
  <tr><td>R:</td><td>$R2 R<sub>sun</sub> (Range in &tau;=0.01-$tau Gyr: $Rmin2-$Rmax2 R<sub>sun</sub>)</td></tr>
  <tr><td>L:</td><td>$L2 L<sub>sun</sub></td></tr>
  <tr><td>P<sub>rot</sub>:</td><td>$Prot2 days</td></tr>
  <tr><td>HZ:</td><td>[$lin2,$aE2 ($aHZ2),$lout2] AU</td></tr>
</table>
<H3>Cricumbinary Habitable Zone</H3>
<a href="tmp/HZ-$suffix.png.txt" target="_blank"><img src="tmp/HZ-$suffix.png"></a><br/>
<a href="tmp/HZ+planet-$suffix.png.txt" target="_blank"><img src="tmp/HZ+planet-$suffix.png"></a><br/>
CONTENT;

if($qchz){
$tsys=$parts[$i++];
$lincont=$parts[$i++];$loutcont=$parts[$i++];
$slincont=$parts[$i++];$sloutcont=$parts[$i++];
echo<<<CONTENT
<H3>Continuous Habitable Zone</H3>
<table>
  <tr><td>&tau;<sub>sys</sub>:</td><td>$tsys Gyr</td></tr>
  <tr><td>CHZ binary:</td><td>[$lincont,$loutcont] AU</td></tr>
  <tr><td>CHZ single-primary:</td><td>[$slincont,$sloutcont] AU</td></tr>
</table>
<a href="tmp/HZevol-$suffix.png.txt" target="_blank"><img src="tmp/HZevol-$suffix.png"></a><br/>

Orbits of the stellar components with respect to a planet at the inner
and outer edge of the Continuous Habitable Zone:<br/>

<a href="tmp/StellarOrbits-$suffix.png.txt"
target="_blank"><img src="tmp/StellarOrbits-$suffix.png"></a><br/>

Insolation and Photosynthetic Photon Flux Density (PPFD, 400-1400 nm)
at the inner and outer edge of the continuous habitable zone:<br/>

<a href="tmp/InsolationPhotonDensity-$suffix.png.txt"
target="_blank"><img src="tmp/InsolationPhotonDensity-$suffix.png"></a><br/>

CONTENT;
}
if($qintegration){
$suffix1=sprintf("%.2f",$M1);
$suffix2=sprintf("%.2f",$M2);
echo<<<CONTENT
<H3>Evolution Plots</H3>

Evolution of rotational periods with (solid) and without (dashed)
tidal interaction:<br/>

<a href="tmp/PeriodEvolution-$suffix.png.txt" target="_blank"><img src="tmp/PeriodEvolution-$suffix.png"></a><br/>

Evolution of XUV and stellar wind flux within the continuous habitable
zone in Binary with BHM (solid), no BHM (dash-dotted) and
single-primary (dotted): <br/>

<a href="tmp/FluxXUV-$suffix.png.txt" target="_blank"><img src="tmp/FluxXUV-$suffix.png"></a><br/> 

<a href="tmp/FluxSW-$suffix.png.txt" target="_blank"><img src="tmp/FluxSW-$suffix.png"></a><br/>

Ratio of XUV and stellar wind flux in Binaries with BHM, without BHM
and around single-primary systems:<br/>

<a href="tmp/RatiosFluxXUV-$suffix.png.txt" target="_blank"><img src="tmp/RatiosFluxXUV-$suffix.png"></a><br/> 

<a href="tmp/RatiosFluxSW-$suffix.png.txt" target="_blank"><img src="tmp/RatiosFluxSW-$suffix.png"></a><br/>

Integrated XUV and stellar wind fluxes:<br/>

<a href="tmp/IntFXUV-$suffix.png.txt" target="_blank"><img src="tmp/IntFXUV-$suffix.png"></a><br/>
<a href="tmp/IntFSW-$suffix.png.txt" target="_blank"><img src="tmp/IntFSW-$suffix.png"></a><br/>

Mass-loss as a function of planetary mass at the inner edge of the
continuous habitable zone:<br/>

<a href="tmp/MassLoss-$suffix.png.txt" target="_blank"><img src="tmp/MassLoss-$suffix.png"></a><br/>

<!--DEPRECATED
<img src="tmp/PeriodFit-$suffix1.png"><br/>
<img src="tmp/PeriodFit-$suffix2.png"><br/>
<img src="tmp/AccelerationEvolution-$suffix.png"><br/>
-->

CONTENT;
}

echo<<<CONTENT
$save_button
<a href=?back&$qstring>Back</a> - <a href=?$qreload>Reload</a>
</form>
CONTENT;

 }else{

  //echo "Confname: $confname";
//////////////////////////////////////////////////////////////////////////////////
//INPUT
//////////////////////////////////////////////////////////////////////////////////
   if(!isset($stat) and !isset($back)){access("access");}

   //GLOBAL LIST
   $out=shell_exec("ls -md repo/admin/*");
   $confs=preg_split("/\s*,\s/",$out);
   $preconfs=array();
   foreach($confs as $conf){
     $conf=rtrim($conf);
     $confiname=rtrim(shell_exec("cat $conf/confname"));
     $md5in=rtrim(shell_exec("cat $conf/md5in"));
     $qstring=rtrim(shell_exec("cat $conf/qstring"));
     //echo "Configuration '$conf' ($confiname,$md5in)...<br/>";
     $preconfs_name["$md5in"]="$confiname";
     $preconfs_qstring["$md5in"]="$qstring";
   }
   $global_list="";
   $keys=array_keys($preconfs_name);
   array_multisort($preconfs_name,$keys);
   foreach($keys as $key){
     $qstring=$preconfs_qstring[$key];
     $confiname=$preconfs_name[$key];
     $global_list.="<a href='?load=repo/admin/$key&$qstring'>$confiname</a><br/>";
   }
   if(!preg_match("/\w/",$global_list)){
     $global_list="<i>(No configurations found)</i>";
   }

   //SESSION LIST
   $out=shell_exec("ls -md repo/users/$sessid/*");
   $confs=preg_split("/\s*,\s/",$out);
   $preconfs_name=array();
   $preconfs_qstring=array();
   foreach($confs as $conf){
     $conf=rtrim($conf);
     //echo "User configuration:$conf<br/>";
     $confiname=rtrim(shell_exec("cat $conf/confname"));
     $md5in=rtrim(shell_exec("cat $conf/md5in"));
     $qstring=rtrim(shell_exec("cat $conf/qstring"));
     //echo "$confiname";
     $preconfs_name["$md5in"]="$confiname";
     $preconfs_qstring["$md5in"]="$qstring";
   }
   $this_session="";
   $keys=array_keys($preconfs_name);
   array_multisort($preconfs_name,$keys);
   foreach($keys as $key){
     $qstring=$preconfs_qstring[$key];
     $confiname=$preconfs_name[$key];
     $this_session.="<a href='?load=repo/users/$sessid/$key&$qstring'>$confiname</a><br/>";
   }
   if(!preg_match("/\w/",$this_session)){
     $this_session="<i>(No configurations found)</i>";
   }
   
   $ZSVEC=array("ZSVEC_full"=>"Full (35 metallicities, 0.0001-0.06, [Fe/H] -2.30 to 0.62)",
	       "ZSVEC_coarse"=>"Coarse (10 values, 0.0001-0.06, [Fe/H] -2.30 to 0.62)",
	       "ZSVEC_siblings"=>"Near solar (3 values , 0.01-0.02, [Fe/H] -0.197 to 0.117)");
   $zsel=selectFunction("zsvec",$ZSVEC,$zsvec);

   $EARLYWIND=array("trend"=>"Trending","constant"=>"Constant");
   $ewsel=selectFunction("earlywind",$EARLYWIND,$earlywind);

   $YESNO=array("1"=>"Yes","0"=>"No");
   $savedsel=selectFunction("qsaved",$YESNO,$qsaved);

   $check_qchz=checkFunction("qchz",$qchz);
   $check_qintegration=checkFunction("qintegration",$qintegration);

echo<<<CONTENT
<H2>Input Data</H2>

<H3>Binary System</H3>

<p>Choose here the basic properties of the binary system and a test planet.  You can load precalculated systems in the <a href="#repo">Results Repository</a>.</p>

[Fe/H] : <input type="text" name="FeH" value="$FeH"> or Z : <input type="text" name="Z" value="$Z"><br/> 

<i style="font-size:12px">Metallicity.  Leave in zero either [Fe/H] in Z if you do not know the exact value of this quantities. The valid range of Z with depend on the isochrone set selected in the <a href="#options">options section</a>. It typically range from 0.0001 to 0.06.</i><br/><br/>

M<sub>1</sub> : <input type="text" name="M1" value="$M1"> M<sub>Sun</sub><br/>
<i style="font-size:12px">Mass of the main component</i><br/><br/>

M<sub>2</sub> : <input type="text" name="M2" value="$M2"> M<sub>Sun</sub><br/>
<i style="font-size:12px">Mass of the secondary component.  Leave 0 to compute single star properties</i><br/><br/>

P<sub>bin</sub> : <input type="text" name="Pbin" value="$Pbin"> days<br/>
<i style="font-size:12px">Binary period.</i><br/><br/>

e : <input type="text" name="e" value="$e"><br/>
<i style="font-size:12px">Binary eccentricity.</i><br/><br/>

&tau; : <input type="text" name="tau" value="$tau"> Gyr<br/>
<i style="font-size:12px">Age of the system.  Values must be between 0.01 and 12.5 Gyr</i><br/><br/>

<input type="submit" name="submit" value="submit">

<H3>Properties of the test planet</H3>

M<sub>p</sub> : <input type="text" name="Mp" value="$Mp"> M<sub>Earth</sub><br/>
<i style="font-size:12px">Planetary mass. Values must be between 0.5 and 10.0</i><br/><br/>

a<sub>p</sub> : <input type="text" name="ap" value="$ap"> AU<br/>
<i style="font-size:12px">Semimajor axis of planet</i><br/><br/>

e<sub>p</sub> : <input type="text" name="ep" value="$ep"><br/>
<i style="font-size:12px">Eccentricity of the planet</i><br/><br/>

<input type="submit" name="submit" value="submit">

<H3>Available calculation</H3>

<p>Please indicate here which calculation do you want to perform.  More advanced calculations will takes considerably more time to be performed.</p>

Basic binary properties: <input type="checkbox" name="qbasic" checked><br/>
<i style="font-size:12px">Compute the basic properties of the binaries including instantaneous stellar properties, critical distance, binary semimajor axis, etc.</i><br/><br/>

Binary HZ: <input type="checkbox" name="qhz" checked><br/>
	   <i style="font-size:12px">Compute and plot the Habitable Zone (HZ) of the binary at its present age (see parameter &tau;).</i><br/><br/>

Compute the continuous HZ:$check_qchz<br/>
<i style="font-size:12px">Compute and plot the continuous habitable zone (CHZ).</i><br/><br/>

Integrate properties:$check_qintegration<br/>
<i style="font-size:12px">Calculate the evolution of the interacting properties between the planet and the stellar components (XUV flux, stellar wind, estimated atmospheric mass-loss, etc.)</i><br/><br/>

Total integration time : <input type="text" name="tautot" value="$tautot"> Gyr<br/>
<i style="font-size:12px">Values must be between 0.01 and 12.5 Gyr</i><br/><br/>

<input type="submit" name="submit" value="submit">

<a name="options"></a>
<H3>Options</H3>

Set of isochrones : $zsel<br/>
<i style="font-size:12px">It could reduce considerably the execution time.</i><br/><br/>

Type of early stellar wind : $ewsel
<br/>

<i style="font-size:12px">Observations does not provide us information
about the stellar wind before &tau;<0.7 Gyr.  Some observations
suggest there is a saturation on magnetic activity before that.
Select which type of behavior do you want to simulate.</i><br/><br/>

Do you want to retrieve any previous result: $savedsel<br/>

<i style="font-size:12px">Use this option to load results from a
previous calculation performed with exactly the same
parameters.</i><br/><br/>

Configuration name: 
<input type='text' name='confname' value="$confname"><br/>

<i style="font-size:12px">Save a configuration with a given name. Do
not modify if you don't need this option.</i><br/><br/>

<input type="submit" name="submit" value="submit">

<a name="repo"></a>
<H3>Results Repository</H3>

<H4>Global list</H4>

$global_list

<H4>This session</H4>

$this_session

CONTENT;
 }

//////////////////////////////////////////////////////////////////////////////////
//FOOTER
//////////////////////////////////////////////////////////////////////////////////
if(isset($stat)){
echo<<<CONTENT
<h2>Usage statistics</h2>
<a href="http://astronomia.udea.edu.co/sitios/$WEBDIR/access.log" target="_blank">
Full
</a>
 - 
<a href="http://urania.udea.edu.co/sitios/facom/cgi-bin/stat.php?statfile=$DIR/access.log" target="_blank">
Recalculate
</a> - 
<a href="http://astronomia.udea.edu.co/sitios/$WEBDIR/access.html" target="_blank">
Last
</a>
CONTENT;
}

echo<<<CONTENT
</form>
<hr/>

<i style="font-size:10pt">
Developed by Jorge Zuluaga (2014), Viva la BHM!. <br/> 
Last update: 14-July-2014 (Jorge Zuluaga)<br/>
Please cite: Mason, P. A., Zuluaga, J. I., Clark, J. M., &
Cuartas-Restrepo, P. A. (2013). Rotational Synchronization May Enhance
Habitability for Circumbinary Planets: Kepler Binary Case Studies. The
Astrophysical Journal Letters, 774(2), L26.  </i>

</BODY>
</HTML>
CONTENT;
?>
