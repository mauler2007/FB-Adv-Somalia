document.addEventListener("DOMContentLoaded", function () {
  let checkEmail = () => {
    const emailInput = document.querySelector("#email"),
      submitBtn = document.querySelector(".sub-form");

    const reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;

    emailInput.addEventListener("input", function () {
      submitBtn.disabled = true;

      if (reg.test(emailInput.value) == false) {
        emailInput.classList.add("err-val");
        return false;
      } else {
        emailInput.classList.remove("err-val");
        submitBtn.disabled = false;
      }
    });
  };
  checkEmail();

  var countryData = window.intlTelInputGlobals.getCountryData(), // получить данные о стране из плагина
    // список-выборка отображаемых стран
    ONLYCOUNTRIESLIST = [
      "al",
      "dz",
      "as",
      "ad",
      "ao",
      "ai",
      "ag",
      "ar",
      "am",
      "aw",
      "az",
      "bs",
      "bh",
      "bd",
      "bb",
      "by",
      "bz",
      "bj",
      "bm",
      "bt",
      "bo",
      "ba",
      "bw",
      "br",
      "io",
      "bn",
      "bf",
      "bi",
      "kh",
      "cm",
      "ca",
      "cv",
      "ky",
      "cf",
      "td",
      "cl",
      "co",
      "km",
      "cg",
      "ck",
      "cr",
      "ci",
      "dj",
      "dm",
      "do",
      "ec",
      "eg",
      "sv",
      "gq",
      "et",
      "fo",
      "fj",
      "gf",
      "pf",
      "ga",
      "gm",
      "ge",
      "gh",
      "gi",
      "gl",
      "gd",
      "gp",
      "gu",
      "gt",
      "gg",
      "gn",
      "gw",
      "gy",
      "ht",
      "hn",
      "hk",
      "is",
      "in",
      "id",
      "ir",
      "ie",
      "jm",
      "jp",
      "je",
      "jo",
      "kz",
      "ke",
      "ki",
      "xk",
      "kw",
      "la",
      "ls",
      "li",
      "mk",
      "mg",
      "mw",
      "my",
      "mv",
      "ml",
      "mh",
      "mq",
      "mr",
      "mu",
      "mx",
      "fm",
      "md",
      "mc",
      "mn",
      "me",
      "ms",
      "ma",
      "mz",
      "mm",
      "na",
      "nr",
      "np",
      "nc",
      "nz",
      "ni",
      "ne",
      "ng",
      "nu",
      "nf",
      "mp",
      "om",
      "pk",
      "pw",
      "pg",
      "py",
      "pe",
      "ph",
      "pr",
      "qa",
      "re",
      "rw",
      "sh",
      "kn",
      "lc",
      "pm",
      "vc",
      "ws",
      "sm",
      "st",
      "sa",
      "sn",
      "rs",
      "sc",
      "sl",
      "sg",
      "sb",
      "za",
      "kr",
      "lk",
      "sr",
      "sz",
      "tw",
      "tj",
      "tz",
      "th",
      "tl",
      "tg",
      "tk",
      "to",
      "tt",
      "tn",
      "tr",
      "tm",
      "tc",
      "tv",
      "vi",
      "ug",
      "ua",
      "ae",
      "uy",
      "uz",
      "vu",
      "ve",
      "vn",
      "wf",
      "eh",
      "zm",
      "zw",
      "de",
      "pt",
      "at",
    ],
    CURRENCYLIST = ["usd", "brl", "try", "inr", "eur", "uah"],
    input = document.getElementById("phone"),
    addressDropdown = document.getElementById("country"),
    currencyList = document.getElementById("currency"),
    btnSubmit = document.querySelector(".sub-form"),
    errorMsg = document.getElementById("error-msg"),
    validMsg = document.getElementById("valid-msg"),
    swicthLinks = document.querySelectorAll(".form-tabs__switches a"),
    promoSwitch = document.getElementById("checkPromo"),
    form = document.getElementById("tabsForm"),
    authLink = document.getElementById("authLink"),
    authLinkHref = authLink.getAttribute("href"),
    promoInput = document.getElementById("promo"),
    rezult = document.querySelector(".rezult"),
    currentCountry = document.getElementById("currentCountry").value;
  // console.log(currentCountry)

  const newCountryData = countryData.filter(function (item) {
    return ONLYCOUNTRIESLIST.indexOf(item.iso2) !== -1;
  });

  // здесь индекс соответствует коду ошибки, возвращаемому из getValidationError - см. readmevar

  function getLangValue() {
    let cookies = document.cookie,
      allCookies = cookies.split(";");

    // console.log(allCookies.length)

    for (everyCookie of allCookies) {
      let cookie = everyCookie.trim().split("=");
      // console.log('cookie', cookie)

      if (cookie[0] == "content_lang") {
        langFromCookies = cookie[1];
        return langFromCookies;
      }
    }
    return;
  }

  langFromCookies = getLangValue();

  switch (langFromCookies) {
    case "ru":
      errorMap = [
        "не верный номер",
        "не верный код страны",
        "cлишком короткий",
        "cлишком длинный",
        "не верный номер",
      ];
      break;

    case "tr":
      errorMap = [
        "Geçersiz numara",
        "Geçersiz ülke kodu",
        "Çok kısa",
        "Çok uzun",
        "Geçersiz numara",
      ];
      break;

    case "br":
      errorMap = [
        "número inválido",
        "código de país inválido",
        "muito curto",
        "demasiado longo",
        "número inválido",
      ];
      break;

    case "pt":
      errorMap = [
        "Número inválido",
        "Código de país inválido",
        "Muito curto",
        "Demasiado longo",
        "Número inválido",
      ];
      break;

    case "en":
      errorMap = [
        "Invalid number",
        "Invalid country code",
        "Too short",
        "Too long",
        "Invalid number",
      ];
      break;

    case "de":
      errorMap = [
        "falsche Nummer",
        "falsche Landesvorwahl",
        "zu kurz",
        "zu lang",
        "falsche Nummer",
      ];
      break;

    case "gr":
      errorMap = [
        "λάθος αριθμός",
        "λάθος κωδικός χώρας",
        "πολύ σύντομο",
        "πολύ μεγάλος",
        "λάθος αριθμός",
      ];
      break;

    case "es":
      errorMap = [
        "número incorrecto",
        "código de país incorrecto",
        "demasiado corto",
        "demasiado largo",
        "número incorrecto",
      ];
      break;

    case "fr":
      errorMap = [
        "numéro incorrect",
        "mauvais code pays",
        "trop court",
        "trop long",
        "numéro incorrect",
      ];
      break;

    case "az":
      errorMap = [
        "səhv nömrə",
        "səhv ölkə kodu",
        "çox qısa",
        "çox uzun",
        "səhv nömrə",
      ];
      break;

    case "in":
      errorMap = [
        "गलत संख्या",
        "गलत देश कोड",
        "बहुत छोटा",
        "बहुत लंबा",
        "गलत संख्या",
      ];
      break;

    default:
      errorMap = [
        "Invalid number",
        "Invalid country code",
        "Too short",
        "Too long",
        "Invalid number",
      ];
  }

  // инициализация плагина

  var iti = window.intlTelInput(input, {
    preferredCountries: ["br", "tr", "ua", "in"],
    separateDialCode: true, // отображение кода страны возле флага
    onlyCountries: ONLYCOUNTRIESLIST,
    initialCountry: currentCountry,
    utilsScript: "js/utils.js", // только для форматирования/плейсхолдера и т.д.
  });

  iti.promise.then(function () {
    // $("#country, #currency").styler();
    // $("#country").styler();

  });

//   const parentCurrencyDropdpwn = document.querySelector('#surrencyParent'); 
  
//   $('.js-example-basic-single').select2({
//     placeholder: 'Select an option',
//     dropdownAutoWidth: true,
//     dropdownCssClass: 'noclasss',
//     debug: true,
//     // width : 'resolve',
//     // minimumInputLength: 1 ,// only start searching when the user has input 3 or more characters
//     // minimumResultsForSearch: 2, // at least 20 results must be displayed,
//     dropdownParent: $(parentCurrencyDropdpwn),
//   });


  // заполнить выпадающий список стран
  for (var i = 0; i < newCountryData.length; i++) {
    var country = newCountryData[i];
    var optionNode = document.createElement("option");
    optionNode.value = country.iso2;
    var textNode = document.createTextNode(country.name);
    optionNode.appendChild(textNode);
    addressDropdown.appendChild(optionNode);
  }

  // установить его начальное значение

  addressDropdown.value = iti.getSelectedCountryData().iso2;

  CURRENCYLIST.forEach(function (i) {
    currencyList.insertAdjacentHTML(
      "beforeend",
      `<option value="${i}">${i}</option>`
    );
  });

  // переключение валюты в зависимости от активной (выбранной) страны
  function currencySwitch(country) {
    const currencyList_items = currencyList.querySelectorAll("option");
    const currencyList_items_LIB = document.querySelectorAll(
      "#currency-styler .jq-selectbox__dropdown li"
    );
    const currencyActive_items_LIB = document.querySelector(
      "#currency-styler .jq-selectbox__select-text"
    );
    switch (country) {
      case "ua":
        currencyList_items.forEach(function (el) {
          el.value === "uah" ? (el.selected = true) : "";
        });
        currencyList_items_LIB.forEach(function (el) {
          el.innerHTML === "uah"
            ? ((el.className = "selected sel"),
              (currencyActive_items_LIB.innerHTML = "uah"))
            : (el.className = "");
        });
        break;

      case "in":
        currencyList_items.forEach(function (el) {
          el.value === "inr" ? (el.selected = true) : "";
        });
        currencyList_items_LIB.forEach(function (el) {
          el.innerHTML === "inr"
            ? ((el.className = "selected sel"),
              (currencyActive_items_LIB.innerHTML = "inr"))
            : (el.className = "");
        });
        break;

      case "tr":
        currencyList_items.forEach(function (el) {
          el.value === "try" ? (el.selected = true) : "";
        });
        currencyList_items_LIB.forEach(function (el) {
          el.innerHTML === "try"
            ? ((el.className = "selected sel"),
              (currencyActive_items_LIB.innerHTML = "try"))
            : (el.className = "");
        });
        break;

      case "br":
        currencyList_items.forEach(function (el) {
          el.value === "brl" ? (el.selected = true) : "";
        });
        currencyList_items_LIB.forEach(function (el) {
          el.innerHTML === "brl"
            ? ((el.className = "selected sel"),
              (currencyActive_items_LIB.innerHTML = "brl"))
            : (el.className = "");
        });
        break;

      case ("ro", "de", "at"):
        currencyList_items.forEach(function (el) {
          el.value === "eur" ? (el.selected = true) : "";
        });
        currencyList_items_LIB.forEach(function (el) {
          el.innerHTML === "eur"
            ? ((el.className = "selected sel"),
              (currencyActive_items_LIB.innerHTML = "eur"))
            : (el.className = "");
        });
        break;

      default:
        document.getElementById("currency").options[0].selected = true;
    }
  }

  // var activeCountry = iti.defaultCountry; // получение кода активной (выбранной) страны

  // currencySwitch(activeCountry);

  // отслеживаем изменения телефонного кода
  input.addEventListener("countrychange", function (e) {
    console.log("changed-phone1");
    addressDropdown.value = iti.getSelectedCountryData().iso2;
    currencySwitch(addressDropdown.value);
    $("select").trigger("refresh");
  });

  // setTimeout(() => {
  //   let test = document.getElementById('country-styler')
  //   console.log('changed-test')

  // }, 1000);

  // console.log(addressDropdown)
  // отлслеживаем изменения в выпадающем списке стран

  $(addressDropdown).change(function () {
    console.log("changed-country");
    iti.setCountry(this.value);
    var activeCountry = iti.defaultCountry; // получение кода активной (выбранной) страны
    currencySwitch(activeCountry);
  });

  // функция вадлидации для ввода номера телефона. активирует кнопку отправки при успешном вводе номера
  function validatAction() {
    input.value = input.value.replace(/[^\d]/g, "");
    if (iti.isValidNumber()) {
      errorMsg.classList.add("hide");
      validMsg.classList.remove("hide");
      btnSubmit.classList.remove("sub-form-disabled");
      btnSubmit.disabled = false;
    } else {
      input.classList.add("error");
      validMsg.classList.add("hide");
      btnSubmit.classList.add("sub-form-disabled");
      btnSubmit.disabled = true;
      var errorCode = iti.getValidationError();
      errorMsg.innerHTML = errorMap[errorCode];
      errorMsg.classList.remove("hide");
    }
  }

  // запуск функции валидации формы
  input.addEventListener("input", validatAction);
  input.addEventListener("change", validatAction);
  input.addEventListener("keyup", validatAction);

  // обработчик отправки формы
  function submit() {
    var request = new XMLHttpRequest(),
      form = document.getElementById("tabsForm"),
      formData = new FormData(form);

    formData.delete("currentCountry");
    formData.set("country", formData.get("country").toUpperCase());
    formData.set("currency", formData.get("currency").toUpperCase());

    request.onload = function () {
      if (request.status === 200) {
        var req = JSON.parse(request.responseText),
          success = req["deposit"];

        if (success) {
          rezult.innerHTML = `
              <div style="text-transform: uppercase; color: green">thank you for registering </div>
              `;
          var deposit = req["deposit"],
            newAuthLinkHref = `${authLinkHref}/${deposit}`;

          if (deposit) {
            setTimeout(function () {
              document.location.href = newAuthLinkHref;
              // window.open(newAuthLinkHref);
            }, 2000);
          }
        } else {
          var message = req["message"];
          rezult.innerHTML = `
              <div style="text-transform: uppercase; color: red"> ${message} </div>
              `;
        }
        rezult.classList.add("rezult--show6sec");
      }
    };
    request.open("POST", "registration-form-handler.php", true);
    request.send(formData);
  }

  btnSubmit.addEventListener("click", submit);

  // переключение табов формы для выбора регистрации по телефону или по почте
  swicthLinks.forEach(function (el) {
    var gmail = "switchGmail",
      phone = "switchPhone";
    el.addEventListener("click", function () {
      if (
        !this.classList.contains("active") &&
        this.getAttribute("id") == gmail
      ) {
        for (let i = 0; i < swicthLinks.length; i++) {
          if (swicthLinks[i].getAttribute("id") !== gmail) {
            swicthLinks[i].classList.remove("active");
          }
        }
        this.classList.add("active");
        document.querySelector("." + gmail).classList.remove("hide");
        document.querySelector("." + phone).classList.add("hide");
      } else if (
        !this.classList.contains("active") &&
        this.getAttribute("id") == phone
      ) {
        for (let i = 0; i < swicthLinks.length; i++) {
          if (swicthLinks[i].getAttribute("id") !== phone) {
            swicthLinks[i].classList.remove("active");
          }
        }
        this.classList.add("active");
        document.querySelector("." + phone).classList.remove("hide");
        document.querySelector("." + gmail).classList.add("hide");
      }
    });
  });

  let visitorIdInp = document.getElementById("visitorId");
  // Initialize the agent at application startup.
  const fpPromise = import("https://openfpcdn.io/fingerprintjs/v3").then(
    (FingerprintJS) => FingerprintJS.load()
  );

  // Get the visitor identifier when you need it.
  fpPromise
    .then((fp) => fp.get())
    .then((result) => {
      // This is the visitor identifier:
      const visitorId = result.visitorId;
      visitorIdInp.value = visitorId;
    })
    .catch((error) => console.error(error));

  // показать изначально поле с промокодом
  function showPromocodeInput() {
    promoSwitch.checked = "checked";
    promoInput.className = "promo-show";
    promoInput.disabled = false;
  }

  promoSwitch ? showPromocodeInput() : null;

  // кастомная кнопка для формы пока нет брендбука
  function customFormBtnInit() {
    const text = btnSubmit.textContent;

    btnSubmit.classList.add("pulse-button");
    btnSubmit.classList.add("custom-btn");
    btnSubmit.classList.add("btn-7");
    btnSubmit.innerHTML = `<span>${text}</span>`;
  }

  customFormBtnInit();

  document.forms[0].append(rezult);
});
