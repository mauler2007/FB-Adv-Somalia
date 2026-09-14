<?php

$date = date("Ymdhis"); // получаем дату в формате ( год месяц день час минута секунда )
$updateFile = "?v=$date"; // содаем запись из конкатенации: (приставка версии + дата)
// Массив доступных для выбора языков
$langArray = array("en", "ru", "uz", "so", "az");

include '../__LOCALISATIONS__/processingRegion.php';
include '../__LOCALISATIONS__/processingLang.php';
include '../__LOCALISATIONS__/processingBrowser.php';
include '../__LINKS__/rootURL.php';

include_once("languages/lang-" . $contentLang . ".php");

$mobCashSite_URL_reg = 'https://mobcash.betandyou.info/become-an-agent/?' . $tag;
// $mobCashSite_URL = 'https://mobcash.betandyou.info/?' . $tag;

// Bonus mount  depends of GEO  start
$specialBonusRegions = ["uz", "az"];
$depositBonus;
if (in_array($region, $specialBonusRegions, true)) {
    $depositBonus = '8%';
    $local['step_3'] = str_replace('5%', $depositBonus, $local['step_3']);
} else {
    $depositBonus = '5%';
}
// Bonus mount  depends of GEO  end


// Ambassaddor  depends of GEO  start
$region = strtolower($region ?? 'en');
$availableAmbassadorRegions = ['so', 'en', 'uz', 'az'];
$ambassadorRegion = in_array($region, $availableAmbassadorRegions, true)
    ? $region
    : 'en';
function ambassadorBg($size, $region)
{
    return "./images/meta/ambassadors/bg-{$size}{$region}";
}
// Ambassaddor  depends of GEO  end

$tg_link = 'https://t.me/mobcash_ewallets';
$wats_app_link = 'https://wa.me/35795919967';
$e_mail = 'mailto:agent@betandyou.com';

?>
<!DOCTYPE html>
<html lang="<?= $contentLang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" sizes="32x32" href="images/favicons/favicon-32x32.png?v=1">
    <link rel="icon" sizes="16x16" href="images/favicons/favicon-16x16.png?v=1">
    <link rel="apple-touch-icon" sizes="180x180" href="images/favicons/apple-touch-icon.png?v=1">
    <link rel="shortcut icon" href="images/favicons/favicon.ico?v=1">
    <meta name="theme-color" content="#1e0b0b">
    <meta http-equiv="Cache-Control" content="max-age=600">
    <meta http-equiv="Expires" content="600">
    <meta name="keywords" content="" />
    <meta name="description" content="">
    <title>Test FB Adv Somalia</title>
    <link rel="stylesheet" href="css/style.min.css<?= $updateFile ?>">
</head>

