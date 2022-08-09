<?php

define('SECRET_KEY', '8LeSClchAAAAAAGDbQczz40se6W5d-eRmulZzeKC');

// Подключим автозагрузчик Composer
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Если запрос был отправлен методом POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Переменная в которую будем сохранять результат работы
  $data['result'] = 'success';
  // Валидация name
  if (!empty($_POST['name'])) {
    $data['form']['name'] = htmlspecialchars($_POST['name']);
  } else {
    $data['result'] = 'error';
    $data['errors']['name'] = 'Заполните это поле.';
  }
  // Валидация email
  if (!empty($_POST['email'])) {
    $data['form']['email'] = $_POST['email'];
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
      $data['result'] = 'error';
      $data['errors']['email'] = 'Email не корректный.';
    }
  } else {
    $data['result'] = 'error';
    $data['errors']['email'] = 'Заполните это поле.';
  }
  // Проверяем ответ невидимой Google reCAPTCHA
  if ($data['result'] == 'success') {
    $recaptcha = new \ReCaptcha\ReCaptcha(SECRET_KEY);
    $resp = $recaptcha->setExpectedHostname('site.ru')
      ->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
    if (!$resp->isSuccess()) {
      $errors = $resp->getErrorCodes();
      error_log('reCAPTCHA Error: ' . print_r($errors, true));
      $data['result'] = 'error';
      $data['errors']['form'] = 'Форма не прошла успешную проверку на сервере.';
    }
  }
  // Отправляем письмо на Email
  if ($data['result'] == 'success') {

    $mail = new PHPMailer(true);
    try {
      // Настройки сервера
      $mail->SMTPDebug = SMTP::DEBUG_SERVER;
      $mail->isSMTP();
      $mail->Host = 'ssl://smtp.yandex.ru';                      // SMTP сервер
      $mail->SMTPAuth = true;
      $mail->Username = 'xxxxxxxx@yandex.ru';                    // SMTP имя пользователя
      $mail->Password = 'xxxxxxxx';                              // SMTP пароль
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
      $mail->Port = 465;                                         // TCP порт

      // От кого отправить
      $mail->setFrom('xxxxxxxx@yandex.ru', 'Имя отправителя');

      // Кому отправить
      $mail->addAddress('xxxxxxxx@gmail.com');

      // Контент
      $mail->CharSet = 'utf-8';
      $mail->Encoding = 'base64';
      $mail->isHTML(true);
      $mail->Subject = 'Сообщение с формы обратной связи';
      $mail->Body = '<p>Данные пользователя:</p><ul><li><b>Имя:</b> ' . $data['form']['name'] . '</li><li><b>Email:</b> ' . $data['form']['email'] . '</li></ul>';

      $mail->send();
    } catch (Exception $e) {
      error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
      $data['result'] = 'error';
      $data['errors']['form'] = 'Произошла ошибка при отправке почты. Попробуйте позже.';
    }
  }
}
?>

<!doctype html>
<html lang="ru">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HTML формы с невидимой reCAPTCHA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa" crossorigin="anonymous"></script>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <script>
    function onSubmit(token) {
      document.querySelector('#form-1').submit();
    }
  </script>
</head>

<body class="pt-3">

  <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
    <symbol id="check-circle-fill" fill="currentColor" viewBox="0 0 16 16">
      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
    </symbol>
    <symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16">
      <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
    </symbol>
  </svg>

  <div class="mx-auto card border-light mb-3" style="max-width: 25em;">
    <div class="card-body">

      <?php if ($data['result'] == 'success') { ?>
        <div class="alert alert-success d-flex align-items-center" role="alert">
          <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Success:"><use xlink:href="#check-circle-fill" /></svg>
          <div>Форма успешно отправлена! Нажмите <a class="alert-link" href="<?php $_SERVER['PHP_SELF'] ?>">здесь</a>, если нужно отправить ещё одну форму.</div>
        </div>
      <?php } ?>

      <?php if ($data['result'] != 'success') { ?>
        <form id="form-1" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" novalidate>
          <!-- Имя -->
          <div class="mb-3">
            <label for="name" class="control-label">Имя</label>
            <input type="text" class="form-control<?php if (!empty($data['errors']['name'])) echo ' is-invalid' ?>" name="name" id="name" value="<?php if (!empty($data['form']['name'])) { echo $data['form']['name']; } ?>" required>
            <div class="invalid-feedback"><?php if (!empty($data['errors']['name'])) echo $data['errors']['name'] ?></div>
          </div>
          <!-- Email -->
          <div class="mb-3">
            <label for="email" class="control-label">Email</label>
            <input type="email" class="form-control<?php if (!empty($data['errors']['email'])) echo ' is-invalid' ?>" name="email" id="email" value="<?php if (!empty($data['form']['email'])) { echo $data['form']['email']; } ?>" required>
            <div class="invalid-feedback"><?php if (!empty($data['errors']['email'])) echo $data['errors']['email'] ?></div>
          </div>
          <?php if (!empty($data['errors']['form'])) { ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
              <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Danger:">
                <use xlink:href="#exclamation-triangle-fill" />
              </svg>
              <div>
                <?php echo $data['errors']['form']; ?>
              </div>
            </div>
          <?php } ?>
          <button class="btn btn-primary g-recaptcha" data-sitekey="6LeSClchAAAAAKTjOyWnKVgYVHTKHEScxp8gOSBJ" data-callback="onSubmit">Отправить</button>
        </form>
      <?php } ?>

    </div>
  </div>

</body>

</html>
