<?php

	//-- settings --//
	$GLOBALS['$portName0'] = 'com3';
	$GLOBALS['$baudRate0'] = 9600;
	$GLOBALS['$bits0'] = 8;
	$GLOBALS['$stopBit0'] = 1;
	$GLOBALS['$startIN'] = '06:00:00';
	$GLOBALS['$endIN'] = '08:00:00';
	$GLOBALS['$startOUT'] = '13:00:00';
	$GLOBALS['$endOUT'] = '15:00:00';
	$GLOBALS['$startMID'] = '08:00:01';
	$GLOBALS['$endMID'] = '12:59:59';
	$GLOBALS['$messageNotIn'] = 'terlambat parah / tidak tap masuk';
	$GLOBALS['$messageNotOut'] = 'pulang tidak tap';
	$GLOBALS['$serverName'] = 'DEMOUNIT-PC\SQL2008';
	$GLOBALS{'$dbName'] = 'soyaletegra';
	
	//Establish connection to SQL
	function connect()
	{
		$serverName = "DEMOUNIT-PC\SQL2008";
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
	
	function echoFlush($string)
	{
		echo $string . "\n";
		flush();
	}
	
	function waitFor($connectionThread,$durationSec)
	{
		return 1;
	}
	
	
	function checkDIO()
	{
		if(!extension_loaded('dio'))
		{
			echoFlush( "Current version of AIS is no longer using DIO. The routine will be continued." )
		}
	}
	
	function openDevice($portName,$baudRate,$bits,$stopBit)
	{
		$portNumber = substr($portName,3);
		exec("mode {$portName} baud={$baudRate} data={$bits} stop={$stopBit} parity=n xon=on");
		exec("SerialSend /devnum {$portNumber} /baudrate {$baudRate} AT");
		return $portNumber;
	}
	
	function confDevice($connectionThread,$confData,$durationSec)
	{
		return 1;
	}
	
	function sendSMS($dstNo,$strMessage,$portNumber)
	{
		exec("SerialSend /baudrate 9600 /devnum {$portNumber} /hex at+cmgs=\x22{$dstNo}x22\x0d\x0a");
		exec("timeout /t 1");
		exec("SerialSend /baudrate 9600 /devnum {$portNumber} /hex {$strMessage}\x1a\x0d\x0a");
		exec("timeout /t 4");
	}
	
	function closeDevice($connectionThread)
	{
		return 1;
	}
?>