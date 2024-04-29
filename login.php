<?php
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');

  $inputJSON = file_get_contents('php://input');
  $input = json_decode($inputJSON, true);
  
  // if (isset($_POST['POST'])){ }
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //header("Access-Control-Allow-Origin: * ");
    //header("Content-Type: text/html; charset=UTF-8");
    //header("Access-Control-Allow-Methods: POST");
    //header("Access-Control-Max-Age: 3600");
    //header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    $_POST['Username'] = $input['Username'];
    $_POST['Password'] = $input['Password'];

    // ตรวจสอบการส่งข้อมูล Username & Password จาก client
    if (isset($_POST['Username']) AND isset($_POST['Password'])) {
      // รับข้อมูล Username & Password จาก client
      $Username = mysqli_real_escape_string($Con_wang,trim($_POST['Username']));
      $Password = mysqli_real_escape_string($Con_wang,trim($_POST['Password']));

      $sql = "SELECT * FROM `member` WHERE `mem_username`='".$Username."' OR `mem_code`='".$Username."' LIMIT 1";
      $query = mysqli_query($Con_wang,$sql);    $num_rows = mysqli_num_rows($query);
      // พบ ข้อมูลที่ตรงกัน ใน Databases
      if ($num_rows != 0) {
        // ดึงข้อมูลที่พบออกมา
        $result = mysqli_fetch_array($query);
        // ตรวจสอบ Password จาก Client === Databases is true
        if ($Password == $result['mem_password']) {
          if ($result['mem_statuslogin']==0) {
            // Update status login user and lasttime login 
            mysqli_query($Con_wang,"
              UPDATE 
                `member` 
              SET 
                `mem_login`='1',
                `mem_logindate`=NOW(),
                `mem_loginupdate`=NOW()
              WHERE 
                `mem_id`='".$result['mem_id']."'
            ");
            //mysqli_close($Con_wang);  // close session connection to databases
            http_response_code(200);  //  response http status = 200 this OK
            $jwt = encode_jwt($result['mem_code']);  // ส่ง token ไปให้ client
            // สร้าง object สำหรับ response JSON 
            $json = array(
              'status' => true,
              'time' => date('Y-m-d H:i:s'),
              'meassage' => 'success',
              'token' => $jwt,
            );
            // response JSON Encode to Client
            echo json_encode($json);
          }
          // ถูกระงับการเข้าใช้งาน ชั่วคราว
          else {
            http_response_code(401);
            $json = array(
              'status' => false,
              'time' => date('Y-m-d H:i:s'),
              'meassage' => 'unauthorized ขออภัยในความไม่สะดวก ชื่อบัญชีผู้ใช้ถูกระงับการใช้งานชั่วคราว',
              'token' => null,
            );
            // response JSON Encode to Client
            echo json_encode($json);
          }
        }
        // หาก Password ไม่ถูกต้อง
        else {
          http_response_code(401);
          $json = array(
              'status' => false,
              'time' => date('Y-m-d H:i:s'),
              'meassage' => 'unauthorized รหัสผ่านไม่ถูกต้อง กรุณาตรวจสอบ Password แล้วลองใหม่อีกครั้งในภายหลัง ขอบคุณครับ/ค่ะ',
              'token' => null,
          );
          // response JSON Encode to Client
          echo json_encode($json);
        }
      }
      // ไม่พบ or ไม่มีชื่อบัญชีผู้ใช้ในระบบ
      else {
        http_response_code(401);
        $json = array(
              'status' => false,
              'time' => date('Y-m-d H:i:s'),
              'meassage' => 'unauthorized ไม่พบชื่อบัญชีผู้ใช้ ในระบบกรุณาตรวจสอบ Username แล้วลองใหม่อีกครั้งในภายหลัง ขอบคุณครับ/ค่ะ',
              'token' => null,
        );
        // response JSON Encode to Client
        echo json_encode($json);
      }
    }
    // ส่งค่ามาไม่ครับ or function ไม่รู้จักตัวแปลที่ส่งเข้ามา
    else {
      http_response_code(401);
      $json = array(
              'status' => false,
              'time' => date('Y-m-d H:i:s'),
              'meassage' => 'unauthorized ไม่เข้าใจ หรือ รับค่า POST[.....] จาก Client ไม่ได้',
              'token' => null,
      );
      // response JSON Encode to Client
      echo json_encode($json);
    }
  }
?>
