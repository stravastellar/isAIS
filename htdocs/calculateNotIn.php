<?php
	include "functions.php";
	$message = $GLOBALS['$messageNotIn'];
	
	//Connect to db
	$conn = connect();

	try
	{	//Execute calculation query
		$sql = "
			--Set the sms_status variable to 0 - BelumSms
			DECLARE @sms_status varchar(2)
			SET @sms_status = 0

			--Set the tap_status variable to 3 - NoTapIN
			DECLARE @tap_status varchar(2)
			SET @tap_status = 3

			--Set today date
			DECLARE @todayDate varchar(12)
			SET @todayDate = CONVERT(VARCHAR(10),GETDATE(),126)

			--Set Message
			DECLARE @message varchar(50)
			SET @message = '".$message."'

			;WITH Temp1 AS
			(
				SELECT  sms.id, sms.events_id, sms.profiles_id, profiles.user_name, profiles.mobile_no, events.tran_date, events.tran_time, sms.tap_status, sms.sms_status
				FROM         sms INNER JOIN
									  events ON sms.events_id = events.id INNER JOIN
									  profiles ON sms.profiles_id = profiles.id AND events.user_num = profiles.id
				WHERE sms.tap_status = 1 AND events.tran_date=dateadd(day, datediff(day,0,GETDATE()),0)
			),
			Temp2 AS
			(
				SELECT id AS user_num, user_name, mobile_no, @todayDate AS tran_date, @tap_status AS tap_status, @sms_status AS sms_status, @message AS message FROM profiles p WHERE NOT EXISTS (SELECT * FROM Temp1 t WHERE t.profiles_id=p.id)
			)

			--Insert the filtered result into [sms_abnormal] table
			INSERT INTO sms_abnormal
					   (user_num,
					   user_name,
					   mobile_no,
					   tran_date,
					   tap_status,
					   sms_status,
					   message
					   )
					SELECT *
					FROM Temp2
		";
		
		$stmt = sqlsrv_query($conn, $sql);
		sqlsrv_free_stmt( $stmt);
	}
	catch (Exception $e) 
	{
		echoFlush(  $e->getMessage() );
		exit(1);
	}
?>