<body class="body">
    <div class="wrapper" style="opacity: 1;">
        <main class="main">
            <section class="top">
                <header class="header">
                    <div class="container">
                        <div class="header__inner">
                            <div class="header__left">
                                <div class="logo">
                                    <div class="logo__link" href="#">Logotype
                                        <svg viewBox="0 0 114 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M0.440375 0.27354H6.20746C7.48216 0.27354 8.50782 0.587571 9.28587 1.21703C10.0639 1.84649 10.4536 2.60774 10.4536 3.50216C10.4536 4.03769 10.3186 4.51995 10.0512 4.95175C9.78252 5.38213 9.3928 5.7242 8.88348 5.97795C9.59258 6.19945 10.1385 6.57377 10.5198 7.10369C10.9025 7.63362 11.0938 8.26028 11.0938 8.98367C11.0938 10.0659 10.697 10.9632 9.90493 11.6739C9.11281 12.3861 8.08996 12.7422 6.83918 12.7422H0.440375C0.312343 12.7422 0.208229 12.7016 0.125219 12.6216C0.0422085 12.5417 0 12.4408 0 12.3188V0.712341C0 0.584767 0.0408015 0.481025 0.120998 0.398311C0.201194 0.315598 0.308122 0.27354 0.440375 0.27354ZM7.1459 4.01526C7.1459 3.69563 7.02349 3.43907 6.78009 3.24561C6.53669 3.05214 6.19902 2.95541 5.76568 2.95541H3.20644V5.11577H5.76568C6.19761 5.11577 6.53528 5.01483 6.78009 4.81296C7.02349 4.61108 7.1459 4.34471 7.1459 4.01386V4.01526ZM7.60316 8.86731C7.60316 8.48598 7.4709 8.19158 7.2078 7.98129C6.9447 7.77101 6.57749 7.66726 6.10616 7.66726H3.20644V10.0772H6.10616C6.57186 10.0772 6.93767 9.96922 7.20358 9.75472C7.4695 9.53883 7.60175 9.24442 7.60175 8.86871L7.60316 8.86731Z"
                                                fill="white" />
                                            <path
                                                d="M13.706 0.27354H23.1959C23.3239 0.27354 23.4294 0.318402 23.5153 0.405321C23.6011 0.493642 23.6447 0.60159 23.6447 0.727762V2.77316C23.6447 2.89513 23.6011 2.99747 23.5153 3.08299C23.4294 3.1685 23.3225 3.21196 23.1959 3.21196H16.4735V5.08353H21.8748C21.9972 5.08353 22.0999 5.12839 22.1857 5.21531C22.2715 5.30363 22.3151 5.41158 22.3151 5.53775V7.41772C22.3151 7.5453 22.2715 7.65044 22.1857 7.73596C22.0999 7.82147 21.9957 7.86493 21.8748 7.86493H16.4735V9.80239H23.1959C23.3239 9.80239 23.4294 9.84585 23.5153 9.93136C23.6011 10.0169 23.6447 10.1234 23.6447 10.2496V12.295C23.6447 12.417 23.6011 12.5207 23.5153 12.609C23.4294 12.6974 23.3225 12.7422 23.1959 12.7422H13.706C13.5836 12.7422 13.4809 12.7002 13.3951 12.6132C13.3092 12.5277 13.2656 12.4212 13.2656 12.295V0.712341C13.2656 0.590374 13.3078 0.488034 13.3951 0.402517C13.4823 0.317 13.585 0.27354 13.706 0.27354Z"
                                                fill="white" />
                                            <path
                                                d="M25.3686 2.77316V0.727762C25.3686 0.600188 25.4122 0.493642 25.5023 0.405321C25.5909 0.317 25.6992 0.27354 25.8259 0.27354H35.6801C35.8082 0.27354 35.9137 0.318402 35.9995 0.405321C36.0853 0.493642 36.1289 0.60159 36.1289 0.727762V2.77316C36.1289 2.90074 36.0853 3.00448 35.9995 3.08719C35.9137 3.16991 35.8068 3.21196 35.6801 3.21196H32.4062V12.295C32.4062 12.4226 32.3625 12.5277 32.2767 12.6132C32.1909 12.6988 32.0868 12.7422 31.9658 12.7422H29.5388C29.4108 12.7422 29.3038 12.7002 29.2152 12.6132C29.1266 12.5277 29.0815 12.4212 29.0815 12.295V3.21196H25.8244C25.6964 3.21196 25.5895 3.16991 25.5008 3.08299C25.4122 2.99747 25.3672 2.89373 25.3672 2.77316H25.3686Z"
                                                fill="white" />
                                            <path
                                                d="M39.4918 10.6562L38.9346 12.3539C38.8952 12.4815 38.8319 12.5768 38.7433 12.6441C38.6547 12.71 38.5435 12.7436 38.4113 12.7436H35.9013C35.7015 12.7436 35.565 12.6791 35.4904 12.5487C35.4159 12.4198 35.4201 12.2459 35.5031 12.0314L39.7408 0.729164C39.7901 0.586168 39.8675 0.474015 39.9688 0.394106C40.0715 0.314196 40.1812 0.27354 40.2966 0.27354H43.0472C43.1583 0.27354 43.2638 0.314196 43.3665 0.394106C43.4692 0.474015 43.5452 0.586168 43.5945 0.729164L47.8322 12.0146C47.9152 12.2249 47.9166 12.3987 47.8364 12.5361C47.7562 12.6735 47.6183 12.7436 47.4256 12.7436H44.7664C44.6286 12.7436 44.5174 12.7128 44.4344 12.6525C44.3514 12.5922 44.2881 12.4927 44.2431 12.3539L43.662 10.6562H39.4904H39.4918ZM40.3641 7.98971H42.7981L41.5769 4.29705L40.3641 7.98971Z"
                                                fill="#FFBB04" />
                                            <path
                                                d="M57.2334 12.295L53.0125 5.33167V12.3202C53.0125 12.4422 52.9731 12.5431 52.8958 12.623C52.8184 12.703 52.7157 12.7436 52.5876 12.7436H50.2451C50.117 12.7436 50.0129 12.703 49.9299 12.623C49.8469 12.5431 49.8047 12.4422 49.8047 12.3202V0.712341C49.8047 0.590374 49.8469 0.488034 49.9341 0.402517C50.0214 0.317 50.1241 0.27354 50.2451 0.27354H53.2869C53.4248 0.27354 53.5415 0.301579 53.6358 0.356254C53.7301 0.410929 53.8159 0.502053 53.8933 0.629628L57.9143 7.38548V0.712341C57.9143 0.590374 57.9566 0.488034 58.0396 0.402517C58.1226 0.317 58.2253 0.27354 58.3477 0.27354H60.6832C60.8 0.27354 60.9027 0.318402 60.9942 0.405321C61.0856 0.493642 61.1306 0.595982 61.1306 0.712341V12.3202C61.1306 12.4366 61.0856 12.5361 60.997 12.6188C60.9083 12.7016 60.8028 12.7436 60.6818 12.7436H58.1634C57.9256 12.7436 57.7343 12.7086 57.5893 12.6399C57.4458 12.5712 57.3262 12.4562 57.232 12.2964L57.2334 12.295Z"
                                                fill="#FFBB04" />
                                            <path
                                                d="M64.1044 0.27354H69.4312C71.2925 0.27354 72.8064 0.848327 73.9728 1.9951C75.1391 3.14327 75.7216 4.63631 75.7216 6.47423C75.7216 8.31215 75.1363 9.83043 73.9643 10.9954C72.7924 12.1604 71.2813 12.7422 69.4312 12.7422H64.1044C63.982 12.7422 63.8793 12.7002 63.7935 12.6132C63.7077 12.5277 63.6641 12.4212 63.6641 12.295V0.712341C63.6641 0.590374 63.7063 0.488034 63.7935 0.402517C63.8807 0.317 63.9834 0.27354 64.1044 0.27354ZM66.8719 9.80379H69.3566C70.2092 9.80379 70.9239 9.47995 71.5008 8.83086C72.0762 8.18177 72.3646 7.39669 72.3646 6.47563C72.3646 5.55457 72.0776 4.78632 71.505 4.15686C70.931 3.5274 70.2162 3.21337 69.3566 3.21337H66.8719V9.80379Z"
                                                fill="#FFBB04" />
                                            <path
                                                d="M80.0113 12.2936V7.93082L75.7736 1.00114C75.6681 0.824495 75.6512 0.659068 75.7244 0.504857C75.7961 0.350646 75.9241 0.27354 76.1071 0.27354H78.9744C79.0799 0.27354 79.1742 0.301579 79.2572 0.356254C79.3402 0.410929 79.412 0.49224 79.4739 0.595982L81.6673 4.4793H81.7095L83.8861 0.595982C83.9466 0.490838 84.0197 0.410929 84.1027 0.356254C84.1858 0.301579 84.28 0.27354 84.3855 0.27354H87.1024C87.3022 0.27354 87.4372 0.34644 87.509 0.49224C87.5807 0.63804 87.5582 0.807672 87.4428 1.00114L83.222 7.93082V12.2936C83.222 12.4156 83.1798 12.5193 83.0968 12.6076C83.0138 12.6959 82.9139 12.7408 82.7971 12.7408H80.4545C80.3321 12.7408 80.2294 12.6988 80.1436 12.6118C80.0578 12.5263 80.0142 12.4198 80.0142 12.2936H80.0113Z"
                                                fill="white" />
                                            <path
                                                d="M94.6384 0C96.5279 0 98.1248 0.629462 99.4332 1.88839C100.74 3.14731 101.395 4.68381 101.395 6.5007C101.395 8.31759 100.74 9.84708 99.4332 11.1088C98.1262 12.3705 96.5279 13 94.6384 13C92.7488 13 91.159 12.3691 89.8519 11.1088C88.5449 9.84708 87.8906 8.31198 87.8906 6.5007C87.8906 4.68942 88.5449 3.14731 89.8519 1.88839C91.159 0.629462 92.7545 0 94.6384 0ZM94.6384 10.0265C95.6077 10.0265 96.4167 9.68867 97.0653 9.01294C97.714 8.33722 98.0375 7.49887 98.0375 6.5007C98.0375 5.50253 97.7154 4.66419 97.0696 3.98846C96.4238 3.31274 95.6134 2.97487 94.6398 2.97487C93.6661 2.97487 92.8628 3.31274 92.217 3.98846C91.5712 4.66419 91.249 5.50253 91.249 6.5007C91.249 7.49887 91.5712 8.33722 92.217 9.01294C92.8628 9.68867 93.6704 10.0265 94.6398 10.0265H94.6384Z"
                                                fill="white" />
                                            <path
                                                d="M114 0.69351V8.15312C114 9.66579 113.502 10.8504 112.504 11.7098C111.507 12.5678 110.2 12.9968 108.582 12.9968C106.964 12.9968 105.658 12.5678 104.663 11.7098C103.669 10.8518 103.172 9.66579 103.172 8.15312V0.69351C103.172 0.577151 103.214 0.477614 103.301 0.390695C103.387 0.305178 103.491 0.261719 103.612 0.261719H105.939C106.062 0.261719 106.164 0.305178 106.25 0.390695C106.336 0.476212 106.38 0.577151 106.38 0.69351V7.88816C106.38 8.54986 106.578 9.07839 106.973 9.46952C107.369 9.86206 107.905 10.0569 108.582 10.0569C109.258 10.0569 109.792 9.86206 110.186 9.47373C110.579 9.084 110.775 8.55547 110.775 7.88816V0.69351C110.775 0.577151 110.819 0.477614 110.909 0.390695C110.997 0.305178 111.103 0.261719 111.224 0.261719H113.551C113.673 0.261719 113.777 0.305178 113.866 0.390695C113.955 0.476212 113.998 0.577151 113.998 0.69351H114Z"
                                                fill="white" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="soc-networks">
                                <a href="<?= $e_mail ?>">e mail<svg class="soc-networks__svg icon-gmail" width="21"
                                        height="16" viewBox="0 0 21 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M2.23125 0H18.1687C19.4004 0 20.4 0.9996 20.4 2.23125V13.0687C20.4 13.6605 20.1649 14.228 19.7465 14.6465C19.328 15.0649 18.7605 15.3 18.1687 15.3H2.23125C1.63949 15.3 1.07196 15.0649 0.653518 14.6465C0.235077 14.228 0 13.6605 0 13.0687L0 2.23125C0 0.9996 0.9996 0 2.23125 0ZM1.9125 13.07C1.9125 13.246 2.0553 13.3888 2.23125 13.3888H18.1687C18.2533 13.3888 18.3344 13.3552 18.3941 13.2954C18.4539 13.2356 18.4875 13.1546 18.4875 13.07V4.85647L10.6845 9.43118C10.5376 9.51749 10.3704 9.563 10.2 9.563C10.0296 9.563 9.86237 9.51749 9.7155 9.43118L1.9125 4.85647V13.07ZM18.4875 2.63925V2.23125C18.4875 2.14671 18.4539 2.06564 18.3941 2.00586C18.3344 1.94608 18.2533 1.9125 18.1687 1.9125H2.23125C2.14671 1.9125 2.06564 1.94608 2.00586 2.00586C1.94608 2.06564 1.9125 2.14671 1.9125 2.23125V2.63925L10.2 7.497L18.4875 2.63925Z"
                                            fill="white" />
                                    </svg></a><a href="<?= $wats_app_link; ?>">Wats App
                                    <svg class="soc-networks__svg icon-watsapp" viewBox="0 0 15 15" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M12.2113 2.09032C11.5527 1.42512 10.7683 0.897691 9.9038 0.538812C9.03928 0.179932 8.11196 -0.00321811 7.17592 4.27895e-05C3.25394 4.27895e-05 0.0574649 3.19652 0.0574649 7.11849C0.0574649 8.37554 0.387887 9.59666 1.00563 10.6741L0 14.3662L3.77113 13.375C4.81268 13.9424 5.98352 14.2441 7.17592 14.2441C11.0979 14.2441 14.2944 11.0476 14.2944 7.12568C14.2944 5.22216 13.5545 3.43356 12.2113 2.09032ZM7.17592 13.0374C6.11282 13.0374 5.07127 12.75 4.15901 12.2113L3.94352 12.082L1.70239 12.671L2.29859 10.4874L2.15493 10.2647C1.5643 9.32153 1.25068 8.23133 1.24986 7.11849C1.24986 3.85737 3.90761 1.19962 7.16873 1.19962C8.74902 1.19962 10.2359 1.81737 11.3493 2.93793C11.9006 3.48669 12.3375 4.13942 12.6346 4.85828C12.9318 5.57714 13.0833 6.34783 13.0804 7.12568C13.0948 10.3868 10.437 13.0374 7.17592 13.0374ZM10.4227 8.61258C10.2431 8.52638 9.36676 8.0954 9.20873 8.03075C9.04352 7.97328 8.92859 7.94455 8.80648 8.11695C8.68437 8.29652 8.34676 8.69878 8.2462 8.81371C8.14563 8.93582 8.03789 8.95018 7.85831 8.8568C7.67873 8.77061 7.10409 8.57666 6.42887 7.97328C5.89733 7.4992 5.54535 6.91737 5.43761 6.73779C5.33704 6.55821 5.42324 6.46483 5.51662 6.37145C5.59563 6.29244 5.6962 6.16314 5.7824 6.06258C5.86859 5.96201 5.90451 5.883 5.96197 5.76807C6.01944 5.64596 5.99071 5.5454 5.94761 5.4592C5.90451 5.373 5.54535 4.49666 5.40169 4.13751C5.25803 3.79272 5.10718 3.83582 4.99944 3.82863H4.65465C4.53254 3.82863 4.34578 3.87173 4.18056 4.05131C4.02254 4.23089 3.56282 4.66187 3.56282 5.53821C3.56282 6.41455 4.20211 7.26216 4.28831 7.37709C4.37451 7.4992 5.54535 9.29497 7.32676 10.0636C7.75056 10.2503 8.08099 10.3581 8.33958 10.4371C8.76338 10.5736 9.15127 10.552 9.46014 10.5089C9.80493 10.4586 10.5161 10.0779 10.6597 9.66131C10.8106 9.24469 10.8106 8.89272 10.7603 8.81371C10.71 8.73469 10.6023 8.69878 10.4227 8.61258Z"
                                            fill="white" />
                                    </svg> </a><a href="<?= $tg_link; ?>">telegram<svg
                                        class="soc-networks__svg icon-telegram" width="18" height="15"
                                        viewBox="0 0 18 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M15.8649 0.0938185L0.794416 5.90527C-0.234085 6.31837 -0.228134 6.89212 0.605716 7.14797L4.47492 8.35497L13.4271 2.70672C13.8504 2.44917 14.2372 2.58772 13.9193 2.86992L6.66622 9.41577H6.66452L6.66622 9.41662L6.39932 13.4048C6.79032 13.4048 6.96287 13.2255 7.18217 13.0138L9.06152 11.1863L12.9707 14.0738C13.6915 14.4707 14.2091 14.2667 14.3885 13.4065L16.9546 1.31272C17.2173 0.259569 16.5526 -0.217281 15.8649 0.0938185Z"
                                            fill="white" />
                                    </svg></a>
                            </div>
                            <div class="header__box">
                                <div class="langCheck">
                                    <div class="langCheck__wrapper">
                                        <?php include '../__LOCALISATIONS__/langSwitcher.php' ?>
                                    </div>
                                </div>
                            </div>
                            <a class="button button--yellow button--header custom-btn btn-7" href="#become">
                                <span>
                                    <?= $local['cta_secondary_button']; ?>
                                </span>
                            </a>
                        </div>
                    </div>
                </header>
                <div class="top__layout">
                    <div class="top__bg">
                        <picture>
                            <source type="image/avif" media="(min-width: 1920px)"
                                srcset="<?= ambassadorBg('1920', $ambassadorRegion); ?>.avif">
                            <source type="image/webp" media="(min-width: 1920px)"
                                srcset="<?= ambassadorBg('1920', $ambassadorRegion); ?>.webp">

                            <source type="image/avif" media="(min-width: 1440px)"
                                srcset="<?= ambassadorBg('1440', $ambassadorRegion); ?>.avif">
                            <source type="image/webp" media="(min-width: 1440px)"
                                srcset="<?= ambassadorBg('1440', $ambassadorRegion); ?>.webp">

                            <source type="image/avif" media="(min-width: 1200px)"
                                srcset="<?= ambassadorBg('1200', $ambassadorRegion); ?>.avif">
                            <source type="image/webp" media="(min-width: 1200px)"
                                srcset="<?= ambassadorBg('1200', $ambassadorRegion); ?>.webp">

                            <source type="image/avif" srcset="<?= ambassadorBg('768', $ambassadorRegion); ?>.avif">
                            <source type="image/webp" srcset="<?= ambassadorBg('768', $ambassadorRegion); ?>.webp">

                            <img src="<?= ambassadorBg('768', $ambassadorRegion); ?>.jpg" alt="men">
                        </picture>
                    </div>
                </div>
                <div class="container">
                    <div class="top__inner">
                        <div class="top__title">
                            <div class="top__title-layout"></div>

                            <!-- клас у заголовка top__heading--***   відповідає за розміри бейджа та   вкладених у h1 елементів  -->
                            <h1 class="top__heading top__heading--<?= $contentLang; ?>"
                                aria-label="<?= htmlspecialchars($local['landing_main_title_1'] . ' ' . $local['landing_main_title_2'], ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="top__heading-row">
                                    <span class="reveal-wrap top__heading-first">
                                        <span class="reveal-target"><?= $local['landing_main_title_1']; ?></span>
                                    </span>
                                    <span class="top__title-badge top__title-badge--desktop" aria-hidden="true">
                                        <?= $local['main_headline']; ?>
                                    </span>
                                </span>
                                <span class="reveal-wrap top__heading-second">
                                    <span class="reveal-target"><?= $local['landing_main_title_2']; ?></span>
                                </span>
                                <span class="top__title-badge top__title-badge--mobile-only" aria-hidden="true">
                                    <?= $local['main_headline']; ?>
                                </span>
                            </h1>
                        </div>
                        <div class="top__scene-container">
                            <div class="scene">
                                <div class="scene__item scene__item--screen animated-image">
                                    <picture>
                                        <source type="image/avif"
                                            srcset="<?= ambassadorBg('320', $ambassadorRegion); ?>.avif">
                                        <source type="image/webp"
                                            srcset="<?= ambassadorBg('320', $ambassadorRegion); ?>.webp">

                                        <img src="<?= ambassadorBg('320', $ambassadorRegion); ?>.jpg" alt="men"
                                            width="500" height="390">
                                    </picture>
                                </div>
                            </div>
                            <div class="top__btn-block"><a class="button button--yellow custom-btn btn-7"
                                    href="#become"><span><?= $local['cta_main_button']; ?></span></a>
                            </div>
                            <div class="top__scene-container-layout"></div>
                        </div>
                        <ul class="top__benefits">
                            <li class="top__benefits-item">
                                <p><?= $local['step_1']; ?></p><svg preserveAspectRatio="none" height="1"
                                    viewBox="0 0 588 1" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 0.5H588" stroke="#0B0B33" />
                                </svg>
                            </li>
                            <li class="top__benefits-item">
                                <p><?= $local['step_2']; ?></p><svg preserveAspectRatio="none" height="1"
                                    viewBox="0 0 588 1" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 0.5H588" stroke="#0B0B33" />
                                </svg>
                            </li>
                            <li class="top__benefits-item">
                                <p><?= $local['step_3']; ?></p>
                            </li>
                        </ul>
                        <ul class="top__advantages">
                            <li class="top__advantages-item top__advantages-item--1">
                                <div class="top__advantages-image">
                                    <picture>
                                        <source type="image/avif" media="(min-width: 768px)"
                                            srcset="./images/meta/advantages/adv-1-dark.avif">
                                        <source type="image/webp" media="(min-width: 768px)"
                                            srcset="./images/meta/advantages/adv-1-dark.webp">
                                        <source type="image/avif" srcset="./images/meta/advantages/adv-1.avif">
                                        <source type="image/webp" srcset="./images/meta/advantages/adv-1.webp"><img
                                            src="./images/meta/advantages/adv-1.png" alt="clock" width="50" height="50">
                                    </picture>
                                </div>
                                <p class="break-all"><?= $local['adv_1_text']; ?></p>
                            </li>
                            <li class="top__advantages-item top__advantages-item--2">
                                <div class="top__advantages-image">
                                    <picture>
                                        <source type="image/avif" media="(min-width: 768px)"
                                            srcset="./images/meta/advantages/adv-2-dark.avif">
                                        <source type="image/webp" media="(min-width: 768px)"
                                            srcset="./images/meta/advantages/adv-2-dark.webp">
                                        <source type="image/avif" srcset="./images/meta/advantages/adv-2.avif">
                                        <source type="image/webp" srcset="./images/meta/advantages/adv-2.webp"><img
                                            src="./images/meta/advantages/adv-2.png" alt="clock" width="50" height="50">
                                    </picture>
                                </div>
                                <p><?= $local['adv_2_text']; ?></p>
                            </li>
                            <li class="top__advantages-item top__advantages-item--3">
                                <div class="top__advantages-image">
                                    <picture>
                                        <source type="image/avif" media="(min-width: 768px)"
                                            srcset="./images/meta/advantages/adv-3-dark.avif">
                                        <source type="image/webp" media="(min-width: 768px)"
                                            srcset="./images/meta/advantages/adv-3-dark.webp">
                                        <source type="image/avif" srcset="./images/meta/advantages/adv-3.avif">
                                        <source type="image/webp" srcset="./images/meta/advantages/adv-3.webp"><img
                                            src="./images/meta/advantages/adv-3.png" alt="clock" width="50" height="50">
                                    </picture>
                                </div>
                                <p><?= $local['adv_3_text']; ?></p>
                            </li>
                            <li class="top__advantages-item top__advantages-item--4">
                                <div class="top__advantages-image">
                                    <picture>
                                        <source type="image/avif" media="(min-width: 768px)"
                                            srcset="./images/meta/advantages/adv-4-dark.avif">
                                        <source type="image/webp" media="(min-width: 768px)"
                                            srcset="./images/meta/advantages/adv-4-dark.webp">
                                        <source type="image/avif" srcset="./images/meta/advantages/adv-4.avif">
                                        <source type="image/webp" srcset="./images/meta/advantages/adv-4.webp"><img
                                            src="./images/meta/advantages/adv-4.png" alt="clock" width="50" height="50">
                                    </picture>
                                </div>
                                <p><?= $local['adv_4_text']; ?></p>
                            </li>
                            <li class="top__advantages-item top__advantages-item--5">
                                <div class="top__advantages-image">
                                    <picture>
                                        <source type="image/avif" media="(min-width: 768px)"
                                            srcset="./images/meta/advantages/adv-5-dark.avif">
                                        <source type="image/webp" media="(min-width: 768px)"
                                            srcset="./images/meta/advantages/adv-5-dark.webp">
                                        <source type="image/avif" srcset="./images/meta/advantages/adv-5.avif">
                                        <source type="image/webp" srcset="./images/meta/advantages/adv-5.webp"><img
                                            src="./images/meta/advantages/adv-5.png" alt="clock" width="50" height="50">
                                    </picture>
                                </div>
                                <p><?= $local['adv_5_text']; ?></p>
                            </li>
                            <li class="top__advantages-item top__advantages-item--6">
                                <div class="top__advantages-image">
                                    <picture>
                                        <source type="image/avif" media="(min-width: 768px)"
                                            srcset="./images/meta/advantages/adv-6-dark.avif">
                                        <source type="image/webp" media="(min-width: 768px)"
                                            srcset="./images/meta/advantages/adv-6-dark.webp">
                                        <source type="image/avif" srcset="./images/meta/advantages/adv-6.avif">
                                        <source type="image/webp" srcset="./images/meta/advantages/adv-6.webp"><img
                                            src="./images/meta/advantages/adv-6.png" alt="percents" width="50"
                                            height="50">
                                    </picture>
                                </div>
                                <p><?= $local['adv_6_text']; ?></p>
                            </li>
                        </ul>
                        <div class="top__btn-block"><a class="button button--yellow custom-btn btn-7"
                                href="#become"><span><?= $local['cta_main_button']; ?></span></a>
                        </div>
                    </div>
                </div>
            </section>
            <section class="become" id="become">
                <div class="container">
                    <div class="become__inner">
                        <h3 class="become__title title"><?= $local['form_title']; ?></h3>
                        <p class="become__subtitle"><?= $local['form_subtitle']; ?></p>
                        <form class="form animated-page-content" id="formHomeReg" action="send.php" novalidate>
                            <label class="form__label" for="name">
                                <input class="form__input" type="text" id="name" name="name" value=""
                                    placeholder="<?= $local['field_name']; ?>" required>
                                <div class="validate-block"><span>*Required field </span></div>
                            </label>
                            <label class="form__label" for="email">
                                <input class="form__input" type="email" id="email" name="email" value=""
                                    placeholder="<?= $local['field_email']; ?>" required>
                                <div class="validate-block"><span>*Required field </span></div>
                            </label>
                            <label class="form__label" for="country">
                                <input class="form__input" type="text" id="country" name="country" value=""
                                    placeholder="<?= $local['field_country']; ?>">
                                <div class="validate-block"><span>*Required field </span></div>
                            </label>
                            <!-- form__input--invalid клас для подчеркивания невалидного поля -->
                            <label class="form__label" for="phone">
                                <input class="form__input " type="text" id="phone" name="phone" value="" placeholder=""
                                    required>
                                <div class="validate-block"><span>*Required field</span></div>
                            </label>
                            <label class="form__label" for="messanger">
                                <!-- <input class="form__input" type="text" id="messanger" name="messanger" value=""
                                    placeholder="<?= $local['field_messenger']; ?>"> -->
                                <!-- <input class="form__input" type="text" id="messanger" name="messanger" value=""
                                    placeholder="<?= $local['field_messenger']; ?>" required minlength="6"
                                    maxlength="128" pattern="@[A-Za-z][A-Za-z0-9_]{3,30}[A-Za-z0-9]" autocomplete="off"
                                    autocapitalize="off" spellcheck="false"> -->

                                <input class="form__input" type="text" id="messanger" name="messanger" value=""
                                    placeholder="<?= $local['field_messenger']; ?>" required maxlength="128"
                                    autocomplete="off" autocapitalize="off" spellcheck="false">
                                <div class="validate-block validate-block--messanger" id="telegramValidationMessage"
                                    data-invalid="<?= htmlspecialchars($local['telegram_invalid'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-not-found="<?= htmlspecialchars($local['telegram_not_found'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <span><?= htmlspecialchars($local['telegram_invalid'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </label>
                            <input type="hidden" id="registrationLeadId" name="lead_id">
                            <input type="hidden" id="currentCountry" name="currentCountry" value="<?= $region ?>">
                            <div class="form__actions">
                                <button class="button button--submit sub-form"
                                    type="submit"><?= $local['button_submit']; ?></button>
                            </div>
                            <div class="form__status"></div>
                        </form>
                        <div class="become__layout">
                            <div class="become__circle-layout"> </div>
                            <div class="become__image"><img src="./images/meta/form/Phone-mc.png" alt="phone"
                                    width="516" height="600"></div>
                            <div class="become__image-decor become__image-decor--blue"> <img
                                    src="./images/meta/form/invite-friend-elements.png" alt="Figure fill" width="196"
                                    height="196"></div>
                            <div class="become__image-decor become__image-decor--yellow"><img
                                    src="./images/meta/form/new-logo-lent-yellow.png" alt="yellow figure" width="365"
                                    height="391"></div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <footer class="footer">
        <div class="footer-wrap container">
            <div class="footer__inner"> <a class="button button--footer button--yellow"
                    href="#become"><?= $local['cta_secondary_button']; ?></a>
                <div class="logo">
                    <div class="logo__link">
                        Footer logotype <svg viewBox="0 0 114 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0.440375 0.27354H6.20746C7.48216 0.27354 8.50782 0.587571 9.28587 1.21703C10.0639 1.84649 10.4536 2.60774 10.4536 3.50216C10.4536 4.03769 10.3186 4.51995 10.0512 4.95175C9.78252 5.38213 9.3928 5.7242 8.88348 5.97795C9.59258 6.19945 10.1385 6.57377 10.5198 7.10369C10.9025 7.63362 11.0938 8.26028 11.0938 8.98367C11.0938 10.0659 10.697 10.9632 9.90493 11.6739C9.11281 12.3861 8.08996 12.7422 6.83918 12.7422H0.440375C0.312343 12.7422 0.208229 12.7016 0.125219 12.6216C0.0422085 12.5417 0 12.4408 0 12.3188V0.712341C0 0.584767 0.0408015 0.481025 0.120998 0.398311C0.201194 0.315598 0.308122 0.27354 0.440375 0.27354ZM7.1459 4.01526C7.1459 3.69563 7.02349 3.43907 6.78009 3.24561C6.53669 3.05214 6.19902 2.95541 5.76568 2.95541H3.20644V5.11577H5.76568C6.19761 5.11577 6.53528 5.01483 6.78009 4.81296C7.02349 4.61108 7.1459 4.34471 7.1459 4.01386V4.01526ZM7.60316 8.86731C7.60316 8.48598 7.4709 8.19158 7.2078 7.98129C6.9447 7.77101 6.57749 7.66726 6.10616 7.66726H3.20644V10.0772H6.10616C6.57186 10.0772 6.93767 9.96922 7.20358 9.75472C7.4695 9.53883 7.60175 9.24442 7.60175 8.86871L7.60316 8.86731Z"
                                fill="white" />
                            <path
                                d="M13.706 0.27354H23.1959C23.3239 0.27354 23.4294 0.318402 23.5153 0.405321C23.6011 0.493642 23.6447 0.60159 23.6447 0.727762V2.77316C23.6447 2.89513 23.6011 2.99747 23.5153 3.08299C23.4294 3.1685 23.3225 3.21196 23.1959 3.21196H16.4735V5.08353H21.8748C21.9972 5.08353 22.0999 5.12839 22.1857 5.21531C22.2715 5.30363 22.3151 5.41158 22.3151 5.53775V7.41772C22.3151 7.5453 22.2715 7.65044 22.1857 7.73596C22.0999 7.82147 21.9957 7.86493 21.8748 7.86493H16.4735V9.80239H23.1959C23.3239 9.80239 23.4294 9.84585 23.5153 9.93136C23.6011 10.0169 23.6447 10.1234 23.6447 10.2496V12.295C23.6447 12.417 23.6011 12.5207 23.5153 12.609C23.4294 12.6974 23.3225 12.7422 23.1959 12.7422H13.706C13.5836 12.7422 13.4809 12.7002 13.3951 12.6132C13.3092 12.5277 13.2656 12.4212 13.2656 12.295V0.712341C13.2656 0.590374 13.3078 0.488034 13.3951 0.402517C13.4823 0.317 13.585 0.27354 13.706 0.27354Z"
                                fill="white" />
                            <path
                                d="M25.3686 2.77316V0.727762C25.3686 0.600188 25.4122 0.493642 25.5023 0.405321C25.5909 0.317 25.6992 0.27354 25.8259 0.27354H35.6801C35.8082 0.27354 35.9137 0.318402 35.9995 0.405321C36.0853 0.493642 36.1289 0.60159 36.1289 0.727762V2.77316C36.1289 2.90074 36.0853 3.00448 35.9995 3.08719C35.9137 3.16991 35.8068 3.21196 35.6801 3.21196H32.4062V12.295C32.4062 12.4226 32.3625 12.5277 32.2767 12.6132C32.1909 12.6988 32.0868 12.7422 31.9658 12.7422H29.5388C29.4108 12.7422 29.3038 12.7002 29.2152 12.6132C29.1266 12.5277 29.0815 12.4212 29.0815 12.295V3.21196H25.8244C25.6964 3.21196 25.5895 3.16991 25.5008 3.08299C25.4122 2.99747 25.3672 2.89373 25.3672 2.77316H25.3686Z"
                                fill="white" />
                            <path
                                d="M39.4918 10.6562L38.9346 12.3539C38.8952 12.4815 38.8319 12.5768 38.7433 12.6441C38.6547 12.71 38.5435 12.7436 38.4113 12.7436H35.9013C35.7015 12.7436 35.565 12.6791 35.4904 12.5487C35.4159 12.4198 35.4201 12.2459 35.5031 12.0314L39.7408 0.729164C39.7901 0.586168 39.8675 0.474015 39.9688 0.394106C40.0715 0.314196 40.1812 0.27354 40.2966 0.27354H43.0472C43.1583 0.27354 43.2638 0.314196 43.3665 0.394106C43.4692 0.474015 43.5452 0.586168 43.5945 0.729164L47.8322 12.0146C47.9152 12.2249 47.9166 12.3987 47.8364 12.5361C47.7562 12.6735 47.6183 12.7436 47.4256 12.7436H44.7664C44.6286 12.7436 44.5174 12.7128 44.4344 12.6525C44.3514 12.5922 44.2881 12.4927 44.2431 12.3539L43.662 10.6562H39.4904H39.4918ZM40.3641 7.98971H42.7981L41.5769 4.29705L40.3641 7.98971Z"
                                fill="#FFBB04" />
                            <path
                                d="M57.2334 12.295L53.0125 5.33167V12.3202C53.0125 12.4422 52.9731 12.5431 52.8958 12.623C52.8184 12.703 52.7157 12.7436 52.5876 12.7436H50.2451C50.117 12.7436 50.0129 12.703 49.9299 12.623C49.8469 12.5431 49.8047 12.4422 49.8047 12.3202V0.712341C49.8047 0.590374 49.8469 0.488034 49.9341 0.402517C50.0214 0.317 50.1241 0.27354 50.2451 0.27354H53.2869C53.4248 0.27354 53.5415 0.301579 53.6358 0.356254C53.7301 0.410929 53.8159 0.502053 53.8933 0.629628L57.9143 7.38548V0.712341C57.9143 0.590374 57.9566 0.488034 58.0396 0.402517C58.1226 0.317 58.2253 0.27354 58.3477 0.27354H60.6832C60.8 0.27354 60.9027 0.318402 60.9942 0.405321C61.0856 0.493642 61.1306 0.595982 61.1306 0.712341V12.3202C61.1306 12.4366 61.0856 12.5361 60.997 12.6188C60.9083 12.7016 60.8028 12.7436 60.6818 12.7436H58.1634C57.9256 12.7436 57.7343 12.7086 57.5893 12.6399C57.4458 12.5712 57.3262 12.4562 57.232 12.2964L57.2334 12.295Z"
                                fill="#FFBB04" />
                            <path
                                d="M64.1044 0.27354H69.4312C71.2925 0.27354 72.8064 0.848327 73.9728 1.9951C75.1391 3.14327 75.7216 4.63631 75.7216 6.47423C75.7216 8.31215 75.1363 9.83043 73.9643 10.9954C72.7924 12.1604 71.2813 12.7422 69.4312 12.7422H64.1044C63.982 12.7422 63.8793 12.7002 63.7935 12.6132C63.7077 12.5277 63.6641 12.4212 63.6641 12.295V0.712341C63.6641 0.590374 63.7063 0.488034 63.7935 0.402517C63.8807 0.317 63.9834 0.27354 64.1044 0.27354ZM66.8719 9.80379H69.3566C70.2092 9.80379 70.9239 9.47995 71.5008 8.83086C72.0762 8.18177 72.3646 7.39669 72.3646 6.47563C72.3646 5.55457 72.0776 4.78632 71.505 4.15686C70.931 3.5274 70.2162 3.21337 69.3566 3.21337H66.8719V9.80379Z"
                                fill="#FFBB04" />
                            <path
                                d="M80.0113 12.2936V7.93082L75.7736 1.00114C75.6681 0.824495 75.6512 0.659068 75.7244 0.504857C75.7961 0.350646 75.9241 0.27354 76.1071 0.27354H78.9744C79.0799 0.27354 79.1742 0.301579 79.2572 0.356254C79.3402 0.410929 79.412 0.49224 79.4739 0.595982L81.6673 4.4793H81.7095L83.8861 0.595982C83.9466 0.490838 84.0197 0.410929 84.1027 0.356254C84.1858 0.301579 84.28 0.27354 84.3855 0.27354H87.1024C87.3022 0.27354 87.4372 0.34644 87.509 0.49224C87.5807 0.63804 87.5582 0.807672 87.4428 1.00114L83.222 7.93082V12.2936C83.222 12.4156 83.1798 12.5193 83.0968 12.6076C83.0138 12.6959 82.9139 12.7408 82.7971 12.7408H80.4545C80.3321 12.7408 80.2294 12.6988 80.1436 12.6118C80.0578 12.5263 80.0142 12.4198 80.0142 12.2936H80.0113Z"
                                fill="white" />
                            <path
                                d="M94.6384 0C96.5279 0 98.1248 0.629462 99.4332 1.88839C100.74 3.14731 101.395 4.68381 101.395 6.5007C101.395 8.31759 100.74 9.84708 99.4332 11.1088C98.1262 12.3705 96.5279 13 94.6384 13C92.7488 13 91.159 12.3691 89.8519 11.1088C88.5449 9.84708 87.8906 8.31198 87.8906 6.5007C87.8906 4.68942 88.5449 3.14731 89.8519 1.88839C91.159 0.629462 92.7545 0 94.6384 0ZM94.6384 10.0265C95.6077 10.0265 96.4167 9.68867 97.0653 9.01294C97.714 8.33722 98.0375 7.49887 98.0375 6.5007C98.0375 5.50253 97.7154 4.66419 97.0696 3.98846C96.4238 3.31274 95.6134 2.97487 94.6398 2.97487C93.6661 2.97487 92.8628 3.31274 92.217 3.98846C91.5712 4.66419 91.249 5.50253 91.249 6.5007C91.249 7.49887 91.5712 8.33722 92.217 9.01294C92.8628 9.68867 93.6704 10.0265 94.6398 10.0265H94.6384Z"
                                fill="white" />
                            <path
                                d="M114 0.69351V8.15312C114 9.66579 113.502 10.8504 112.504 11.7098C111.507 12.5678 110.2 12.9968 108.582 12.9968C106.964 12.9968 105.658 12.5678 104.663 11.7098C103.669 10.8518 103.172 9.66579 103.172 8.15312V0.69351C103.172 0.577151 103.214 0.477614 103.301 0.390695C103.387 0.305178 103.491 0.261719 103.612 0.261719H105.939C106.062 0.261719 106.164 0.305178 106.25 0.390695C106.336 0.476212 106.38 0.577151 106.38 0.69351V7.88816C106.38 8.54986 106.578 9.07839 106.973 9.46952C107.369 9.86206 107.905 10.0569 108.582 10.0569C109.258 10.0569 109.792 9.86206 110.186 9.47373C110.579 9.084 110.775 8.55547 110.775 7.88816V0.69351C110.775 0.577151 110.819 0.477614 110.909 0.390695C110.997 0.305178 111.103 0.261719 111.224 0.261719H113.551C113.673 0.261719 113.777 0.305178 113.866 0.390695C113.955 0.476212 113.998 0.577151 113.998 0.69351H114Z"
                                fill="white" />
                        </svg></div>
                </div>
                <div class="soc-networks"><a href="<?= $e_mail ?>">e mail<svg class="soc-networks__svg icon-gmail"
                            width="21" height="16" viewBox="0 0 21 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M2.23125 0H18.1687C19.4004 0 20.4 0.9996 20.4 2.23125V13.0687C20.4 13.6605 20.1649 14.228 19.7465 14.6465C19.328 15.0649 18.7605 15.3 18.1687 15.3H2.23125C1.63949 15.3 1.07196 15.0649 0.653518 14.6465C0.235077 14.228 0 13.6605 0 13.0687L0 2.23125C0 0.9996 0.9996 0 2.23125 0ZM1.9125 13.07C1.9125 13.246 2.0553 13.3888 2.23125 13.3888H18.1687C18.2533 13.3888 18.3344 13.3552 18.3941 13.2954C18.4539 13.2356 18.4875 13.1546 18.4875 13.07V4.85647L10.6845 9.43118C10.5376 9.51749 10.3704 9.563 10.2 9.563C10.0296 9.563 9.86237 9.51749 9.7155 9.43118L1.9125 4.85647V13.07ZM18.4875 2.63925V2.23125C18.4875 2.14671 18.4539 2.06564 18.3941 2.00586C18.3344 1.94608 18.2533 1.9125 18.1687 1.9125H2.23125C2.14671 1.9125 2.06564 1.94608 2.00586 2.00586C1.94608 2.06564 1.9125 2.14671 1.9125 2.23125V2.63925L10.2 7.497L18.4875 2.63925Z"
                                fill="white" />
                        </svg></a>

                    <a href="<?= $wats_app_link; ?>">Wats App
                        <svg class="soc-networks__svg icon-watsapp" viewBox="0 0 15 15" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M12.2113 2.09032C11.5527 1.42512 10.7683 0.897691 9.9038 0.538812C9.03928 0.179932 8.11196 -0.00321811 7.17592 4.27895e-05C3.25394 4.27895e-05 0.0574649 3.19652 0.0574649 7.11849C0.0574649 8.37554 0.387887 9.59666 1.00563 10.6741L0 14.3662L3.77113 13.375C4.81268 13.9424 5.98352 14.2441 7.17592 14.2441C11.0979 14.2441 14.2944 11.0476 14.2944 7.12568C14.2944 5.22216 13.5545 3.43356 12.2113 2.09032ZM7.17592 13.0374C6.11282 13.0374 5.07127 12.75 4.15901 12.2113L3.94352 12.082L1.70239 12.671L2.29859 10.4874L2.15493 10.2647C1.5643 9.32153 1.25068 8.23133 1.24986 7.11849C1.24986 3.85737 3.90761 1.19962 7.16873 1.19962C8.74902 1.19962 10.2359 1.81737 11.3493 2.93793C11.9006 3.48669 12.3375 4.13942 12.6346 4.85828C12.9318 5.57714 13.0833 6.34783 13.0804 7.12568C13.0948 10.3868 10.437 13.0374 7.17592 13.0374ZM10.4227 8.61258C10.2431 8.52638 9.36676 8.0954 9.20873 8.03075C9.04352 7.97328 8.92859 7.94455 8.80648 8.11695C8.68437 8.29652 8.34676 8.69878 8.2462 8.81371C8.14563 8.93582 8.03789 8.95018 7.85831 8.8568C7.67873 8.77061 7.10409 8.57666 6.42887 7.97328C5.89733 7.4992 5.54535 6.91737 5.43761 6.73779C5.33704 6.55821 5.42324 6.46483 5.51662 6.37145C5.59563 6.29244 5.6962 6.16314 5.7824 6.06258C5.86859 5.96201 5.90451 5.883 5.96197 5.76807C6.01944 5.64596 5.99071 5.5454 5.94761 5.4592C5.90451 5.373 5.54535 4.49666 5.40169 4.13751C5.25803 3.79272 5.10718 3.83582 4.99944 3.82863H4.65465C4.53254 3.82863 4.34578 3.87173 4.18056 4.05131C4.02254 4.23089 3.56282 4.66187 3.56282 5.53821C3.56282 6.41455 4.20211 7.26216 4.28831 7.37709C4.37451 7.4992 5.54535 9.29497 7.32676 10.0636C7.75056 10.2503 8.08099 10.3581 8.33958 10.4371C8.76338 10.5736 9.15127 10.552 9.46014 10.5089C9.80493 10.4586 10.5161 10.0779 10.6597 9.66131C10.8106 9.24469 10.8106 8.89272 10.7603 8.81371C10.71 8.73469 10.6023 8.69878 10.4227 8.61258Z"
                                fill="white" />
                        </svg> </a><a href="<?= $tg_link; ?>">Telegram<svg class="soc-networks__svg icon-telegram"
                            width="18" height="15" viewBox="0 0 18 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M15.8649 0.0938185L0.794416 5.90527C-0.234085 6.31837 -0.228134 6.89212 0.605716 7.14797L4.47492 8.35497L13.4271 2.70672C13.8504 2.44917 14.2372 2.58772 13.9193 2.86992L6.66622 9.41577H6.66452L6.66622 9.41662L6.39932 13.4048C6.79032 13.4048 6.96287 13.2255 7.18217 13.0138L9.06152 11.1863L12.9707 14.0738C13.6915 14.4707 14.2091 14.2667 14.3885 13.4065L16.9546 1.31272C17.2173 0.259569 16.5526 -0.217281 15.8649 0.0938185Z"
                                fill="white" />
                        </svg></a>
                </div>
                <div class="footer__copy">
                    <p>Copyright © 2019 - <?= Date('Y') ?> «BETANDYOU» All rights reserved and protected by law.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/gsap.min.js"></script>
    <script src="js/intlTelInput.js" defer></script>
    <script src="js/main.min.js<?= $updateFile ?>" defer></script>
    <script src="js/lead-form.js<?= $updateFile ?>"></script>
</body>

</html>