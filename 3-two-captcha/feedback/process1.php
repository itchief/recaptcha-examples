<?php
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
  
  // завершающие действия
  
  // запись информации в файл
  $output = "----------" . "\n";
  $output .= date("d-m-Y H:i:s") . "\n";
  $output .= "Имя пользователя: " . $name . "\n";
  $output .= "Адрес email: " . $email . "\n";
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
