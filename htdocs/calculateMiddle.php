<?php
	include "functions.php";
	
	$portName0 = $GLOBALS['$portName0'];
	$baudRate0 = $GLOBALS['$baudRate0'];
	$bits0 = $GLOBALS['$bits0'];
	$stopBit0 = $GLOBALS['$stopBit0'];
	$startTime = $GLOBALS['$startMID'];
	$endTime = $GLOBALS['$endMID'];
	
	//Connect to db
	$conn = connect();

	//Check DIO Extension
	checkDIO();


	try
	{	//Execute filtering query
		$sql = "
			--Getting very last id from events_id from [sms] table
			--If any entry exist, check and set @lastEventsId to the value, if not exist, set to zero
			DECLARE @lastEventsId bigint
			IF EXISTS (SELECT TOP 1 events_id FROM sms ORDER BY events_id DESC)
			BEGIN SET @lastEventsId = (SELECT TOP 1 events_id FROM sms ORDER BY events_id DESC) END
			ELSE
			BEGIN SET @lastEventsId = 0 END

			--Set the sms_status variable to 0 - BelumSms
			DECLARE @sms_status varchar(2)
			SET @sms_status = 0

			--Set the tap_status variable to 5 - AbnormalTapMiddle
			DECLARE @tap_status varchar(2)
			SET @tap_status = 5

			--Set temporary table, filtering from BETWEEN StIn AND EndIn
			;WITH Temp1 AS
			(
				SELECT id,
					   user_num,
					   tran_date,
					   tran_time,
					   @tap_status AS tap_status,
					   @sms_status AS sms_status          
				FROM   events
				WHERE tran_type=39 
						AND user_num IS NOT NULL
						AND tran_date = dateadd(day, datediff(day,0,GETDATE()),0)
						AND CAST(tran_time as time) > '".$startTime."'
						AND CAST(tran_time as time) < '".$endTime."'
			),

			--Set temporary table, add FIRST IN calculation
			Temp2 AS
			(
				SELECT	*,
						ROW_NUMBER() OVER(PARTITION BY user_num,tran_date ORDER BY tran_date, tran_time) AS FirstRn
				
				FROM Temp1
			)


			--Insert the filtered result into [sms] table
			INSERT INTO sms
					   (
					   events_id
					   ,profiles_id
					   ,tap_status
					   ,sms_status
					   )
					SELECT DISTINCT id,user_num,tap_status,sms_status
					FROM Temp2
					WHERE FirstRn=1 AND id > @lastEventsId
					ORDER BY id
		";
		
		$stmt = sqlsrv_query($conn, $sql);
		//sqlsrv_free_stmt( $stmt);

	}
	catch (Exception $e) 
	{
		echoFlush(  $e->getMessage() );
		exit(1);
	}
?>
