<?php

//dashboard.php

include ('class/Appointment.php');

$object = new Appointment;

include ('header.php');

?>

<div class="container-fluid">
  <?php
  include ('navbar.php');
  ?>
  <br />
  <div class="card">
    <div class="card-header">
      <div class="row">
        <div class="col">
          <h4 class="m-0 font-weight-bold">Doctor Schedule List</h4>
        </div>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-bordered" id="appointment_list_table">
          <thead>
            <tr>
              <th>Doctor Name</th>
              <th>Education</th>
              <th>Speciality</th>
              <th>Date Available</th>
              <th>Day Available</th>
              <th>Time Available</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

</div>

<?php

include ('footer.php');

?>

<!-- * Invisible input to hold important value -->
<!-- <input type="text" id="invisible_input" style="display: none;"> -->
<div id="appointmentModal" class="modal fade">
  <div class="modal-dialog">
    <form method="post" id="appointment_form">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="modal_title">Book Appointment</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <span id="form_message"></span>
          <div id="appointment_detail"></div>
          <div class="form-group">
            <label>Selected Doctor</label>
            <input type="text" id="selected_doctor_name" class="form-control" value="" readonly />
          </div>
          <div class="form-group">
            <label>Time</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text" id="basic-addon1"><i class="fas fa-clock"></i></span>
              </div>
              <input type="text" name="patient_appointment_time" id="patient_appointment_time"
                class="form-control datetimepicker-input" data-toggle="datetimepicker"
                data-target="#patient_appointment_time" required onkeydown="return false" onpaste="return false;"
                ondrop="return false;" autocomplete="off" />
            </div>
          </div>
          <div class="form-group">
            <label><b>Reason for Appointment</b></label>
            <textarea name="reason_for_appointment" id="reason_for_appointment" class="form-control" required
              rows="5"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <input type="hidden" name="hidden_doctor_id" id="doctor_id" />
          <input type="hidden" name="hidden_doctor_schedule_id" id="doctor_schedule_id" />
          <input type="hidden" name="action" id="action" value="book_appointment" />
          <input type="submit" name="submit" id="submit_button" class="btn btn-success" value="Book" />
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.0/moment.min.js"></script>
<script
  src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js"
  integrity="sha512-k6/Bkb8Fxf/c1Tkyl39yJwcOZ1P4cRrJu77p83zJjN2Z55prbFHxPs9vN7q3l3+tSMGPDdoH51AEU8Vgo1cgAA=="
  crossorigin="anonymous"></script>
<link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css"
  integrity="sha512-3JRrEUwaCkFUBLK1N8HehwQgu8e23jTH4np5NHOmQOobuC4ROQxFwFgBLTnhcnQRMs84muMh0PnnwXlPq5MGjg=="
  crossorigin="anonymous" />

<script>

  $(document).ready(function () {

    var dataTable = $('#appointment_list_table').DataTable({
      "processing": true,
      "serverSide": true,
      "order": [],
      "ajax": {
        url: "action.php",
        type: "POST",
        data: { action: 'fetch_schedule' },
      },
      "columnDefs": [
        {
          "targets": [6],
          "orderable": false,
        },
      ],
    });

    $(document).on('click', '.book_appointment', function () {
      $('#doctor_id').val($(this).data('doctor_id'));
      $('#doctor_schedule_id').val($(this).data('doctor_schedule_id'));
      $('#selected_doctor_name').val($(this).data('doctor-name'));
      $('#appointmentModal').modal('show');
    });

    $('#patient_appointment_time').datetimepicker({
      format: 'HH:mm'
    });

    $("#patient_appointment_time").on("change.datetimepicker", function (e) {
      console.log('test');
    });

    // $(document).on('click', '.get_appointment', function () {

    //   var doctor_schedule_id = $(this).data('doctor_schedule_id');
    //   var doctor_id = $(this).data('doctor_id');

    //   $.ajax({
    //     url: "action.php",
    //     method: "POST",
    //     data: { action: 'make_appointment', doctor_schedule_id: doctor_schedule_id },
    //     success: function (data) {
    //       $('#appointmentModal').modal('show');
    //       $('#hidden_doctor_id').val(doctor_id);
    //       $('#hidden_doctor_schedule_id').val(doctor_schedule_id);
    //       $('#appointment_detail').html(data);
    //     }
    //   });

    // });

    // * initializing form validation library
    $('#appointment_form').parsley();

    $('#appointment_form').on('submit', function (event) {

      event.preventDefault();

      if ($('#appointment_form').parsley().isValid()) {

        $.ajax({
          url: "action.php",
          method: "POST",
          data: $(this).serialize(),
          dataType: "json",
          beforeSend: function () {
            $('#submit_button').attr('disabled', 'disabled');
            $('#submit_button').val('wait...');
          },
          success: function (data) {
            $('#submit_button').attr('disabled', false);
            $('#submit_button').val('Book');
            if (data.error != '') {
              $('#form_message').html(data.error);
            }
            else {
              window.location.href = "appointment.php";
            }
          }
        })

      }

    })

  });

</script>