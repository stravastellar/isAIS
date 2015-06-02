<?php
	include "functions.php";
	
	$portName0 = $GLOBALS['$portName0'];
	$baudRate0 = $GLOBALS['$baudRate0'];
	$bits0 = $GLOBALS['$bits0'];
	$stopBit0 = $GLOBALS['$stopBit0'];
	$startTime = $GLOBALS['$startOUT'];
	$endTime = $GLOBALS['$endOUT'];
	$startMidTime = $GLOBALS['$startMID'];
	$endMidTime = $GLOBALS['$endMID'];
	
	//Connect to db
	$conn = connect();

	//Check DIO Extension
	checkDIO();


	try
	{	
		//Get Last events_id
		$sql="
			--Getting very last id from events_id from [sms] table
			--If any entry exist, check and set @lastEventsId to the value, if not exist, set to zero
			IF EXISTS (SELECT TOP 1 events_id FROM sms ORDER BY events_id DESC)
			BEGIN SELECT TOP 1 events_id FROM sms ORDER BY events_id DESC END
			ELSE
			BEGIN SELECT 0 END
		";
		$stmt = sqlsrv_query($conn, $sql);
		
		if( $stmt === false) {
			die( print_r( sqlsrv_errors(), true) );
		}
		
		while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
			$lastEventsId = $row['events_id'];
		}
		
		//Execute filtering query
		$sql = "
			DECLARE @lastEventsId bigint
			SET @lastEventsId = ".$lastEventsId."

			--Set the sms_status variable to 0 - BelumSms
			DECLARE @sms_status varchar(2)
			SET @sms_status = 0

			--Set the tap_status variable to 2 - OUT
			DECLARE @tap_status varchar(2)
			SET @tap_status = 2
			
			--Process member which already tap at Middle
			;WITH Temp1 AS
			(
				SELECT id,
					   user_num,
					   tran_date,
					   tran_time,
					   ROW_NUMBER() OVER(PARTITION BY user_num,tran_date ORDER BY tran_date DESC, tran_time DESC) AS LastRn,
					   @tap_status AS tap_status,
					   @sms_status AS sms_status 
				FROM   events
				WHERE tran_type=39
						AND id > @lastEventsId
						AND user_num IS NOT NULL
						AND tran_date = dateadd(day, datediff(day,0,GETDATE()),0)
						AND CAST(tran_time as time) > '".$startMidTime."'
						AND CAST(tran_time as time) < '".$endMidTime."'
			),
			--LO
			Temp2 AS
			(
				SELECT * FROM Temp1 WHERE LastRn=1
			)
			UPDATE sms
			SET sms.events_id = Temp2.id, sms.tap_status=2
			FROM sms INNER JOIN events ON sms.events_id = events.id, Temp2
			WHERE EXISTS (SELECT * FROM Temp2 WHERE sms.profiles_id=Temp2.user_num AND events.tran_date=dateadd(day, datediff(day,0,GETDATE()),0)) 
			
			--Process member which have Normal OUT Tap
			;WITH Temp1 AS
			(
				SELECT id,
					   user_num,
					   tran_date,
					   tran_time,
					   ROW_NUMBER() OVER(PARTITION BY user_num,tran_date ORDER BY tran_date DESC, tran_time DESC) AS LastRn,
					   @tap_status AS tap_status,
					   @sms_status AS sms_status 
				FROM   events
				WHERE tran_type=39
						AND id > @lastEventsId
						AND user_num IS NOT NULL
						AND tran_date = dateadd(day, datediff(day,0,GETDATE()),0)
						AND CAST(tran_time as time) > '".$startTime."'
						AND CAST(tran_time as time) < '".$endTime."'
			),
			Temp2 AS
			(
				SELECT * FROM Temp1 WHERE LastRn=1
			)

			UPDATE sms
			SET sms.events_id = Temp2.id
			FROM sms, Temp2
			WHERE sms.profiles_id = Temp2.user_num AND sms.tap_status = 2
			
			;WITH Temp1 AS
			(
				SELECT id,
					   user_num,
					   tran_date,
					   tran_time,
					   ROW_NUMBER() OVER(PARTITION BY user_num,tran_date ORDER BY tran_date DESC, tran_time DESC) AS LastRn,
					   @tap_status AS tap_status,
					   @sms_status AS sms_status 
				FROM   events
				WHERE tran_type=39
						AND id > @lastEventsId
						AND user_num IS NOT NULL
						AND tran_date = dateadd(day, datediff(day,0,GETDATE()),0)
						AND CAST(tran_time as time) > '".$startTime."'
						AND CAST(tran_time as time) < '".$endTime."'
			),
			Temp2 AS
			(
				SELECT * FROM Temp1 WHERE LastRn=1
			)
			INSERT INTO sms
					   (
					   events_id
					   ,profiles_id
					   ,tap_status
					   ,sms_status)
					SELECT DISTINCT id,user_num,tap_status,sms_status
					FROM Temp2 A
					WHERE NOT EXISTS (SELECT * FROM sms B WHERE B.tap_status = 2 AND A.user_num = B.profiles_id)
					ORDER BY id
		";
		
		$stmt = sqlsrv_query($conn, $sql);
		
		
		//Start Sending SMS Routine
		$device0 = openDevice($portName0,$baudRate0,$bits0,$stopBit0);
		if (!$device0)
		{
			closeDevice($device0);
			echoFlush( "Could not open Serial port {$portName0} ");
			exit;
		}
		
		//Get tap_status OUT
		$sql = "         
			SELECT  sms.id, sms.events_id, sms.profiles_id, profiles.user_name, profiles.mobile_no, events.tran_date, events.tran_time, sms.sms_status
			FROM sms INNER JOIN events ON sms.events_id = events.id INNER JOIN profiles ON sms.profiles_id = profiles.id AND events.user_num = profiles.id
			WHERE 
				sms.sms_status=0
				AND events.tran_date = dateadd(day, datediff(day,0,GETDATE()),0)
				AND sms.tap_status = 2
			ORDER BY events.tran_date DESC, events.tran_time ASC
		";
		
		$stmt = sqlsrv_query($conn, $sql);
		
		if( $stmt === false) {
			die( print_r( sqlsrv_errors(), true) );
		}

		while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
			//echo $row['user_name']."  -  ".$row['mobile_no'] ."  pada ".$row['tran_date']->format('Y-m-d '). $row['tran_time']->format('H:i:s')." status kirim sms :". $row['sms_status']."<br />";
			
			//Send the sms
			sendSMS($row['mobile_no'],'Selamat Siang, '.$row['user_name'].'. Anda keluar hari ini pada '.$row['tran_time']->format('H:i:s'),$device0);
			
			//Set sms status to SudahKirim
			$sql_update_sms_status = "
									UPDATE sms
									SET sms_status = 1
									WHERE id = ".$row['id']
			;
			$stmt2 = sqlsrv_query($conn, $sql_update_sms_status);
			sqlsrv_free_stmt( $stmt2);
		}
		
		sqlsrv_free_stmt( $stmt);
		closeDevice($device0);
	}
	catch (Exception $e) 
	{
		echoFlush(  $e->getMessage() );
		exit(1);
	}
?>
