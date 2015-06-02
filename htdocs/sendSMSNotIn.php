<?php
	include "functions.php";
	
	$portName0 = $GLOBALS['$portName0'];
	$baudRate0 = $GLOBALS['$baudRate0'];
	$bits0 = $GLOBALS['$bits0'];
	$stopBit0 = $GLOBALS['$stopBit0'];
		
	//Connect to db
	$conn = connect();

	//Check DIO Extension
	checkDIO();


	try
	{		
		//Start Sending SMS Routine
		$device0 = openDevice($portName0,$baudRate0,$bits0,$stopBit0);
		if (!$device0)
		{
			closeDevice($device0);
			echoFlush( "Could not open Serial port {$portName0} ");
			exit;
		}
		
		//Get tap_status IN
		$sql = "         
			SELECT  id,user_name, mobile_no, tran_date, sms_status
			FROM sms_abnormal
			WHERE 
				sms_status=0
				AND tran_date = dateadd(day, datediff(day,0,GETDATE()),0)
				AND tap_status = 3
		";
		
		$stmt = sqlsrv_query($conn, $sql);
		
		if( $stmt === false) {
			die( print_r( sqlsrv_errors(), true) );
		}

		while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
			//Send the sms
			sendSMS($row['mobile_no'],'Selamat Siang, '.$row['user_name'].'. tidak absen / terlambat sesuai jam masuk ',$device0);
			
			//Set sms status to SudahKirim
			$sql_update_sms_status = "
									UPDATE sms_abnormal
									SET sms_status = 1
									WHERE id = ".$row['id']
			;
			$stmt2 = sqlsrv_query($conn, $sql_update_sms_status);
			sqlsrv_free_stmt( $stmt2);
		}
		
		//sqlsrv_free_stmt( $stmt);
		closeDevice($device0);

	}
	catch (Exception $e) 
	{
		echoFlush(  $e->getMessage() );
		exit(1);
	}
?>
