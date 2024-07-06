<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$mail = new PHPMailer(true);

try {
  $mail->SMTPDebug = SMTP::DEBUG_SERVER;
  $mail->isSMTP();
  $mail->Host = 'ssl://smtp.yandex.ru'; // SMTP-сервер
  $mail->SMTPAuth = true;
  $mail->Username = 'alexander...@yandex.ru'; // имя пользователя
  $mail->Password = '....'; // пароль
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  $mail->Port = 465; // // TCP-порт

  $mail->setFrom('alexander...@yandex.ru', 'Александр');  // от кого отправить
  $mail->addAddress('alexander...@gmail.com'); // кому отправить

  $mail->CharSet = 'utf-8';
  $mail->Encoding = 'base64';
  $mail->isHTML(true);
  $mail->Subject = 'Сообщение с формы обратной связи'; // тема
  $mail->Body = 'Это текст HTML-сообщения, у которого <b>эта часть выделена жирным начертанием!</b>'; // содержимое

  $mail->send();
  echo 'Сообщение было отправлено!';
} catch (Exception $e) {
  echo "Сообщение не удалось отправить. Ошибка: {$mail->ErrorInfo}";
}
