<?php
	//-- functions.php v.1.01 --//
	//-- is-AIS by Erick Setiawan --//
	//-- see changelog for further information --//
	
	//-- device settings --//
	//-- will utilized records from database on next update --//
	//-- sementara di set dengan maksimal jumlah alat per server (8) --//
	$portName0 = 'com6';
	$baudRate0 = 9600;
	$portName1 = 'com6';
	$baudRate1 = 9600;
	$portName2 = 'com6';
	$baudRate2 = 9600;
	$portName3 = 'com6';
	$baudRate3 = 9600;
	$portName4 = 'com6';
	$baudRate4 = 9600;
	$portName5 = 'com6';
	$baudRate5 = 9600;
	$portName6 = 'com6';
	$baudRate6 = 9600;
	$portName7 = 'com6';
	$baudRate7 = 9600;
	$GLOBALS['numOfDevice'] = 8 ;
	//-- end of device setting --//
	
	//-- new method defining device --//
	$GLOBALS['devices'] = array();
	if ( $GLOBALS['numOfDevice'] > 1 ) {
		for ($i=0;$i<$GLOBALS['numOfDevice'];$i++){
			array_push( $GLOBALS['devices'], array( substr(${'portName'.$i},3) , ${'baudRate'.$i} ));
		}
	}
	else {
			array_push ( $GLOBALS['devices'], array( substr($portName0,3) , $baudRate0 ));
		}
	//-- end of new method & device settings --//
	
	//-- time and schedule setting --//
	$GLOBALS['$startIN'] = '06:00:00';
	$GLOBALS['$endIN'] = '08:00:00';
	$GLOBALS['$startOUT'] = '13:00:00';
	$GLOBALS['$endOUT'] = '15:00:00';
	$GLOBALS['$startMID'] = '08:00:01';
	$GLOBALS['$endMID'] = '12:59:59';
	//-- end of time and schedule setting --//
	
	//-- string related --//
	$GLOBALS['$messageNotIn'] = 'tidak melakukan tap masuk';
	$GLOBALS['$messageNotOut'] = 'tidak melakukan tap pulang';
	//-- end of string related --//
	
	//Establish connection to SQL
	function connect()
	{
		$serverName = "ER\SQL2008";
		// Since UID and PWD are not specified in the $connectionInfo array,
		// The connection will be attempted using Windows Authentication.
		$connectionInfo = array( "Database"=>"soyaletegra");
		$conn = sqlsrv_connect( $serverName, $connectionInfo);

		if( $conn ) {
			 return $conn;
		}else{
			 echo "Connection could not be established.<br />";
			 die( print_r( sqlsrv_errors(), true));
		}
	}
	
	//Disconnect from SQL connection
	function disconnect($conn)
	{
		sqlsrv_close( $conn );
	}
	
	//echo strings in realtime processing on web browser
	function echoFlush($string)
	{
		echo $string . "\n";
		flush();
	}
	
	//DIO is no longer used since v.1.01, so we comment it 
	//function checkDIO()
	//{
	//	if(!extension_loaded('dio'))
	//	{
	//		echoFlush( "Current version of AIS is no longer using DIO. The routine will be continued." );
	//	}
	//}
	
	//Execute baud setting for serial devices and sending command untuk pancingan
	function openDevice($numOfDevice){
		for ( $i=0 ; $i < $numOfDevice ; $i++){
			$command = 'mode '. $GLOBALS['devices'][$i][0] .' baud='. $GLOBALS['devices'][$i][1] .' data=8 stop=1 parity=n xon=on';
			exec($command);
			$command = 'SerialSend /devnum '. $GLOBALS['devices'][$i][0] .' /baudrate '. $GLOBALS['devices'][$i][1] .' "AT"';
			exec($command);
		}
		return 1;
	}
	
	//Command to send SMS
	function sendSMS($dstNo,$strMessage,$portNumber,$baudRate)
	{
		$command = 'SerialSend /baudrate '.$baudRate.' /devnum '.$portNumber.' /hex "AT+CMGS=\x22'.$dstNo.'\x22\x0d"';
		exec($command);
		exec("timeout /t 1");
		$command = 'SerialSend /baudrate '.$baudRate.' /devnum '.$portNumber.' /hex "'.$strMessage.'\x1a\x0d"';
		exec($command);
		exec("timeout /t 4");
	}
	
	//functions below is no longer used since v.1.01, so we comment it 
	// function confDevice($connectionThread,$confData,$durationSec)
	// {
		// return 1;
	// }
	
	// function waitFor($connectionThread,$durationSec)
	// {
		// return 1;
	// }
	
	// function closeDevice($connectionThread)
	// {
		// return 1;
	// }
?>