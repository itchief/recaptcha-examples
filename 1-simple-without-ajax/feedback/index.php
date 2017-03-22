<?php
$secret = ''; // ваш секретный ключ

// если данные были отправлены методом POST, то...
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // переменная в которую будем сохранять результат работы
  $data['result']='success';
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

  if ($data['result']=='success') {
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
      }
    } else {
      $data['captcha']='Код капчи не прошёл проверку на сервере!';
      $data['result']='error';
    }
  }
  
  if ($data['result']=='success') {

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
  }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="utf-8">
  <title>Форма обратной связи</title>
  <link rel="stylesheet" href="/feedback/css/bootstrap.min.css">
  <script>
    function onSubmit(token) {
      // отправить форму на сервер
      console.log(token);
      document.getElementById("contactForm").submit();
    }
  </script>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body>

  <div class="container">
    <div class="row">
      <div class="col-sm-6 col-sm-offset-3">
        <h2 class="text-center">Форма обратной связи</h2>
        <div class="panel panel-info">
          <div class="panel-heading">
            <h2 class="h3 panel-title">Форма обратной связи</h3>
          </div>
          <div class="panel-body">

<?php if ($data['result']=='success') {
?>
<!-- Сообщение, отображаемое в случае успешной отправки данных -->
<div class="alert alert-success success-send" role="alert">
  <strong>Внимание!</strong> Форма была успешно отправлена.
</div>
<?php
}
?>

<?php if ($data['result']=='error' || $_SERVER['REQUEST_METHOD'] != 'POST') {
?>
<form id="contactForm" method="POST" action="/feedback/index.php">

<div class="error col-sm-12" style="color: #ff0000; margin-top: 5px; margin-bottom: 5px;">
<?php
if (isset($data)) {
  if ($data['result']=='error') {
    echo '<p>Форма не отправлена!</p>';
  }
  foreach ($data as $key=>$value) {
    if ($key != 'result') {
      echo '<p>'. $data[$key] .'</p>';
    }
  }
}
?>
</div>

<div class="clearfix"></div>
<!-- Имя пользователя -->
<div class="form-group has-feedback <?php if (isset($data['name'])) {echo "has-error";} ?>">
  <label for="name" class="control-label">Введите ваше имя*:</label>
  <input type="text" name="name" data-name="Введите ваше имя*" class="form-control" required="required" value="<?php if (isset($email)) {echo $name;} ?>" minlength="2" maxlength="30">
  <span class="glyphicon form-control-feedback <?php if (isset($data['name'])) {echo "glyphicon-remove";} ?>"></span>
</div>
<!-- Email пользователя -->
<div class="form-group has-feedback <?php if (isset($data['email'])) {echo "has-error";} ?>">
  <label for="email" class="control-label">Введите ваш email*:</label>
  <input type="text" name="email" data-name="Введите email*" required="required" class="form-control" value="<?php if (isset($email)) {echo $email;} ?>">
  <span class="glyphicon form-control-feedback <?php if (isset($data['email'])) {echo "glyphicon-remove";} ?>"></span>
</div>
<button class="g-recaptcha btn btn-primary pull-right" data-sitekey="6LdXhhgUAAAAAFUToe9JV6qa19DrI2TEM3GH-l7g" data-callback="onSubmit">Отправить</button>
</form>

<?php
}
?>

        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>