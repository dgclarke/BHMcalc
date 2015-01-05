<?PHP
/*
###################################################
#  ____  _    _ __  __           _      
# |  _ \| |  | |  \/  |         | |     
# | |_) | |__| | \  / | ___ __ _| | ___ 
# |  _ <|  __  | |\/| |/ __/ _` | |/ __|
# | |_) | |  | | |  | | (_| (_| | | (__ 
# |____/|_|  |_|_|  |_|\___\__,_|_|\___|
# v2.0
###################################################
# 2014 [)] Jorge I. Zuluaga, Viva la BHM!
###################################################
# Web Interface
###################################################
*/
?>
<?PHP
//////////////////////////////////////////////////////////////
//GLOBAL VARIABLES
//////////////////////////////////////////////////////////////
$CONTENT="";
$SERVER=shell_exec("hostname");

//==============================
//GETTING LOCATION
//==============================
if(!isset($RELATIVE)){$RELATIVE=".";}
$dir=rtrim(shell_exec("cd $RELATIVE;pwd"));
$parts=preg_split("/\//","$dir");
$BHMDIR=$parts[count($parts)-1];
$ROOTDIR=preg_replace("/$BHMDIR/","",rtrim(shell_exec("cd $RELATIVE;pwd")));
$wDIR="/$BHMDIR/";
$DIR=$ROOTDIR.$wDIR;
//echo "$ROOTDIR,$DIR,$wDIR<br/>";

//OTHER DIRECTORIES
$wSYSDIR=$wDIR."sys/";
$SYSDIR=$ROOTDIR.$wSYSDIR;

$wTMPDIR="tmp/";
$TMPDIR=$ROOTDIR.$wDIR.$wTMPDIR;

$wOBJSDIR="objs/";
$OBJSDIR=$ROOTDIR.$wDIR.$wOBJSDIR;

$wLINKDIR="links/";
$LINKDIR=$ROOTDIR.$wDIR.$wLINKDIR;

$wLOGDIR="logs/";
$LOGDIR=$ROOTDIR.$wDIR.$wLOGDIR;

//PYTHON COMMAND
$PYTHONCMD="PYTHONPATH=. MPLCONFIGDIR=$TMPDIR python";

//GET VARIABLES
foreach(array_keys($_GET) as $field){
    $$field=$_GET[$field];
}
foreach(array_keys($_POST) as $field){
    $$field=$_POST[$field];
}
$GETSTR=print_r($_GET,true);
$POSTSTR=print_r($_POST,true);
if(isset($VERBOSE)){$VERBOSE=1;}
else{$VERBOSE=0;}

$wCSSFILE="web/BHM.css";

$CSSFILE=$DIR."web/BHM.css";

//QUERY STRING
$QUERY_STRING=$_SERVER["QUERY_STRING"];

//QUERYSTRING FOR CONFIGURATION
$PARSE_STRING="";

$MODELS=array("'BCA98'"=>"BCA98",
	      "'PARSEC'"=>"PARSEC",
	      "'BASTI'"=>"BASTI",
	      "'YZVAR'"=>"YZVAR");

$ROTMODELS=array("'Chaboyer'"=>"Chaboyer (2011)",
		 "'Kawaler'"=>"Kawaler (1988)",
		 "'Matt'"=>"Matt (2012)");

$HZINMODELS=array("'recent venus'"=>"Recent Venus",
		  "'runaway greenhouse'"=>"Runaway Greenhouse",
		  "'moist greenhouse'"=>"Moist Greenhouse");

$HZOUTMODELS=array("'early mars'"=>"Early Mars",
		   "'maximum greenhouse'"=>"Maximum Greenhouse");

$REFOBJS=array("'Earth'"=>"Earth",
	       "'Saturn'"=>"Saturn");

//////////////////////////////////////////////////////////////
//DATE
//////////////////////////////////////////////////////////////
date_default_timezone_set("EST");
$TODAY=getdate();
$YEAR=$TODAY['year'];//e.g. 2005
$MONTH=100+$TODAY['mon'];
$MONTH=substr($MONTH,1,2);//e.g. 01, 12
$DAY=$TODAY['mday'];//e.g. 12, 31
$DATE="$DAY-$MONTH-$YEAR";//e.g. 12-02-2005
$TIME=$TODAY['hours'].":".$TODAY['minutes'].":".$TODAY['seconds'];
$DATETIME=$DATE."-".$TIME;

//////////////////////////////////////////////////////////////
//CSS
//////////////////////////////////////////////////////////////
if(!file_exists($CSSFILE) or isset($GENCSS)){
  include_once($DIR."web/BHMcss.php");
  $fc=fopen($CSSFILE,"w");
  fwrite($fc,$CSS);
  fclose($fc);
}

//////////////////////////////////////////////////////////////
//SESSION ID
//////////////////////////////////////////////////////////////
if(!isset($_SESSION)){session_start();}
$SESSID=session_id();
$wSESSDIR=$wSYSDIR."$SESSID/";
$SESSDIR=$ROOTDIR.$wSESSDIR;
$SESSIONDIR=$SESSDIR;
if(!is_dir($SESSDIR)){
  $SESSIONDIR=$SESSDIR;
  $wSESSDIR=$wSYSDIR."template/";
  $SESSDIR=$ROOTDIR.$wSESSDIR;
}

