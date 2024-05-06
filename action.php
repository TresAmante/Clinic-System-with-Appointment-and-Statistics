<?php

//action.php

include ('class/Appointment.php');

$object = new Appointment;

if (isset($_POST["action"])) {
  if ($_POST["action"] == 'check_login') {
    if (isset($_SESSION['patient_id'])) {
      echo 'dashboard.php';
    } else {
      echo 'login.php';
    }
  }

  if ($_POST['action'] == 'patient_register') {
    $error = '';

    $success = '';

    $data = array(
      ':patient_email_address' => $_POST["patient_email_address"]
    );

    $object->query = "
		SELECT * FROM patient_table 
		WHERE patient_email_address = :patient_email_address
		";

    $object->execute($data);

    if ($object->row_count() > 0) {
      $error = '<div class="alert alert-danger">Email Address Already Exists</div>';
    } else {
      $patient_verification_code = md5(uniqid());
      $data = array(
        ':patient_email_address' => $object->clean_input($_POST["patient_email_address"]),
        ':patient_password' => $_POST["patient_password"],
        ':patient_first_name' => $object->clean_input($_POST["patient_first_name"]),
        ':patient_last_name' => $object->clean_input($_POST["patient_last_name"]),
        ':patient_date_of_birth' => $object->clean_input($_POST["patient_date_of_birth"]),
        ':patient_gender' => $object->clean_input($_POST["patient_gender"]),
        ':patient_address' => $object->clean_input($_POST["patient_address"]),
        ':patient_phone_no' => $object->clean_input($_POST["patient_phone_no"]),
        ':patient_maritial_status' => $object->clean_input($_POST["patient_maritial_status"]),
        ':patient_added_on' => $object->now,
        ':patient_verification_code' => $patient_verification_code,
        ':email_verify' => 'Yes'
      );

      $object->query = "
			INSERT INTO patient_table 
			(patient_email_address, patient_password, patient_first_name, patient_last_name, patient_date_of_birth, patient_gender, patient_address, patient_phone_no, patient_maritial_status, patient_added_on, patient_verification_code, email_verify) 
			VALUES (:patient_email_address, :patient_password, :patient_first_name, :patient_last_name, :patient_date_of_birth, :patient_gender, :patient_address, :patient_phone_no, :patient_maritial_status, :patient_added_on, :patient_verification_code, :email_verify)
			";

      $object->execute($data);

    }

    $output = array(
      'error' => $error,
      'success' => $success
    );
    //echo json_encode($output);
    header('location: login.php');
  }

  if ($_POST['action'] == 'patient_login') {
    $error = '';

    $data = array(
      ':patient_email_address' => $_POST["patient_email_address"]
    );

    $object->query = "
		SELECT * FROM patient_table 
		WHERE patient_email_address = :patient_email_address
		";

    $object->execute($data);

    if ($object->row_count() > 0) {

      $result = $object->statement_result();

      foreach ($result as $row) {
        if ($row["email_verify"] == 'Yes') {
          if ($row["patient_password"] == $_POST["patient_password"]) {
            $_SESSION['patient_id'] = $row['patient_id'];
            $_SESSION['patient_name'] = $row['patient_first_name'] . ' ' . $row['patient_last_name'];
          } else {
            $error = '<div class="alert alert-danger">Wrong Password</div>';
          }
        } else {
          $error = '<div class="alert alert-danger">Please first verify your email address</div>';
        }
      }
    } else {
      $error = '<div class="alert alert-danger">Wrong Email Address</div>';
    }

    $output = array(
      'error' => $error
    );

    echo json_encode($output);

  }

  if ($_POST['action'] == 'fetch_schedule') {
    $output = array();

    $order_column = array('doctor_table.doctor_name', 'doctor_table.doctor_degree', 'doctor_table.doctor_expert_in', 'doctor_schedule_table.doctor_schedule_date', 'doctor_schedule_table.doctor_schedule_day', 'doctor_schedule_table.doctor_schedule_start_time');

    $main_query = "
    SELECT * FROM doctor_schedule_table 
    INNER JOIN doctor_table 
    ON doctor_table.doctor_id = doctor_schedule_table.doctor_id ";

    $search_query = '
    WHERE doctor_schedule_table.doctor_schedule_date >= "' . date('Y-m-d') . '" 
    AND doctor_schedule_table.doctor_schedule_status = "Active" 
    AND doctor_table.doctor_status = "Active" 
    ';

    // $search_query = '
    // WHERE doctor_schedule_table.doctor_schedule_status = "Active" 
    // AND doctor_table.doctor_status = "Active" 
    // ';

    if (isset($_POST["search"]["value"])) {
      $search_query .= 'AND ( doctor_table.doctor_name LIKE "%' . $_POST["search"]["value"] . '%" ';
      $search_query .= 'OR doctor_table.doctor_degree LIKE "%' . $_POST["search"]["value"] . '%" ';
      $search_query .= 'OR doctor_table.doctor_expert_in LIKE "%' . $_POST["search"]["value"] . '%" ';
      $search_query .= 'OR doctor_schedule_table.doctor_schedule_date LIKE "%' . $_POST["search"]["value"] . '%" ';
      $search_query .= 'OR doctor_schedule_table.doctor_schedule_day LIKE "%' . $_POST["search"]["value"] . '%" ';
      $search_query .= 'OR doctor_schedule_table.doctor_schedule_start_time LIKE "%' . $_POST["search"]["value"] . '%") ';
    }

    if (isset($_POST["order"])) {
      $order_query = 'ORDER BY ' . $order_column[$_POST['order']['0']['column']] . ' ' . $_POST['order']['0']['dir'] . ' ';
    } else {
      $order_query = 'ORDER BY doctor_schedule_table.doctor_schedule_date ASC ';
    }

    $limit_query = '';

    if ($_POST["length"] != -1) {
      $limit_query .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
    }

    $object->query = $main_query . $search_query . $order_query;

    $object->execute();

    $filtered_rows = $object->row_count();

    $object->query .= $limit_query;

    $result = $object->get_result();

    $object->query = $main_query . $search_query;

    $object->execute();

    $total_rows = $object->row_count();

    $data = array();

    foreach ($result as $row) {
      $sub_array = array();

      $sub_array[] = $row["doctor_name"];

      $sub_array[] = $row["doctor_degree"];

      $sub_array[] = $row["doctor_expert_in"];

      $sub_array[] = $row["doctor_schedule_date"];

      $sub_array[] = $row["doctor_schedule_day"];

      $sub_array[] = $row["doctor_schedule_start_time"];

      $sub_array[] = '
			<div align="center">
			<button type="button" name="book_appointment" class="btn btn-success btn-sm book_appointment" data-doctor_id="' . $row["doctor_id"] .
        '" data-doctor_schedule_id="' . $row["doctor_schedule_id"] . '" data-doctor-name="' . $row["doctor_name"] . '">Book Appointment</button>
			</div>
			';
      $data[] = $sub_array;
    }

    $output = array(
      "draw" => intval($_POST["draw"]),
      "recordsTotal" => $total_rows,
      "recordsFiltered" => $filtered_rows,
      "data" => $data
    );

    echo json_encode($output);
  }

  if ($_POST['action'] == 'edit_profile') {
    $data = array(
      ':patient_password' => $_POST["patient_password"],
      ':patient_first_name' => $_POST["patient_first_name"],
      ':patient_last_name' => $_POST["patient_last_name"],
      ':patient_date_of_birth' => $_POST["patient_date_of_birth"],
      ':patient_gender' => $_POST["patient_gender"],
      ':patient_address' => $_POST["patient_address"],
      ':patient_phone_no' => $_POST["patient_phone_no"],
      ':patient_maritial_status' => $_POST["patient_maritial_status"]
    );

    $object->query = "
		UPDATE patient_table  
		SET patient_password = :patient_password, 
		patient_first_name = :patient_first_name, 
		patient_last_name = :patient_last_name, 
		patient_date_of_birth = :patient_date_of_birth, 
		patient_gender = :patient_gender, 
		patient_address = :patient_address, 
		patient_phone_no = :patient_phone_no, 
		patient_maritial_status = :patient_maritial_status 
		WHERE patient_id = '" . $_SESSION['patient_id'] . "'
		";

    $object->execute($data);

    $_SESSION['success_message'] = '<div class="alert alert-success">Profile Data Updated</div>';

    echo 'done';
  }

  if ($_POST['action'] == 'make_appointment') {
    $object->query = "
		SELECT * FROM patient_table 
		WHERE patient_id = '" . $_SESSION["patient_id"] . "'
		";

    $patient_data = $object->get_result();

    $object->query = "
		SELECT * FROM doctor_schedule_table 
		INNER JOIN doctor_table 
		ON doctor_table.doctor_id = doctor_schedule_table.doctor_id 
		WHERE doctor_schedule_table.doctor_schedule_id = '" . $_POST["doctor_schedule_id"] . "'
		";

    $doctor_schedule_data = $object->get_result();

    $html = '
		<h4 class="text-center">Patient Details</h4>
		<table class="table">
		';

    foreach ($patient_data as $patient_row) {
      $html .= '
			<tr>
				<th width="40%" class="text-right">Patient Name</th>
				<td>' . $patient_row["patient_first_name"] . ' ' . $patient_row["patient_last_name"] . '</td>
			</tr>
			<tr>
				<th width="40%" class="text-right">Contact No.</th>
				<td>' . $patient_row["patient_phone_no"] . '</td>
			</tr>
			<tr>
				<th width="40%" class="text-right">Address</th>
				<td>' . $patient_row["patient_address"] . '</td>
			</tr>
			';
    }

    $html .= '
		</table>
		<hr />
		<h4 class="text-center">Appointment Details</h4>
		<table class="table">
		';
    foreach ($doctor_schedule_data as $doctor_schedule_row) {
      $html .= '
			<tr>
				<th width="40%" class="text-right">Doctor Name</th>
				<td>' . $doctor_schedule_row["doctor_name"] . '</td>
			</tr>
			<tr>
				<th width="40%" class="text-right">Appointment Date</th>
				<td>' . $doctor_schedule_row["doctor_schedule_date"] . '</td>
			</tr>
			<tr>
				<th width="40%" class="text-right">Appointment Day</th>
				<td>' . $doctor_schedule_row["doctor_schedule_day"] . '</td>
			</tr>
			<tr>
				<th width="40%" class="text-right">Available Time</th>
				<td>' . $doctor_schedule_row["doctor_schedule_start_time"] . ' - ' . $doctor_schedule_row["doctor_schedule_end_time"] . '</td>
			</tr>
			';
    }

    $html .= '
		</table>';
    echo $html;
  }

  if ($_POST['action'] == 'book_appointment') {
    $error = '';
    $data = array(
      ':patient_id' => $_SESSION['patient_id'],
      ':doctor_schedule_id' => $_POST['hidden_doctor_schedule_id']
    );

    $object->query = "
		SELECT * FROM appointment_table 
		WHERE patient_id = :patient_id 
		AND doctor_schedule_id = :doctor_schedule_id
		";

    $object->execute($data);

    if ($object->row_count() > 0) {
      $error = '<div class="alert alert-danger">You have already applied for appointment for this day, try for other day.</div>';
    } else if (!isAppointmentTimeValid($_POST["patient_appointment_time"], $_POST["hidden_doctor_schedule_id"])) {
      $error = '<div class="alert alert-danger">You cannot schedule an appointment earlier than the doctor\'s schedule.</div>';
    } else if (isTimeOfAppointmentDuplicate($_POST["patient_appointment_time"], $_POST["hidden_doctor_schedule_id"])) {
      $error = '<div class="alert alert-danger">There is already another appointment scheduled on this date and time.</div>';
    } else {
      $object->query = "
			SELECT * FROM doctor_schedule_table 
			WHERE doctor_schedule_id = '" . $_POST['hidden_doctor_schedule_id'] . "'
			";

      $schedule_data = $object->get_result();

      $object->query = "
			SELECT COUNT(appointment_id) AS total FROM appointment_table 
			WHERE doctor_schedule_id = '" . $_POST['hidden_doctor_schedule_id'] . "' 
			";

      $appointment_data = $object->get_result();

      $total_doctor_available_minute = 0;
      $average_consulting_time = 0;
      $total_appointment = 0;

      foreach ($schedule_data as $schedule_row) {
        $end_time = strtotime($schedule_row["doctor_schedule_end_time"] . ':00');

        $start_time = strtotime($schedule_row["doctor_schedule_start_time"] . ':00');

        $total_doctor_available_minute = ($end_time - $start_time) / 60;

        $average_consulting_time = $schedule_row["average_consulting_time"];
      }

      foreach ($appointment_data as $appointment_row) {
        $total_appointment = $appointment_row["total"];
      }

      $total_appointment_minute_use = $total_appointment * $average_consulting_time;

      // $appointment_time = date("H:i", strtotime('+' . $total_appointment_minute_use . ' minutes', $start_time));
      $appointment_time = $_POST['patient_appointment_time'];

      $status = '';

      $appointment_number = $object->Generate_appointment_no();

      if (strtotime($end_time) > strtotime($appointment_time . ':00')) {
        $status = 'Booked';
      } else {
        $status = 'Waiting';
      }

      $data = array(
        ':doctor_id' => $_POST['hidden_doctor_id'],
        ':patient_id' => $_SESSION['patient_id'],
        ':doctor_schedule_id' => $_POST['hidden_doctor_schedule_id'],
        ':appointment_number' => $appointment_number,
        ':reason_for_appointment' => $_POST['reason_for_appointment'],
        ':appointment_time' => $appointment_time,
        ':status' => 'Booked'
      );

      $object->query = "
			INSERT INTO appointment_table 
			(doctor_id, patient_id, doctor_schedule_id, appointment_number, reason_for_appointment, appointment_time, status) 
			VALUES (:doctor_id, :patient_id, :doctor_schedule_id, :appointment_number, :reason_for_appointment, :appointment_time, :status)
			";

      $object->execute($data);

      $_SESSION['appointment_message'] = '<div class="alert alert-success">Your Appointment has been <b>' . $status . '</b> with Appointment No. <b>' . $appointment_number . '</b></div>';

    }
    echo json_encode(['error' => $error]);
  }

  if ($_POST['action'] == 'fetch_appointment') {
    $output = array();

    $order_column = array('appointment_table.appointment_number', 'doctor_table.doctor_name', 'doctor_schedule_table.doctor_schedule_date', 'appointment_table.appointment_time', 'doctor_schedule_table.doctor_schedule_day', 'appointment_table.status');

    $main_query = "
		SELECT * FROM appointment_table  
		INNER JOIN doctor_table 
		ON doctor_table.doctor_id = appointment_table.doctor_id 
		INNER JOIN doctor_schedule_table 
		ON doctor_schedule_table.doctor_schedule_id = appointment_table.doctor_schedule_id 
		
		";

    $search_query = '
		WHERE appointment_table.patient_id = "' . $_SESSION["patient_id"] . '" 
		';

    if (isset($_POST["search"]["value"])) {
      $search_query .= 'AND ( appointment_table.appointment_number LIKE "%' . $_POST["search"]["value"] . '%" ';
      $search_query .= 'OR doctor_table.doctor_name LIKE "%' . $_POST["search"]["value"] . '%" ';
      $search_query .= 'OR doctor_schedule_table.doctor_schedule_date LIKE "%' . $_POST["search"]["value"] . '%" ';
      $search_query .= 'OR appointment_table.appointment_time LIKE "%' . $_POST["search"]["value"] . '%" ';
      $search_query .= 'OR doctor_schedule_table.doctor_schedule_day LIKE "%' . $_POST["search"]["value"] . '%" ';
      $search_query .= 'OR appointment_table.status LIKE "%' . $_POST["search"]["value"] . '%") ';
    }

    if (isset($_POST["order"])) {
      $order_query = 'ORDER BY ' . $order_column[$_POST['order']['0']['column']] . ' ' . $_POST['order']['0']['dir'] . ' ';
    } else {
      $order_query = 'ORDER BY appointment_table.appointment_id ASC ';
    }

    $limit_query = '';

    if ($_POST["length"] != -1) {
      $limit_query .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
    }

    $object->query = $main_query . $search_query . $order_query;

    $object->execute();

    $filtered_rows = $object->row_count();

    $object->query .= $limit_query;

    $result = $object->get_result();

    $object->query = $main_query . $search_query;

    $object->execute();

    $total_rows = $object->row_count();

    $data = array();

    foreach ($result as $row) {
      $sub_array = array();

      $sub_array[] = $row["appointment_number"];

      $sub_array[] = $row["doctor_name"];

      $sub_array[] = $row["doctor_schedule_date"];

      $sub_array[] = $row["appointment_time"];

      $end_time = calculateEndTime($row["appointment_time"], $row["doctor_schedule_id"]);
      $sub_array[] = $end_time;

      $sub_array[] = $row["doctor_schedule_day"];

      $status = '';

      if ($row["status"] == 'Booked') {
        $status = '<span class="badge badge-warning">' . $row["status"] . '</span>';
      }

      if ($row["status"] == 'In Process') {
        $status = '<span class="badge badge-primary">' . $row["status"] . '</span>';
      }

      if ($row["status"] == 'Completed') {
        $status = '<span class="badge badge-success">' . $row["status"] . '</span>';
      }

      if ($row["status"] == 'Cancel') {
        $status = '<span class="badge badge-danger">' . $row["status"] . '</span>';
      }

      $sub_array[] = $status;

      $sub_array[] = '<a href="download.php?id=' . $row["appointment_id"] . '" class="btn btn-danger btn-sm" target="_blank"><i class="fas fa-file-pdf"></i> PDF</a>';

      $sub_array[] = '<button type="button" name="cancel_appointment" class="btn btn-danger btn-sm cancel_appointment" data-id="' . $row["appointment_id"] . '"><i class="fas fa-times"></i></button>';

      $data[] = $sub_array;
    }

    $output = array(
      "draw" => intval($_POST["draw"]),
      "recordsTotal" => $total_rows,
      "recordsFiltered" => $filtered_rows,
      "data" => $data
    );

    echo json_encode($output);
  }

  if ($_POST['action'] == 'cancel_appointment') {
    $data = array(
      ':status' => 'Cancel',
      ':appointment_id' => $_POST['appointment_id']
    );
    $object->query = "
		UPDATE appointment_table 
		SET status = :status 
		WHERE appointment_id = :appointment_id
		";
    $object->execute($data);
    echo '<div class="alert alert-success">Your Appointment has been Cancel</div>';
  }
}

