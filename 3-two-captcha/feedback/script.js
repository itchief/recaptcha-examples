var idCaptcha1, idCaptcha2;
var onloadReCaptchaInvisible = function() {
  idCaptcha1 = grecaptcha.render('recaptcha1', {
    "sitekey": "6LdXhhgUAAAAAFUToe9JV6qa19DrI2TEM3GH-l7g",
    "callback": "onSubmitReCaptcha1",
    "size": "invisible"
  });
  idCaptcha2 = grecaptcha.render('recaptcha2', {
    "sitekey": "6LdXhhgUAAAAAFUToe9JV6qa19DrI2TEM3GH-l7g",
    "callback": "onSubmitReCaptcha2",
    "size": "invisible"
  });
};

function onSubmitReCaptcha1(token) {
  var idForm = 'contactForm1';
  sendForm(document.getElementById(idForm), '/feedback/process1.php', idCaptcha1);
}

function onSubmitReCaptcha2(token) {
  var idForm = 'contactForm2';
  sendForm(document.getElementById(idForm), '/feedback/process2.php', idCaptcha2);
}

// валидация формы
var validateForm = function(feedbackForm) {
  // переменная, отвечающая за валидность формы
  var isFormValid = true;
  // проверим элементы input и textarea формы на корректнось заполнения
  $(feedbackForm).find('input,textarea').each(function() {
    //найти form-group (для установления success/error)
    var formGroup = $(this).parents('.form-group');
    //найти glyphicon
    var glyphicon = formGroup.find('.form-control-feedback');
    //валидация данных посредством HTML5 функции checkValidity
    if (this.checkValidity()) {
      //установим зелёный цвет элементу
      formGroup.addClass('has-success').removeClass('has-error');
      //добавим иконку OK        
      if ($(this).prop("tagName").toLowerCase() != 'textarea') {
        glyphicon.addClass('glyphicon-ok').removeClass('glyphicon-remove');
      }
    } else {
      //установим красный цвет элементу
      formGroup.addClass('has-error').removeClass('has-success');
      //добавим иконку Remove
      if ($(this).prop("tagName").toLowerCase() != 'textarea') {
        glyphicon.addClass('glyphicon-remove').removeClass('glyphicon-ok');
      }
      //отметим форму как не валидную
      isFormValid = false;
    }
  });
  return isFormValid;
};
// подготовка данных формы
var prepareDataForm = function(feedbackForm, captchaID) {
    // создаём экземпляр объекта FormData
    var formData = new FormData(feedbackForm);
    // добавим ответ invisible reCaptcha
    formData.append('g-recaptcha-response', grecaptcha.getResponse(captchaID));
    return formData;
  }
  // отправка формы через AJAX
var sendForm = function(feedbackForm, url, captchaID) {
  $.ajax({
    type: "POST",
    url: url,
    data: prepareDataForm(feedbackForm, captchaID),
    contentType: false,
    processData: false,
    cache: false,
    success: function(data) {
      var data = JSON.parse(data);
      $(feedbackForm).find('.error').text('');
      if (data.result == "success") {
        $(feedbackForm).hide();
        $(feedbackForm).parent().find('.success-send').removeClass('hidden');
      } else {
        var errors = '<p>Отправить форму не удалось!</p>';
        for (var error in data) {
          if (error == 'result') {
            continue;
          }
          errors += '<p>' + data[error] + '</p>';
        }
        $(feedbackForm).find('.error').html(errors);
      }
    },
    error: function(request) {
      $(feedbackForm).find('.error').text('Произошла ошибка ' + request.responseText + ' при отправке данных.');
    }
  });
}

//после загрузки веб-страницы
$(function() {

  // при отправке формы messageForm на сервер (id="messageForm")
  $('#contactForm1').submit(function(event) {
    // отменим отправку форму на сервер
    event.preventDefault();
    if (validateForm(this)) {
      // вызываем invisible reCaptcha      
      grecaptcha.execute(idCaptcha1);
    }
  });
  // при отправке формы messageForm на сервер (id="messageForm")
  $('#contactForm2').submit(function(event) {
    // отменим отправку форму на сервер
    event.preventDefault();
    if (validateForm(this)) {
      // вызываем invisible reCaptcha      
      grecaptcha.execute(idCaptcha2);
    }
  });

});