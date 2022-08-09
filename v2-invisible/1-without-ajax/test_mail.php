<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

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
  $mail->Body = 'Это текст HTML-сообщения, у которого <b>эта часть выделена жирным начертанием!</b>';

  $mail->send();
  echo 'Сообщение было отправлено!';
} catch (Exception $e) {
  echo "Сообщение не удалось отправить. Ошибка: {$mail->ErrorInfo}";
}