function calculateEndTime($startTime, $doctor_schedule_id)
{
  $pdo = new PDO("mysql:host=localhost;dbname=chaos", "root", "password");

  $query = "SELECT average_consulting_time FROM doctor_schedule_table WHERE doctor_schedule_id = :doctor_schedule_id";

  $statement = $pdo->prepare($query);

  $statement->bindParam(':doctor_schedule_id', $doctor_schedule_id);

  $statement->execute();

  $result = $statement->fetch(PDO::FETCH_ASSOC);

  $statement->closeCursor();
  $pdo = null;

  $endTime = date('H:i:s', strtotime($startTime . ' +' . $result['average_consulting_time'] . ' minutes'));

  return $endTime;
}

function isTimeOfAppointmentDuplicate($startTime, $doctor_schedule_id)
{
  $pdo = new PDO("mysql:host=localhost;dbname=chaos", "root", "");

  $sql = "SELECT COUNT(*) as count 
            FROM appointment_table AS a
            INNER JOIN doctor_schedule_table AS s ON a.doctor_schedule_id = s.doctor_schedule_id
            WHERE a.doctor_schedule_id = :doctor_schedule_id 
            AND ((:start_time BETWEEN a.appointment_time AND DATE_ADD(a.appointment_time, INTERVAL s.average_consulting_time MINUTE)) 
            OR (DATE_ADD(:start_time, INTERVAL s.average_consulting_time MINUTE) BETWEEN a.appointment_time AND DATE_ADD(a.appointment_time, INTERVAL s.average_consulting_time MINUTE)))";

  $stmt = $pdo->prepare($sql);
  $stmt->bindParam(':doctor_schedule_id', $doctor_schedule_id);
  $stmt->bindParam(':start_time', $startTime);
  $stmt->execute();

  // Fetch the count of overlapping appointments
  $result = $stmt->fetch(PDO::FETCH_ASSOC);

  $stmt->closeCursor();
  $pdo = null;

  if ($result['count'] > 0) {
    return true;
  } else {
    return false;
  }
}

function isAppointmentTimeValid($startTime, $doctor_schedule_id)
{
  $pdo = new PDO("mysql:host=localhost;dbname=chaos", "root", "");

  // Query to fetch the scheduled start time for the doctor
  $sql = "SELECT doctor_schedule_start_time FROM doctor_schedule_table WHERE doctor_schedule_id = :doctor_schedule_id";

  // Prepare and execute the query
  $stmt = $pdo->prepare($sql);
  $stmt->bindParam(':doctor_schedule_id', $doctor_schedule_id);
  $stmt->execute();

  // Fetch the scheduled start time from the result
  $scheduled_start_time = $stmt->fetchColumn();

  // Check if the appointment start time is later than or equal to the scheduled start time
  if ($startTime >= $scheduled_start_time) {
    return true;
  } else {
    return false;
  }
}