//////////////////////////////////////////////////////////////
//ROUTINES
//////////////////////////////////////////////////////////////
function mainHeader($refresh="",$options="?")
{
  global $CSS;
  $refreshcode="";
  if(preg_match("/\d+/",$refresh)){
    $refreshcode="<meta http-equiv='refresh' content='$refresh;URL=$options'>";
  }

$HEADER=<<<HEADER
<head>
  $refreshcode
  <script src="web/jquery.js"></script>
  <script src="web/md5.js"></script>
  <script src="web/BHM.js"></script>
  <script src="web/tabber.js"></script>
  <script>
function changePlanetMorb(){
  Morb=parseFloat($("input[name=star1_M]").val())+parseFloat($("input[name=star2_M]").val());
  $("input[name=planet_Morb]").attr("value",Morb);
}
 </script>
  <link rel="stylesheet" type="text/css" href="web/BHM.css">
  <script type="text/javascript">
  //setInterval("refreshiFrames()",2000);  
  </script>
</head>
HEADER;
 return $HEADER;
}

function selectFunction($name,$selection,$defvalue,$options=""){
$sel=<<<SELECT
  <select name="$name" $options>
SELECT;
 foreach(array_keys($selection) as $value){
   $option=$selection[$value];
   $selected="";
   if($value=="$defvalue"){$selected="selected";}
   $sel.="<option value=\"$value\" $selected>$option\n";
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

function accessLog($action="browse"){
  //phpinfo();
  $datetime=$GLOBALS["DATETIME"];
  $agent=$_SERVER["HTTP_USER_AGENT"];
  $remote=$_SERVER["REMOTE_ADDR"];
  $self=$_SERVER["PHP_SELF"];
  $parts=preg_split("/\?/",$_SERVER["REQUEST_URI"]);
  $referer=$parts[0];
  $sessid=$GLOBALS["SESSID"];
  $hitstr="$datetime**$sessid**$remote**$referer**$self**$agent**$action\n";
  $logfile="$GLOBALS[LOGDIR]/access.log";
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

function loadConfiguration($file,$prefix)
{
  $conf=parse_ini_file($file);
  foreach(array_keys($conf) as $key){
    $varname="${prefix}_$key";
    $value=$conf[$key];
    $GLOBALS["PARSE_STRING"].="$varname=$value&";
    $GLOBALS[$varname]=$value;
  }
}

function saveConfiguration($dir,$qstring)
{
  $fields=preg_split("/&/",$qstring);

  $data=array();
  foreach($fields as $field){
    if(!preg_match("/_/",$field)){continue;}
    $parts=preg_split("/_/",$field);
    $module=$parts[0];
    preg_match("/${module}_(.+)=(.+)/",$field,$matches);
    $key=$matches[1];
    $value=$matches[2];
    $value=preg_replace("/%27/","'",$value);
    $value=preg_replace("/%20/"," ",$value);
    $data["$module"]["$key"]=$value;
  }
  preg_match("/sys=(.+)/",$field,$matches);
  $sys=$matches[1];
  foreach(array_keys($data) as $module){
    $fmodule="$dir/$module.conf";
    $fm=fopen($fmodule,"w");
    foreach(array_keys($data["$module"]) as $key){
      $value=$data["$module"]["$key"];
      $value=preg_replace("/^'/","\"'",$value);
      $value=preg_replace("/'$/","'\"",$value);
      $entry="$key=$value";
      fwrite($fm,"$entry\n");
    }
    if(!preg_match("/star/",$module) and !preg_match("/planet/",$module)){
      fwrite($fm,"str_sys=\"'$sys'\"\n");
    }
    fclose($fm);
  }
}

function ajaxMultipleForm($ids,$element,$slope=1)
{
  $code="";
  $i=0;
  foreach($ids as $id){
    $varname="statusidload$i";
    $$varname="${id}_results_status_loader";
    $i++;
  }

$code.=<<<CODE

$("#$element").submit(function(e){
CODE;
 
 $i=0;
  foreach($ids as $id){
    $sname="statusidload$i";
    $sval=$$sname;
    $pipepos=$i*$slope;

$code.=<<<CODE

 //alert("Submit All");
 var postData$i = $("#${id}_form").serializeArray();
 var formURL$i = $("#${id}_form").attr("action")+"?pipepos=$pipepos";
 $("#${id}_results_status").attr("style","opacity:0.1;background:white");
 $("#$sval").attr("style","background-image:url('web/load.gif');background-position:center top;background-repeat:no-repeat;z-index:100");
 
CODE;
    $i++;
  }

$code.=<<<CODE
e.preventDefault();
 if(!ajaxLoading){
   ajaxLoading=true;
   $.when(

CODE;

  $i=0;
  foreach($ids as $id){
    $sname="statusidload$i";
    $sval=$$sname;
$code.=<<<CODE
      $.ajax({
	url : formURL$i,
	type: "GET",
	data : postData$i,
	success:function(data, textStatus, jqXHR) 
	    {
	      $("#${id}_results_status").attr("style","background-color:white");
	      $("#$sval").attr("style","background-color:white");
	      $("#${id}_download").css("display","block");
	      $("#${id}_stdout").css("display","block");
	      $("#${id}_download").html("<a href=JavaScript:refreshiFrame('#${id}_results_frame')>Refresh</a> | <a href="+data+" target='_blank'>Download</a>");
	      $("#${id}_results_frame").attr("src",data);
	      refreshiFrames();
	    },
	error: function(jqXHR, textStatus, errorThrown) 
	    {
	      $("#${id}_results_status").html('<pre><code class="prettyprint">AJAX Request Failed<br/> textStatus='+textStatus+', errorThrown='+errorThrown+'</code></pre>');
	      $("#${id}_results_status").attr("style","background-color:white");
	      $("#$sval").attr("style","background-color:white");
	    }
	}),
CODE;
        $i++;
    }
    $code=trim($code,",");
$code.=<<<CODE
      );
   }else{ajaxLoading=false;}
      e.unbind();
   });
   $("#$element").submit();
CODE;
 return $code;
}

function ajaxMultipleFormSimple($form,$element)
{
  $code="";

$code.=<<<CODE
$("#$form").submit(function(e){
 var postData = $("#$form").serializeArray();
 var formURL = $("#$form").attr("action");
 e.preventDefault();
 if(!ajaxLoading){
   ajaxLoading=true;
   $.ajax({
     url : formURL,
     type: "GET",
     data : postData,
     success:function(data, textStatus, jqXHR) 
	 {
	     $("#$element").html("Status:"+data);
	 },
     error: function(jqXHR, textStatus, errorThrown) 
	 {
	     $("#$element").html("Status: <i>Error</i>");
	 }
     });
   }else{ajaxLoading=false;}
      e.unbind();
   });
   $("#$form").submit();
CODE;
 return $code;
}

function ajaxFromCode($code,$element,$action)
{
$allcode=<<<CODE
<script>
 var ajaxLoading=false;
 $($element).$action(function(){
     $code
 });
</script>
CODE;
 return $allcode;
}

function echoVerbose($string){
  if($GLOBALS["VERBOSE"]){echo $string;}
}

function readCSV($csvfile,$key=""){
  if(($fc=fopen($csvfile,"r"))==FALSE){
    echo "File $cvsfile is not reachable.";
    return;
  }
  $i=0;
  $data=array();
  $keys=array();
  while(($row=fgetcsv($fc,1000,";"))!=FALSE){
    $ncols=count($row);
    if($i==0){
      $col=0;
      $fields=array();
      foreach($row as $field){
	$fields[$col]=$field;
	$col++;
      }
    }else{
      $data[$i]=array();
      for($c=0;$c<$ncols;$c++){
	$value=$row[$c];
	$value=preg_replace("/\*/","",$value);
	if(preg_match("/\d+,\d+/",$value)){
	  $value=preg_replace("/,/",".",$value);
	}
	$data[$i][$fields[$c]]=$value;
      }
      if(preg_match("/\w/",$key)){
	$valkey=$data[$i][$key];
	$keys[$valkey]=$i;
      }else{
	$keys[$i]=$i;
      }
    }
    $i++;
  }
  fclose($fc);
  return array($data,$keys);
}

function loadSystems()
{
  global $DIR;
  $sys_csvfile="$DIR/BHM/data/BHMcat/BHMcat-systems.csv";
  $pla_csvfile="$DIR/BHM/data/BHMcat/BHMcat-planets.csv";

  $data=readCSV($sys_csvfile,"BHMCat");
  $systems=$data[0];
  $sys_keys=$data[1];
  $sys_fields=array_keys($systems[1]);
  $sys_num=count($systems);

  $data=readCSV($pla_csvfile,"BHMCatP");
  $planets=$data[0];
  $pla_keys=$data[1];
  $pla_fields=array_keys($planets[1]);
  $pla_num=count($planets);

  //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
  //LOAD PLANETS INTO SYSTEMS
  //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
  for($i=1;$i<=$sys_num;$i++){
    $planetids=$systems[$i]["Planets"];
    $planetids=preg_split("/;/",$planetids);
    $systems[$i]["PlanetsData"]=array();
    $j=0;
    foreach($planetids as $planetid){
      if(!preg_match("/\w/",$planetid)){continue;}
      $planet=$planets[$pla_keys["$planetid"]];
      array_push($systems[$i]["PlanetsData"],$planet);
      $j++;
    }
    if($j==0){
      $systems[$i]["NumPlanets"]=1;
      $planet=$planets[$pla_keys["BHMCatP0000"]];
      array_push($systems[$i]["PlanetsData"],$planet);
      $j=1;
    }
    $systems[$i]["NumPlanets"]=$j;
  }
  return $systems;
}

function isBlank($string){
  if(!preg_match("/[\w\d]+/",$string)){return 1;}
  else{return 0;}
}

?>
