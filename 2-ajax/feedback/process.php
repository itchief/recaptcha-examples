<?php
  /* основные настройки: */
  $allowedExtension = array("jpg", "jpeg", "gif", "png"); // разрешённые типы файлов
  $pathToFile = $_SERVER['DOCUMENT_ROOT'].'/feedback/files/'; // директория для хранения файлов
  $maxSizeFile = 1048576; // максимальный размер файла в байтах
  $secret = ''; // ваш секретный ключ

  // открываем сессию
  session_start();
  // переменная в которую будем сохранять результат работы
  $data['result']='error';

  // функция для проверки длины строки
  function checkStringLength($string, $minLength, $maxLength) {
    $length = mb_strlen($string,'UTF-8');
    if (($length < $minLength) || ($length > $maxLength)) {
      return false;
    }
    else {
      return true;
    }
  }
  // если запрос не AJAX, то возвращаем ошибку и завершаем работу скрипта
  if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
    echo json_encode($data);
    exit();
  }

  $data['result']='success';

  // получаем имя
  if (isset($_POST['name'])) {
    $name = $_POST['name'];
    if (!checkStringLength($name, 2, 30)) {
      $data['name'] = 'Поле <b>имя</b> содержит недопустимое количество символов. Допустимое значение от 2 до 30.';
      $data['result']='error';
    }
  } else {
    $data['name'] = 'Поля <b>имя</b> не заполнено.';
    $data['result'] = 'error';
  }
  // получаем email
  if (isset($_POST['email'])) {
    $email = $_POST['email'];
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) {
      $data['email']='Поле <b>email</b> имеет не корректное значение.';
      $data['result']='error';
    }
  } else {
    $data['email'] = 'Поля <b>email</b> не заполнено.';
    $data['result']='error';
  }
  //получаем сообщение
  if (isset($_POST['message'])) {
    $message = $_POST['message'];
    if (!checkStringLength($message, 20, 500)) {
      $data['message']='Поле <b>сообщение</b> содержит недопустимое количество символов. Допустимое значение от 20 до 500.';
      $data['result']='error';
    }
  } else {
    $data['message'] = 'Поле <b>message</b> не заполнено.';
    $data['result']='error';
  }
  // блок проверки invisible reCAPTCHA
  require_once (dirname(__FILE__).'/recaptcha/autoload.php');
  // если в массиве $_POST существует ключ g-recaptcha-response, то...
  if (isset($_POST['g-recaptcha-response'])) {
    // создать экземпляр службы recaptcha, используя секретный ключ
    $recaptcha = new \ReCaptcha\ReCaptcha($secret);
    // получить результат проверки кода recaptcha
    $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
    // результат проверки
    if ($resp->isSuccess()){
      $data['result']=='success';
    } else {
      /* //для отладки: 
           $errors = $resp->getErrorCodes();
           $data['error-captcha'] = $errors;
      */
      $data['captcha']='Код капчи не прошёл проверку на сервере!';
      $data['result']='error';
      echo json_encode($data);
      exit();      
    }
  } else {
    $data['captcha']='Код капчи не прошёл проверку на сервере!';
    $data['result']='error';
    echo json_encode($data);
    exit();     
  }
  // если прозошли ошибки, то завершаем работу и возвращаем ответ клиенту
  if ($data['result']!='success') {
    echo json_encode($data);
    exit();    
  }

  // обработка переданных файлов (name="files[]")
  if(isset($_FILES["files"])) {
    $files = array();
    // цикл по файлам
    $i = 1;
    foreach ($_FILES["files"]["error"] as $key => $error) {
      if ($error == UPLOAD_ERR_OK) {
        // получаем характеристики файла
        $nameFile = $_FILES['files']['name'][$key];
        $extFile = mb_strtolower(pathinfo($nameFile, PATHINFO_EXTENSION));
        $sizefile = $_FILES['files']['size'][$key];
        $filetype = $_FILES['files']['type'][$key];
        // проверка расширения файла и его размер
        if (!in_array($extFile, $allowedExtension)) {
          $data['files-'+$i]='Ошибка при загрузке файла '. $nameFile .' (неверное расширение).';
          $data['result']='error';
          echo json_encode($data);
          exit();           
        }
        if ($sizefile > $maxSizeFile) {
          $data['files-'+$i]='Ошибка при загрузке файлов '. $nameFile .' (размер превышает '. $maxSizeFile/1024 .' Кбайт).';
          $data['result']='error';
          echo json_encode($data);
          exit();    
        } 
        $tmpFile = $_FILES['files']['tmp_name'][$key];
        // уникальное имя файла
        $newFileName = uniqid('img_', true).'.'.$extFile;
        // полное имя файла
        $newFullFileName = $pathToFile.$newFileName;
        // перемещаем файл в директорию
        if (!move_uploaded_file($tmpFile, $newFullFileName)) {
          $data['files'] = 'Произошла ошибка при загрузке файлов.';
          $data['result']='error';
          echo json_encode($data);
          exit();              
        }
        $files[] = $newFullFileName;
      } else {
        $data['files'] = 'Произошла ошибка при загрузке файлов.';
        $data['result']='error';
        echo json_encode($data);
        exit();        
      }
    }
  }
  
  // завершающие действия
  
  // запись информации в файл
  $output = "----------" . "\n";
  $output .= date("d-m-Y H:i:s") . "\n";
  $output .= "Имя пользователя: " . $name . "\n";
  $output .= "Адрес email: " . $email . "\n";
  $output .= "Сообщение: " . $message . "\n";
  if (isset($files)) {
    $output .= "Файлы: " . "\n";
    foreach ($files as $value) {
      $output .= $value . "\n";
    }
  }
  if (file_put_contents(dirname(__FILE__).'/message.txt', $output, FILE_APPEND | LOCK_EX)) {
    $data['result']='success';
  } else {
    $data['files'] = 'Произошла ошибка при отправке формы.';
    $data['result']='error';
    echo json_encode($data);
    exit();        
  }

  // отправка формы на email
  require_once dirname(__FILE__) . '/phpmailer/PHPMailerAutoload.php';
  //формируем тело письма
  $output = '<p><b>Дата</b>: ' . date('d-m-Y H:i') . '</p>';
  $output .= '<p><b>Имя пользователя:</b> ' . $name . '</p>';
  $output .= '<p><b>Адрес email:</b> ' . $email . '</p>';
  $output .= '<p><b>Сообщение:</b> ' . $message . '</p>';
  if (isset($files)) {
    $output .= '<p>Файлы:</p>';
    foreach ($files as $value) {
      $href = substr($value, strpos($value, '/feedback/'));
      $output .=  '<p><a href="'.$_SERVER['SERVER_NAME'].$href.'">'.$href.'</a></p>';
    }
  }
  // создаём экземпляр класса PHPMailer
  $mail = new PHPMailer;
  $mail->CharSet = 'UTF-8';
  $mail->From      = 'email@mysite.ru';
  $mail->FromName  = 'Имя сайта';
  $mail->isHTML(true);
  $mail->Subject   = 'Сообщение с формы обратной связи';
  $mail->Body      = $output;
  $mail->AddAddress( 'myemail@mail.ru' );

  // отправляем письмо
  if ($mail->Send()) {
    $data['result']='success';
  } else {
    $data['result']='error';
  }

  echo json_encode($data);

?>
