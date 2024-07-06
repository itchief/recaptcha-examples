<?php

const SECRET_KEY = 'you_secret_key';

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// если запрос был отправлен методом POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $data = [
    'success' => true,
    'errors' => [],
    'message' => '',
  ];
  // переменная, которая будем содержать результат
  $gRecaptchaResponse = $_POST['g-recaptcha-response'];
  $remoteIp = $_SERVER['REMOTE_ADDR'];
  // проверяем ответ невидимой Google reCAPTCHA
  $recaptcha = new \ReCaptcha\ReCaptcha(SECRET_KEY);
  $resp = $recaptcha->setExpectedHostname('localhost')
                    ->verify($gRecaptchaResponse, $remoteIp);
  if (!$resp->isSuccess()) {
    $errors = $resp->getErrorCodes();
    $data['success'] = false;
    $data['message'] = 'Капча не пройдена.';
    error_log('reCAPTCHA: ' . print_r($errors, true));
  }
  if (!(isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))) {
    $data['success'] = false;
    $data['errors']['email'] = 'Email не корректный.';
  }
  if (!(isset($_POST['name']) && mb_strlen($_POST['name']) > 2)) {
    $data['success'] = false;
    $data['errors']['name'] = 'Имя должно быть от 3 символов.';
  }
  // отправляем письмо на email
  if ($data['success']) {
    $mail = new PHPMailer(true);
    try {
      // Настройки сервера
      $mail->SMTPDebug = SMTP::DEBUG_OFF;
      $mail->isSMTP();
      $mail->Host = 'ssl://smtp.yandex.ru'; // SMTP-сервер
      $mail->SMTPAuth = true;
      $mail->Username = 'alexander...@yandex.ru'; // имя пользователя
      $mail->Password = '...'; // SMTP-пароль
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
      $mail->Port = 465; // TCP-порт

      // От кого отправить
      $mail->setFrom('alexander...@yandex.ru', 'Александр');
      // Кому отправить
      $mail->addAddress('alexander...@gmail.com');

      $mail->CharSet = 'utf-8';
      $mail->Encoding = 'base64';
      $mail->isHTML(true);
      $mail->Subject = 'Сообщение с формы обратной связи';
      $mail->Body = '<p>Пользователь для связи оставил следующие данные:</p><ul><li><b>Имя:</b> ' . $_POST['name'] . '</li><li><b>Email:</b> ' . $_POST['email'] . '</li></ul>';

      $mail->send();
      $data['success'] = true;
    } catch (Exception $e) {
      error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
      $data['message'] = 'Произошла ошибка при отправке почты. Попробуйте позже.';
    }
  }
}
?>

<!doctype html>
<html lang="ru">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Защита формы на сайте от спама с помощью невидимой reCAPTCHA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body class="pt-3">

  <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
    <symbol id="check-circle-fill" viewBox="0 0 16 16">
      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
    </symbol>
    <symbol id="exclamation-triangle-fill" viewBox="0 0 16 16">
      <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
    </symbol>
  </svg>

  <div class="mx-auto card border-light mb-3" style="max-width: 25em;">
    <div class="card-body">

      <?php if (isset($data['success']) && $data['success']) : ?>
        <div class="alert alert-success d-flex align-items-center" role="alert">
          <svg class="bi flex-shrink-0 me-3" width="24" height="24" role="img" aria-label="Success:"><use xlink:href="#check-circle-fill" /></svg>
          <div>Форма успешно отправлена! Нажмите <a class="alert-link" href="<?php $_SERVER['PHP_SELF'] ?>">здесь</a>, если нужно отправить ещё одну форму.</div>
        </div>
      <?php endif; ?>

      <?php if (!isset($data['success']) || !$data['success']) : ?>
        <form id="form" action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
          <!-- Имя -->
          <div class="mb-3">
            <label for="name" class="form-label">Имя</label>
            <input type="text" class="form-control<?= isset($data['errors']['name']) ? ' is-invalid' : '' ?>" name="name" id="name" value="<?= isset($data['success']) ? $_POST['name'] : '' ?>" required>
            <div class="invalid-feedback"><?= isset($data['errors']['name']) ? $data['errors']['name'] : '' ?></div>
          </div>
          <!-- Email -->
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control<?= isset($data['errors']['email']) ? ' is-invalid' : '' ?>" name="email" id="email" value="<?= isset($data['success']) ? $_POST['email'] : '' ?>" required>
            <div class="invalid-feedback"><?= isset($data['errors']['email']) ? $data['errors']['email'] : '' ?></div>
          </div>
          <?php if (isset($data['success']) && !$data['success'] && count($data['errors']) == 0) : ?>
            <div class="alert alert-warning d-flex align-items-center" role="alert">
              <svg class="bi flex-shrink-0 me-3" width="24" height="24" role="img" aria-label="Danger:">
                <use xlink:href="#exclamation-triangle-fill" />
              </svg>
              <div>
                <?= empty($data['message']) ? 'Произошла неизвестная ошибка при отправке формы.' : $data['message'] ?>
              </div>
            </div>
          <?php endif ?>
          <button type="submit" class="btn btn-primary g-recaptcha" data-sitekey="your_site_key" data-callback="onSubmit">Отправить</button>
        </form>
      <?php endif; ?>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script>
    function onSubmit(token) {
      document.querySelector('#form').submit();
    }
  </script>

</body>

</html>
