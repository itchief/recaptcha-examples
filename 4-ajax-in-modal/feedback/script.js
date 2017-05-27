// максимальное количество файлов
var countFiles = 5;
// типы разрешённых файлов
var typeFile = ['jpg', 'jpeg', 'gif', 'png', 'avi', 'mkv', 'mp4'];
// максимльный размер
var maxSizeFile = 104857600;

function onSubmitReCaptcha(token) {
  var idForm = 'messageForm';
  sendForm(document.getElementById(idForm), '/feedback/process.php');
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
var prepareDataForm = function(feedbackForm) {
  // создаём экземпляр объекта FormData
  var formData = new FormData();
  // добавим в него значения полей
  if ($(feedbackForm).find('[name="name"]').length == 1) {
    formData.append('name', $(feedbackForm).find('[name="name"]').val());
  }
  if ($(feedbackForm).find('[name="email"]').length == 1) {
    formData.append('email', $(feedbackForm).find('[name="email"]').val());
  }
  if ($(feedbackForm).find('[name="message"]').length == 1) {
    formData.append('message', $(feedbackForm).find('[name="message"]').val());
  }
  if ($(feedbackForm).find('[name="files[]"]').length >= 1) {
    var files = $(feedbackForm).find('[name="files[]"]');
    for (var i = 0; i < files.length; i++) {
      var fileList = files[i].files;
      if (fileList.length > 0) {
        var file = fileList[0];
        if (($.inArray(file.name.split('.').pop().toLowerCase(), typeFile) >= 0) && (file.size < 104857600)) {
          formData.append('files[]', file, file.name);
        }
      }
    }
  }
  // добавим ответ invisible reCaptcha
  formData.append('g-recaptcha-response', grecaptcha.getResponse());
  return formData;
}

// отправка формы через AJAX
var sendForm = function(feedbackForm, url) {
  $.ajax({
    type: "POST",
    url: url,
    data: prepareDataForm(feedbackForm),
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
  // отображаем на форме максимальное количество файлов
  $('#countFiles').text(countFiles);
  // при изменения значения элемента "Выбрать файл"
  $(document).on('change', 'input[name="files[]"]', function(e) {
    // если выбран файл, то добавить ещё элемент "Выбрать файл"
    if ((e.target.files.length > 0) && ($(this).next('p').next('input[name="files[]"]').length == 0) && ($('input[name="files[]"]').length < countFiles)) {
      $(this).next('p').after('<input type="file" name="files[]"><p style="margin-top: 3px; margin-bottom: 3px; color: #ff0000;"></p>');
    }
    // если выбран файл, то..
    if (e.target.files.length > 0) {
      // получить файл
      var file = e.target.files[0];
      // проверить размер файла
      if (file.size > maxSizeFile) {
        $(this).next('p').text('* Файл не будет отправлен, т.к. его размер больше 512Кбайт');
      }
      // проверить тип файла
      else if ($.inArray(file.name.split('.').pop().toLowerCase(), typeFile) == -1) {
        $(this).next('p').text('* Файл не будет отправлен, т.к. его тип не соответствует разрешённому');
      } else {
        // убираем сообщение об ошибке
        if ($(this).next('p')) {
          $(this).next('p').text('');
        }
      }
    } else {
      // если после изменения файл не выбран, то сообщаем об этом пользователю
      $(this).next('p').text('* Файл не будет отправлен, т.к. он не выбран');
    }
  });

  // при отправке формы messageForm на сервер (id="messageForm")
  $('#messageForm').submit(function(event) {
    // отменим отправку форму на сервер
    event.preventDefault();
    if (validateForm(this)) {
      // вызываем invisible reCaptcha      
      grecaptcha.execute();
    }
  });

});